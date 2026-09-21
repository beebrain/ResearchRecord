<?php

namespace App\Services;

use App\Libraries\UserIdentity;
use CodeIgniter\Database\BaseConnection;

/**
 * Business logic for curriculum chair search, role summary, and conflict validation.
 */
class ChairSelectionService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    /**
     * @param array<string,mixed> $user
     * @return array<string,mixed>
     */
    public function formatTeacherForSelection(array $user, int $curriculumId): array
    {
        $email    = UserIdentity::normalizeEmail((string) ($user['email'] ?? ''));
        $roles    = $this->getTeacherCurriculumRoleSummary($email, $curriculumId);
        $conflict = $this->getChairSelectionConflicts($email, $curriculumId);

        return [
            'email'        => $email,
            'name'         => $this->formatTeacherDisplayName($user),
            'role_summary' => $roles['summary_lines'],
            'role_badges'  => $roles['badges'],
            'has_conflict' => $conflict['has_conflict'],
            'warnings'     => $conflict['warnings'],
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function searchTeachers(string $query, int $curriculumId, int $limit = 20): array
    {
        if (mb_strlen(trim($query)) < 2 || $curriculumId <= 0) {
            return [];
        }

        $escaped = $this->db->escapeLikeString(trim($query));
        $like    = '%' . $escaped . '%';

        $users = $this->db->table('user u')
            ->select('u.email, u.titleThai, u.title, u.thai_name, u.thai_lastname, u.gf_name, u.gl_name, u.faculty_id')
            ->where('u.active', 1)
            ->where('u.user_type', 'TEACHER')
            ->groupStart()
                ->like('u.thai_name', $like)
                ->orLike('u.thai_lastname', $like)
                ->orLike('u.gf_name', $like)
                ->orLike('u.gl_name', $like)
                ->orLike('u.email', $like)
            ->groupEnd()
            ->orderBy('u.thai_name', 'ASC')
            ->orderBy('u.gf_name', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        $data = [];
        foreach ($users as $user) {
            $data[] = $this->formatTeacherForSelection($user, $curriculumId);
        }

        return $data;
    }

    /**
     * @return array{summary_lines: list<string>, badges: list<array<string,string>>}
     */
    public function getTeacherCurriculumRoleSummary(string $email, int $excludeCurriculumId): array
    {
        $email = UserIdentity::normalizeEmail($email);
        if ($email === '') {
            return ['summary_lines' => [], 'badges' => []];
        }

        $badges = [];
        $lines  = [];

        $chairs = $this->db->table('curriculum c')
            ->select('c.id, c.name')
            ->where('c.chair_email', $email)
            ->where('c.status', 1)
            ->where('c.id !=', $excludeCurriculumId)
            ->orderBy('c.name', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($chairs as $row) {
            $label    = 'ประธาน: ' . ($row['name'] ?? '-');
            $lines[]  = $label;
            $badges[] = ['type' => 'chair', 'label' => $label];
        }

        $assignments = $this->db->table('teacher_curriculum tc')
            ->select('c.name as curriculum_name, tc.role, tc.curriculum_id')
            ->join('curriculum c', 'c.id = tc.curriculum_id', 'inner')
            ->where('tc.teacher_email', $email)
            ->where('tc.status', 1)
            ->where('tc.curriculum_id !=', $excludeCurriculumId)
            ->orderBy('c.name', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($assignments as $row) {
            $role = (string) ($row['role'] ?? 'instructor');
            if ($role === 'coordinator') {
                $label = 'ผู้รับผิดชอบ: ' . ($row['curriculum_name'] ?? '-');
                $badges[] = ['type' => 'coordinator', 'label' => $label];
            } else {
                $label = 'สมาชิกหลักสูตร: ' . ($row['curriculum_name'] ?? '-');
                $badges[] = ['type' => 'member', 'label' => $label];
            }
            $lines[] = $label;
        }

        if ($lines === []) {
            $lines[] = 'ยังไม่มีตำแหน่งในหลักสูตรอื่น';
        }

        return ['summary_lines' => $lines, 'badges' => $badges];
    }

    /**
     * @return array{has_conflict: bool, warnings: list<string>}
     */
    public function getChairSelectionConflicts(string $email, int $curriculumId): array
    {
        $email = UserIdentity::normalizeEmail($email);
        if ($email === '') {
            return ['has_conflict' => false, 'warnings' => []];
        }

        $warnings = [];

        $otherChairs = $this->db->table('curriculum')
            ->select('name')
            ->where('chair_email', $email)
            ->where('status', 1)
            ->where('id !=', $curriculumId)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($otherChairs as $row) {
            $warnings[] = 'เป็นประธานหลักสูตร «' . ($row['name'] ?? '-') . '» อยู่แล้ว';
        }

        return [
            'has_conflict' => $warnings !== [],
            'warnings'     => $warnings,
        ];
    }

    /**
     * ผู้รับผิดชอบอยู่ได้ 1 สาขาหลัก + 1 พหุสาขา — ประธานไม่นับในโควตานี้
     */
    public static function coordinatorQuotaAllows(
        bool $targetIsMultidisciplinary,
        int $otherRegularCount,
        int $otherMultiCount
    ): bool {
        if ($targetIsMultidisciplinary) {
            return $otherMultiCount < 1;
        }

        return $otherRegularCount < 1;
    }

    /**
     * @return array{allowed: bool, message: string, warnings: list<string>}
     */
    public function getCoordinatorAssignmentConflicts(string $email, int $curriculumId): array
    {
        $email = UserIdentity::normalizeEmail($email);
        if ($email === '' || $curriculumId <= 0) {
            return ['allowed' => false, 'message' => 'ข้อมูลอาจารย์หรือหลักสูตรไม่ถูกต้อง', 'warnings' => []];
        }

        $hasFlag = $this->db->fieldExists('is_multidisciplinary', 'curriculum');
        $targetSelect = $hasFlag ? 'id, name, is_multidisciplinary' : 'id, name';

        $target = $this->db->table('curriculum')
            ->select($targetSelect)
            ->where('id', $curriculumId)
            ->get()
            ->getRowArray();

        if ($target === null) {
            return ['allowed' => false, 'message' => 'ไม่พบหลักสูตร', 'warnings' => []];
        }

        $targetIsMulti = $hasFlag && (int) ($target['is_multidisciplinary'] ?? 0) === 1;

        $others = $this->db->table('teacher_curriculum tc')
            ->select($hasFlag ? 'c.name, c.is_multidisciplinary' : 'c.name')
            ->join('curriculum c', 'c.id = tc.curriculum_id', 'inner')
            ->where('tc.teacher_email', $email)
            ->where('tc.status', 1)
            ->where('tc.role', 'coordinator')
            ->where('tc.curriculum_id !=', $curriculumId)
            ->where('c.status', 1)
            ->orderBy('c.name', 'ASC')
            ->get()
            ->getResultArray();

        $regularNames = [];
        $multiNames   = [];
        foreach ($others as $row) {
            $label = \App\Models\CurriculumModel::displayName($row);
            if ((int) ($row['is_multidisciplinary'] ?? 0) === 1) {
                $multiNames[] = $label;
            } else {
                $regularNames[] = $label;
            }
        }

        if (self::coordinatorQuotaAllows($targetIsMulti, count($regularNames), count($multiNames))) {
            return ['allowed' => true, 'message' => '', 'warnings' => []];
        }

        if ($targetIsMulti) {
            $message = 'เป็นผู้รับผิดชอบหลักสูตรพหุสาขาได้เพียง 1 หลักสูตร (อยู่แล้วที่ «'
                . ($multiNames[0] ?? '-') . '»)';
        } else {
            $message = 'เป็นผู้รับผิดชอบสาขาหลักได้เพียง 1 หลักสูตร (อยู่แล้วที่ «'
                . ($regularNames[0] ?? '-') . '») — หลักสูตรที่สองต้องเป็นพหุสาขา';
        }

        return [
            'allowed'  => false,
            'message'  => $message,
            'warnings' => [$message],
        ];
    }

    /**
     * @param array<string,mixed> $user
     */
    public function formatTeacherDisplayName(array $user): string
    {
        $title = '';
        if (! empty($user['titleThai'])) {
            $title = (string) $user['titleThai'];
        } elseif (! empty($user['title'])) {
            $title = (string) $user['title'];
        }

        if (! empty($user['thai_name']) && ! empty($user['thai_lastname'])) {
            return trim(($title !== '' ? $title . ' ' : '') . $user['thai_name'] . ' ' . $user['thai_lastname']);
        }

        if (! empty($user['gf_name']) && ! empty($user['gl_name'])) {
            return trim(($title !== '' ? $title . ' ' : '') . $user['gf_name'] . ' ' . $user['gl_name']);
        }

        return (string) ($user['email'] ?? '');
    }
}
