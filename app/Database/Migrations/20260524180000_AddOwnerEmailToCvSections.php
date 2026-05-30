<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOwnerEmailToCvSections extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('cv_sections')) {
            return;
        }

        if (! $this->db->fieldExists('owner_email_norm', 'cv_sections')) {
            $this->forge->addColumn('cv_sections', [
                'owner_email_norm' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'user_uid',
                ],
            ]);
        }

        if (! $this->indexExists('cv_sections', 'idx_cv_sections_owner_email')) {
            $this->db->query('CREATE INDEX `idx_cv_sections_owner_email` ON `cv_sections` (`owner_email_norm`)');
        }

        if ($this->db->tableExists('user')) {
            $this->db->query(
                'UPDATE `cv_sections` cs
                 JOIN `user` u ON u.uid = cs.user_uid
                 SET cs.owner_email_norm = LOWER(TRIM(u.email))
                 WHERE (cs.owner_email_norm IS NULL OR TRIM(cs.owner_email_norm) = \'\')
                   AND u.email IS NOT NULL
                   AND TRIM(u.email) <> \'\''
            );
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('cv_sections')) {
            return;
        }

        if ($this->indexExists('cv_sections', 'idx_cv_sections_owner_email')) {
            $this->db->query('DROP INDEX `idx_cv_sections_owner_email` ON `cv_sections`');
        }

        if ($this->db->fieldExists('owner_email_norm', 'cv_sections')) {
            $this->forge->dropColumn('cv_sections', 'owner_email_norm');
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $rows = $this->db->query('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$indexName])
            ->getResultArray();

        return $rows !== [];
    }
}
