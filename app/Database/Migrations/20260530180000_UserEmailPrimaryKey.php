<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migrate identity: user.email is PRIMARY KEY; drop user.uid and uid-based FK columns.
 */
class UserEmailPrimaryKey extends Migration
{
    public function up()
    {
        $this->normalizeUserEmails();
        $this->dropViews();

        $this->addEmailColumnsAndBackfill();
        $this->dropUidForeignKeys();
        $this->dropUidColumns();
        $this->promoteUserEmailPrimaryKey();
        $this->addEmailForeignKeys();
        $this->recreateViews();
    }

    public function down()
    {
        throw new \RuntimeException('UserEmailPrimaryKey cannot be reversed automatically — restore from DB backup.');
    }

    private function normalizeUserEmails(): void
    {
        $this->db->query('UPDATE `user` SET `email` = LOWER(TRIM(`email`)) WHERE `email` IS NOT NULL');
    }

    private function dropViews(): void
    {
        $this->db->query('DROP VIEW IF EXISTS `publication_view`');
        $this->db->query('DROP VIEW IF EXISTS `teacher_curriculum_view`');
    }

    private function addEmailColumnsAndBackfill(): void
    {
        $this->addColumnIfMissing('teacher_curriculum', 'teacher_email', 'VARCHAR(255) NULL AFTER `teacher_uid`');
        if ($this->db->fieldExists('teacher_uid', 'teacher_curriculum')) {
            $this->db->query(
                'UPDATE `teacher_curriculum` tc
                 INNER JOIN `user` u ON tc.teacher_uid = u.uid
                 SET tc.teacher_email = u.email'
            );
        }
        $this->db->query(
            'DELETE FROM `teacher_curriculum` WHERE `teacher_email` IS NULL OR TRIM(`teacher_email`) = \'\''
        );

        $this->addColumnIfMissing('publications', 'created_by_email', 'VARCHAR(255) NULL AFTER `created_by`');
        if ($this->db->fieldExists('created_by', 'publications')) {
            $this->db->query(
                'UPDATE `publications` p
                 INNER JOIN `user` u ON p.created_by = u.uid
                 SET p.created_by_email = u.email'
            );
        }

        $this->addColumnIfMissing('curriculum', 'chair_email', 'VARCHAR(255) NULL AFTER `chair_id`');
        $this->db->query(
            'UPDATE `curriculum` c
             INNER JOIN `user` u ON c.chair_id = u.uid
             SET c.chair_email = u.email
             WHERE c.chair_id IS NOT NULL'
        );

        $this->addColumnIfMissing('faculties', 'dean_email', 'VARCHAR(255) NULL AFTER `dean_id`');
        $this->db->query(
            'UPDATE `faculties` f
             INNER JOIN `user` u ON f.dean_id = u.uid
             SET f.dean_email = u.email
             WHERE f.dean_id IS NOT NULL'
        );

        $this->addColumnIfMissing('authors', 'user_email', 'VARCHAR(255) NULL AFTER `user_uid`');
        $this->addColumnIfMissing('authors', 'created_by_email', 'VARCHAR(255) NULL AFTER `created_by`');
        $this->db->query(
            'UPDATE `authors` a
             LEFT JOIN `user` u1 ON a.user_uid = u1.uid
             LEFT JOIN `user` u2 ON a.created_by = u2.uid
             SET a.user_email = u1.email,
                 a.created_by_email = u2.email'
        );

        $this->addColumnIfMissing('user_roles', 'user_email', 'VARCHAR(255) NULL AFTER `user_id`');
        $this->db->query(
            'UPDATE `user_roles` ur
             INNER JOIN `user` u ON ur.user_id = u.uid
             SET ur.user_email = u.email'
        );

        $this->addColumnIfMissing('admission_form_teachers', 'user_email', 'VARCHAR(255) NULL AFTER `user_id`');
        $this->db->query(
            'UPDATE `admission_form_teachers` aft
             INNER JOIN `user` u ON aft.user_id = u.uid
             SET aft.user_email = u.email
             WHERE aft.user_id IS NOT NULL'
        );

        $this->addColumnIfMissing('student_admission_forms', 'created_by_email', 'VARCHAR(255) NULL AFTER `created_by`');
        $this->db->query(
            'UPDATE `student_admission_forms` saf
             INNER JOIN `user` u ON saf.created_by = u.uid
             SET saf.created_by_email = u.email
             WHERE saf.created_by IS NOT NULL'
        );

        if ($this->db->tableExists('user_profile')) {
            $this->addColumnIfMissing('user_profile', 'user_email', 'VARCHAR(255) NULL AFTER `user_uid`');
            $this->db->query(
                'UPDATE `user_profile` up
                 INNER JOIN `user` u ON CAST(up.user_uid AS UNSIGNED) = u.uid
                 SET up.user_email = u.email'
            );
        }

        if ($this->db->fieldExists('owner_email_norm', 'cv_sections')) {
            $this->db->query(
                'UPDATE `cv_sections` cs
                 INNER JOIN `user` u ON CAST(cs.user_uid AS UNSIGNED) = u.uid
                 SET cs.owner_email_norm = u.email
                 WHERE cs.owner_email_norm IS NULL OR TRIM(cs.owner_email_norm) = \'\''
            );
        }
    }

    private function dropUidForeignKeys(): void
    {
        $drops = [
            ['teacher_curriculum', 'fk_teacher_curriculum_user'],
            ['teacher_curriculum', 'fk_teacher_curriculum_curriculum'],
            ['publications', 'fk_publications_created_by'],
            ['curriculum', 'fk_curriculum_chair'],
            ['faculties', 'fk_faculties_dean'],
            ['authors', 'fk_authors_user'],
            ['authors', 'fk_authors_created_by'],
            ['user_roles', 'user_roles_user_id_foreign'],
            ['user_roles', 'user_roles_role_id_foreign'],
            ['user_roles', 'user_roles_faculty_id_foreign'],
            ['admission_form_teachers', 'fk_teacher_user'],
            ['admission_form_teachers', 'fk_teacher_form'],
            ['student_admission_forms', 'fk_admission_created_by'],
            ['student_admission_forms', 'fk_admission_curriculum'],
            ['student_admission_forms', 'fk_admission_faculty'],
            ['user', 'fk_user_curriculum'],
            ['user', 'fk_user_faculty'],
            ['publication_authors', 'fk_pub_authors_author'],
            ['publication_authors', 'FK_publication_authors_publications'],
        ];

        foreach ($drops as [$table, $name]) {
            $this->dropForeignKeyIfExists($table, $name);
        }

        if ($this->indexExists('teacher_curriculum', 'unique_teacher_curriculum')) {
            $this->db->query('ALTER TABLE `teacher_curriculum` DROP INDEX `unique_teacher_curriculum`');
        }
    }

    private function dropUidColumns(): void
    {
        $this->dropColumnIfExists('teacher_curriculum', 'teacher_uid');
        $this->dropColumnIfExists('publications', 'created_by');
        $this->dropColumnIfExists('curriculum', 'chair_id');
        $this->dropColumnIfExists('faculties', 'dean_id');
        $this->dropColumnIfExists('authors', 'user_uid');
        $this->dropColumnIfExists('authors', 'created_by');
        $this->dropColumnIfExists('user_roles', 'user_id');
        $this->dropColumnIfExists('user_roles', 'assigned_by');
        $this->dropColumnIfExists('admission_form_teachers', 'user_id');
        $this->dropColumnIfExists('student_admission_forms', 'created_by');
        $this->dropColumnIfExists('publication_authors', 'uid');
        $this->dropColumnIfExists('cv_sections', 'user_uid');

        if ($this->db->tableExists('user_profile')) {
            $this->dropColumnIfExists('user_profile', 'user_uid');
        }
    }

    private function promoteUserEmailPrimaryKey(): void
    {
        $this->dropForeignKeyIfExists('user', 'fk_user_curriculum');
        $this->dropForeignKeyIfExists('user', 'fk_user_faculty');

        if ($this->db->fieldExists('uid', 'user')) {
            $this->db->query('ALTER TABLE `user` MODIFY `uid` INT(10) UNSIGNED NOT NULL');
            $this->db->query('ALTER TABLE `user` DROP PRIMARY KEY');
            $this->dropColumnIfExists('user', 'uid');
        }

        $this->db->query('ALTER TABLE `user` MODIFY `email` VARCHAR(255) NOT NULL');

        if (! $this->indexExists('user', 'PRIMARY')) {
            $this->db->query('ALTER TABLE `user` ADD PRIMARY KEY (`email`)');
        }
    }

    private function addEmailForeignKeys(): void
    {
        $this->ensureUtf8mb4EmailColumns();

        if (! $this->indexExists('teacher_curriculum', 'uq_teacher_curriculum_email')) {
            $this->db->query(
                'ALTER TABLE `teacher_curriculum`
                 MODIFY `teacher_email` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                 ADD UNIQUE KEY `uq_teacher_curriculum_email` (`teacher_email`, `curriculum_id`)'
            );
        } else {
            $this->db->query(
                'ALTER TABLE `teacher_curriculum`
                 MODIFY `teacher_email` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL'
            );
        }
        $this->addFk('teacher_curriculum', 'fk_tc_teacher_email', 'teacher_email', 'user', 'email', 'CASCADE', 'CASCADE');
        $this->addFk('teacher_curriculum', 'fk_teacher_curriculum_curriculum', 'curriculum_id', 'curriculum', 'id', 'CASCADE', 'CASCADE');

        $this->db->query('ALTER TABLE `publications` MODIFY `created_by_email` VARCHAR(255) NOT NULL');
        $this->addFk('publications', 'fk_publications_created_by_email', 'created_by_email', 'user', 'email', 'RESTRICT', 'CASCADE');

        $this->addFk('curriculum', 'fk_curriculum_chair_email', 'chair_email', 'user', 'email', 'SET NULL', 'CASCADE');
        $this->addFk('faculties', 'fk_faculties_dean_email', 'dean_email', 'user', 'email', 'SET NULL', 'CASCADE');

        $this->addFk('authors', 'fk_authors_user_email', 'user_email', 'user', 'email', 'SET NULL', 'CASCADE');
        $this->addFk('authors', 'fk_authors_created_by_email', 'created_by_email', 'user', 'email', 'SET NULL', 'CASCADE');

        $this->db->query('ALTER TABLE `user_roles` MODIFY `user_email` VARCHAR(255) NOT NULL');
        $this->addFk('user_roles', 'fk_user_roles_user_email', 'user_email', 'user', 'email', 'CASCADE', 'CASCADE');
        $this->addFk('user_roles', 'user_roles_role_id_foreign', 'role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->addFk('user_roles', 'user_roles_faculty_id_foreign', 'faculty_id', 'faculties', 'id', 'CASCADE', 'CASCADE');

        $this->addFk('admission_form_teachers', 'fk_aft_user_email', 'user_email', 'user', 'email', 'SET NULL', 'CASCADE');
        $this->addFk('student_admission_forms', 'fk_admission_created_by_email', 'created_by_email', 'user', 'email', 'SET NULL', 'CASCADE');

        if ($this->db->tableExists('user_profile') && $this->db->fieldExists('user_email', 'user_profile')) {
            $this->db->query('ALTER TABLE `user_profile` MODIFY `user_email` VARCHAR(255) NOT NULL');
            $this->addFk('user_profile', 'fk_user_profile_email', 'user_email', 'user', 'email', 'CASCADE', 'CASCADE');
        }

        $this->addFk('user', 'fk_user_curriculum', 'curriculum_id', 'curriculum', 'id', 'SET NULL', 'CASCADE');
        $this->addFk('user', 'fk_user_faculty', 'faculty_id', 'faculties', 'id', 'SET NULL', 'CASCADE');

        $this->addFk('publication_authors', 'FK_publication_authors_publications', 'publication_id', 'publications', 'id', 'CASCADE', 'CASCADE');
        $this->addFk('publication_authors', 'fk_pub_authors_author', 'author_id', 'authors', 'id', 'SET NULL', 'CASCADE');
    }

    private function ensureUtf8mb4EmailColumns(): void
    {
        $tables = [
            'user', 'teacher_curriculum', 'publications', 'curriculum', 'faculties',
            'authors', 'user_roles', 'admission_form_teachers', 'student_admission_forms',
            'user_profile', 'cv_sections', 'publication_authors',
        ];

        foreach ($tables as $table) {
            if ($this->db->tableExists($table)) {
                $this->db->query(
                    "ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
                );
            }
        }
    }

    private function recreateViews(): void
    {
        $path = APPPATH . 'Database/Sql/recreate_views_email_pk.sql';
        if (! is_file($path)) {
            return;
        }

        $sql = file_get_contents($path);
        if ($sql === false || trim($sql) === '') {
            return;
        }

        foreach (array_filter(array_map('trim', preg_split('/;\s*\n/', $sql))) as $statement) {
            if ($statement !== '') {
                $this->db->query($statement);
            }
        }
    }

    private function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        if (! $this->db->tableExists($table) || $this->db->fieldExists($column, $table)) {
            return;
        }

        $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }

    private function dropColumnIfExists(string $table, string $column): void
    {
        if ($this->db->tableExists($table) && $this->db->fieldExists($column, $table)) {
            $this->forge->dropColumn($table, $column);
        }
    }

    private function dropForeignKeyIfExists(string $table, string $name): void
    {
        if ($this->foreignKeyExists($table, $name)) {
            $this->db->query("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$name}`");
        }
    }

    private function addFk(
        string $table,
        string $name,
        string $column,
        string $refTable,
        string $refColumn,
        string $onDelete,
        string $onUpdate
    ): void {
        if ($this->foreignKeyExists($table, $name)) {
            return;
        }

        $this->db->query(
            "ALTER TABLE `{$table}`
             ADD CONSTRAINT `{$name}`
             FOREIGN KEY (`{$column}`) REFERENCES `{$refTable}` (`{$refColumn}`)
             ON DELETE {$onDelete} ON UPDATE {$onUpdate}"
        );
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
