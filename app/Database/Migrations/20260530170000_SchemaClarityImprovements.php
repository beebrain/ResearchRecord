<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Schema clarity: data cleanup, missing FKs, useful indexes, table comments,
 * seed roles/user_roles if empty.
 */
class SchemaClarityImprovements extends Migration
{
    public function up()
    {
        $this->cleanOrphanFacultyIds();
        $this->cleanOrphanTeacherCurriculum();
        $this->addForeignKeyIfMissing(
            'publications',
            'fk_publications_created_by',
            'created_by',
            'user',
            'uid',
            'RESTRICT',
            'CASCADE'
        );
        $this->addForeignKeyIfMissing(
            'user',
            'fk_user_faculty',
            'faculty_id',
            'faculties',
            'id',
            'SET NULL',
            'CASCADE'
        );
        $this->addIndexIfMissing('publications', 'idx_publications_approve', 'approve');
        $this->addIndexIfMissing('publications', 'idx_publications_created_by', 'created_by');
        $this->addIndexIfMissing('publications', 'idx_publications_year', 'publication_year');
        $this->addCompositeIndexIfMissing(
            'teacher_curriculum',
            'idx_tc_curriculum_status',
            ['curriculum_id', 'status']
        );
        $this->applyTableComments();
        $this->seedRolesAndUserRoles();
    }

    public function down()
    {
        $this->dropForeignKeyIfExists('publications', 'fk_publications_created_by');
        $this->dropForeignKeyIfExists('user', 'fk_user_faculty');

        foreach (
            [
                ['publications', 'idx_publications_approve'],
                ['publications', 'idx_publications_created_by'],
                ['publications', 'idx_publications_year'],
                ['teacher_curriculum', 'idx_tc_curriculum_status'],
            ] as [$table, $index]
        ) {
            if ($this->indexExists($table, $index)) {
                $this->db->query("DROP INDEX `{$index}` ON `{$table}`");
            }
        }
    }

    private function cleanOrphanFacultyIds(): void
    {
        if (! $this->db->tableExists('user') || ! $this->db->tableExists('faculties')) {
            return;
        }

        $this->db->query(
            'UPDATE `user` u
             LEFT JOIN `faculties` f ON f.id = u.faculty_id
             SET u.faculty_id = NULL
             WHERE u.faculty_id IS NOT NULL AND f.id IS NULL'
        );
    }

    private function cleanOrphanTeacherCurriculum(): void
    {
        if (! $this->db->tableExists('teacher_curriculum')) {
            return;
        }

        // Soft-deactivate assignments pointing at deleted users or curricula
        $this->db->query(
            'UPDATE `teacher_curriculum` tc
             LEFT JOIN `user` u ON tc.teacher_uid = u.uid
             LEFT JOIN `curriculum` c ON tc.curriculum_id = c.id
             SET tc.status = 0,
                 tc.notes = CONCAT(COALESCE(tc.notes, \'\'), \' [auto-deactivated: orphan reference]\')
             WHERE u.uid IS NULL OR c.id IS NULL'
        );
    }

    private function seedRolesAndUserRoles(): void
    {
        if (! $this->db->tableExists('roles') || ! $this->db->tableExists('user_roles')) {
            return;
        }

        if ((int) $this->db->table('roles')->countAllResults() > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('roles')->insertBatch([
            [
                'name'         => 'user',
                'display_name' => 'User',
                'description'  => 'Regular user — own publications',
                'created_at'   => $now,
            ],
            [
                'name'         => 'faculty_admin',
                'display_name' => 'Faculty Administrator',
                'description'  => 'Manage users/publications within assigned faculties',
                'created_at'   => $now,
            ],
            [
                'name'         => 'super_admin',
                'display_name' => 'Super Administrator',
                'description'  => 'Full system access',
                'created_at'   => $now,
            ],
        ]);

        $roleMap = [];
        foreach ($this->db->table('roles')->get()->getResultArray() as $row) {
            $roleMap[$row['name']] = (int) $row['id'];
        }

        if (! $this->db->tableExists('user')) {
            return;
        }

        $users = $this->db->table('user')->get()->getResultArray();
        $batch = [];

        foreach ($users as $user) {
            $uid = (int) $user['uid'];

            if (! empty($user['admin']) && isset($roleMap['super_admin'])) {
                $batch[] = $this->userRoleRow($uid, $roleMap['super_admin'], null, $now);
            }

            $roleName = $user['role'] ?? 'user';
            if ($roleName === 'user' || ! isset($roleMap[$roleName])) {
                continue;
            }

            if ($roleName === 'faculty_admin' && ! empty($user['managed_faculties'])) {
                $faculties = json_decode($user['managed_faculties'], true);
                if (is_array($faculties)) {
                    foreach ($faculties as $facultyId) {
                        $batch[] = $this->userRoleRow($uid, $roleMap['faculty_admin'], (int) $facultyId, $now);
                    }
                    continue;
                }
            }

            $batch[] = $this->userRoleRow($uid, $roleMap[$roleName], null, $now);
        }

        if ($batch !== []) {
            $this->db->table('user_roles')->insertBatch($batch);
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function userRoleRow(int $userId, int $roleId, ?int $facultyId, string $assignedAt): array
    {
        return [
            'user_id'     => $userId,
            'role_id'     => $roleId,
            'faculty_id'  => $facultyId,
            'assigned_at' => $assignedAt,
            'assigned_by' => null,
        ];
    }

    private function applyTableComments(): void
    {
        $comments = [
            'user'                      => 'บัญชีผู้ใช้ — PK uid; อีเมล unique; role เก่า (ยังใช้ในแอป) คู่กับ user_roles',
            'faculties'                 => 'คณะ — dean_id → user.uid',
            'curriculum'                => 'หลักสูตร — faculty_id, chair_id → user.uid',
            'teacher_curriculum'          => 'M:N อาจารย์↔หลักสูตร — status 1=active',
            'publications'              => 'ผลงานตีพิมพ์ — approve 1=อนุมัติ; created_by → user.uid',
            'publication_authors'       => 'ผู้แต่งต่อผลงาน — จับคู่ email/author_id',
            'authors'                   => 'ผู้แต่ง canonical ตาม email — user_uid → user.uid',
            'cv_sections'               => 'หัวข้อ CV — owner_email_norm เป็น identity หลัก (migrate)',
            'cv_entries'                => 'รายการใน cv_sections',
            'student_admission_forms'   => 'แบบฟอร์มรับนักศึกษาต่อหลักสูตร/ปี',
            'admission_form_teachers'   => 'อาจารย์ในฟอร์มรับนักศึกษา (สูงสุด 5)',
            'roles'                     => 'บทบาทระบบ (canonical สำหรับ RBAC ใหม่)',
            'user_roles'                => 'M:N user↔role; faculty_id = scope สำหรับ faculty_admin',
            'user_profile'              => 'โปรไฟล์เสริม/ORCID cache — หลีกเลี่ยงซ้ำกับ user ในระยะยาว',
        ];

        foreach ($comments as $table => $comment) {
            if ($this->db->tableExists($table)) {
                $safe = str_replace("'", "''", $comment);
                $this->db->query("ALTER TABLE `{$table}` COMMENT = '{$safe}'");
            }
        }
    }

    private function addForeignKeyIfMissing(
        string $table,
        string $constraint,
        string $column,
        string $refTable,
        string $refColumn,
        string $onDelete,
        string $onUpdate
    ): void {
        if (! $this->db->tableExists($table) || ! $this->db->tableExists($refTable)) {
            return;
        }

        if ($this->foreignKeyExists($table, $constraint)) {
            return;
        }

        $this->db->query(
            "ALTER TABLE `{$table}`
             ADD CONSTRAINT `{$constraint}`
             FOREIGN KEY (`{$column}`) REFERENCES `{$refTable}` (`{$refColumn}`)
             ON DELETE {$onDelete} ON UPDATE {$onUpdate}"
        );
    }

    private function addIndexIfMissing(string $table, string $indexName, string $column): void
    {
        if (! $this->db->tableExists($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        $this->db->query("CREATE INDEX `{$indexName}` ON `{$table}` (`{$column}`)");
    }

    /**
     * @param list<string> $columns
     */
    private function addCompositeIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if (! $this->db->tableExists($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        $cols = implode('`, `', $columns);
        $this->db->query("CREATE INDEX `{$indexName}` ON `{$table}` (`{$cols}`)");
    }

    private function dropForeignKeyIfExists(string $table, string $constraint): void
    {
        if ($this->foreignKeyExists($table, $constraint)) {
            $this->db->query("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $rows = $this->db->query('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$indexName])
            ->getResultArray();

        return $rows !== [];
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $dbName = $this->db->getDatabase();
        $row    = $this->db->query(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$dbName, $table, $constraint, 'FOREIGN KEY']
        )->getRowArray();

        return $row !== null;
    }
}
