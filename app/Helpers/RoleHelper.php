<?php

namespace App\Helpers;

use App\Libraries\UserIdentity;

/**
 * Role-based permissions — identity via normalized email (no uid).
 */
class RoleHelper
{
    public static function isGodMode(): bool
    {
        return session()->get('god_mode') === true;
    }

    public static function emailOf(?array $user): string
    {
        if (! is_array($user)) {
            return '';
        }

        return UserIdentity::normalizeEmail((string) ($user['email'] ?? ''));
    }

    public static function isSuperAdmin($user): bool
    {
        if (self::isGodMode()) {
            return true;
        }

        return is_array($user) && ($user['role'] ?? '') === 'super_admin';
    }

    public static function isFacultyAdmin($user): bool
    {
        if (self::isGodMode()) {
            return true;
        }

        return is_array($user) && ($user['role'] ?? '') === 'faculty_admin';
    }

    public static function isRegularUser($user): bool
    {
        if (! is_array($user)) {
            return true;
        }

        $role = $user['role'] ?? 'user';

        return $role === 'user' || $role === '';
    }

    public static function isDean($user): bool
    {
        if (self::isGodMode()) {
            return true;
        }

        $email = self::emailOf($user);
        if ($email === '') {
            return false;
        }

        return \Config\Database::connect()->table('faculties')
            ->where('dean_email', $email)
            ->countAllResults() > 0;
    }

    public static function isChair($user): bool
    {
        if (self::isGodMode()) {
            return true;
        }

        $email = self::emailOf($user);
        if ($email === '') {
            return false;
        }

        return \Config\Database::connect()->table('curriculum')
            ->where('chair_email', $email)
            ->countAllResults() > 0;
    }

    public static function getDeanFaculties($user): array
    {
        $email = self::emailOf($user);
        if ($email === '') {
            return [];
        }

        $faculties = \Config\Database::connect()->table('faculties')
            ->select('id')
            ->where('dean_email', $email)
            ->get()
            ->getResultArray();

        return array_column($faculties, 'id');
    }

    public static function getChairCurricula($user): array
    {
        $email = self::emailOf($user);
        if ($email === '') {
            return [];
        }

        $curricula = \Config\Database::connect()->table('curriculum')
            ->select('id')
            ->where('chair_email', $email)
            ->get()
            ->getResultArray();

        return array_column($curricula, 'id');
    }

    public static function getChairFaculties($user): array
    {
        $email = self::emailOf($user);
        if ($email === '') {
            return [];
        }

        $curricula = \Config\Database::connect()->table('curriculum')
            ->select('faculty_id')
            ->where('chair_email', $email)
            ->get()
            ->getResultArray();

        return array_values(array_unique(array_column($curricula, 'faculty_id')));
    }

    public static function canManageAllFaculties($user): bool
    {
        return self::isSuperAdmin($user);
    }

    public static function getManagedFaculties($user): array
    {
        if (! is_array($user)) {
            return [];
        }

        if (self::isSuperAdmin($user)) {
            return [];
        }

        if (self::isFacultyAdmin($user) && ! empty($user['managed_faculties'])) {
            $faculties = json_decode($user['managed_faculties'], true);

            return is_array($faculties) ? $faculties : [];
        }

        return [];
    }

    public static function canAccessFaculty($user, int $facultyId): bool
    {
        if (self::isSuperAdmin($user)) {
            return true;
        }

        if (self::isFacultyAdmin($user)) {
            return in_array($facultyId, self::getManagedFaculties($user), true);
        }

        return false;
    }

    public static function canAccessCurriculum($user, int $curriculumId, $curriculumModel): bool
    {
        if (self::isSuperAdmin($user)) {
            return true;
        }

        if (self::isFacultyAdmin($user)) {
            $curriculum = $curriculumModel->find($curriculumId);
            if ($curriculum) {
                return self::canAccessFaculty($user, (int) $curriculum['faculty_id']);
            }
        }

        return false;
    }

    public static function canAccessUser($user, string $targetEmail, $userModel): bool
    {
        $targetEmail = UserIdentity::normalizeEmail($targetEmail);
        if ($targetEmail === '') {
            return false;
        }

        if (self::isSuperAdmin($user)) {
            return true;
        }

        if (self::isFacultyAdmin($user)) {
            $targetUser = $userModel->getUserByEmail($targetEmail);
            if (! $targetUser) {
                return false;
            }

            if (! empty($targetUser['faculty_id']) && self::canAccessFaculty($user, (int) $targetUser['faculty_id'])) {
                return true;
            }

            if (! empty($targetUser['curriculum_id'])) {
                $curriculumModel = new \App\Models\CurriculumModel();

                return self::canAccessCurriculum($user, (int) $targetUser['curriculum_id'], $curriculumModel);
            }

            return false;
        }

        return self::emailOf($user) !== '' && self::emailOf($user) === $targetEmail;
    }

    public static function canAccessPublication($user, int $publicationId, $publicationModel): bool
    {
        if (self::isSuperAdmin($user)) {
            return true;
        }

        $publication = $publicationModel->find($publicationId);
        if (! $publication) {
            return false;
        }

        if (self::isFacultyAdmin($user)) {
            $userModel = new \App\Models\UserModel();
            $creatorEmail = UserIdentity::normalizeEmail((string) ($publication['created_by_email'] ?? ''));
            if ($creatorEmail !== '' && self::canAccessUser($user, $creatorEmail, $userModel)) {
                return true;
            }

            return self::publicationHasAuthorInManagedScope($user, $publicationId, $userModel);
        }

        $userEmail = self::emailOf($user);
        $creatorEmail = UserIdentity::normalizeEmail((string) ($publication['created_by_email'] ?? ''));

        return $userEmail !== '' && $userEmail === $creatorEmail;
    }

    private static function publicationHasAuthorInManagedScope($user, int $publicationId, \App\Models\UserModel $userModel): bool
    {
        $db   = \Config\Database::connect();
        $rows = $db->table('publication_authors pa')
            ->select('pa.author_email, a.email AS authors_table_email, a.user_email')
            ->join('authors a', 'a.id = pa.author_id', 'left')
            ->where('pa.publication_id', $publicationId)
            ->get()
            ->getResultArray();

        $checked = [];
        foreach ($rows as $row) {
            foreach ([$row['author_email'] ?? '', $row['authors_table_email'] ?? '', $row['user_email'] ?? ''] as $raw) {
                $email = UserIdentity::normalizeEmail((string) $raw);
                if ($email === '' || isset($checked[$email])) {
                    continue;
                }
                $checked[$email] = true;
                if (self::canAccessUser($user, $email, $userModel)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function getFacultyFilterSQL($user, string $facultyColumn = 'faculty_id'): string
    {
        if (self::isSuperAdmin($user)) {
            return '';
        }

        if (self::isFacultyAdmin($user)) {
            $managedFaculties = self::getManagedFaculties($user);
            if ($managedFaculties !== []) {
                $ids = implode(',', array_map('intval', $managedFaculties));

                return "$facultyColumn IN ($ids)";
            }
        }

        return '1=0';
    }

    public static function getUserFilterSQL($user, $curriculumModel): string
    {
        if (self::isSuperAdmin($user)) {
            return '';
        }

        if (self::isFacultyAdmin($user)) {
            $managedFaculties = self::getManagedFaculties($user);
            if ($managedFaculties !== []) {
                $curricula = $curriculumModel
                    ->whereIn('faculty_id', $managedFaculties)
                    ->findAll();

                if ($curricula !== []) {
                    $curriculumIds = array_column($curricula, 'id');
                    $ids           = implode(',', array_map('intval', $curriculumIds));

                    return "curriculum_id IN ($ids)";
                }
            }
        }

        $email = self::emailOf($user);
        if ($email !== '') {
            $db = \Config\Database::connect();

            return 'email = ' . $db->escape($email);
        }

        return '1=0';
    }

    public static function canManageRoles($user): bool
    {
        return self::isSuperAdmin($user);
    }

    public static function getRoleDisplayName(string $role): string
    {
        $roles = [
            'super_admin'   => 'Super Administrator',
            'faculty_admin' => 'Faculty Administrator',
            'user'          => 'User',
        ];

        return $roles[$role] ?? 'Unknown';
    }

    public static function getRoleBadge(string $role): string
    {
        $badges = [
            'super_admin'   => '<span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs font-medium rounded">Super Admin</span>',
            'faculty_admin' => '<span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded">Faculty Admin</span>',
            'user'          => '<span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-medium rounded">User</span>',
        ];

        return $badges[$role] ?? '<span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-medium rounded">Unknown</span>';
    }
}
