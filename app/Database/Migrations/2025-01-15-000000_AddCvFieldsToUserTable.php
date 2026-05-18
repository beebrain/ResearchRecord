<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCvFieldsToUserTable extends Migration
{
    public function up()
    {
        $fields = [
            'bio' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Professional biography/summary for CV',
                'after' => 'passport_id'
            ],
            'expertise' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Areas of expertise (comma-separated)',
                'after' => 'bio'
            ],
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Contact phone number',
                'after' => 'expertise'
            ],
            'institution' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Current institution/affiliation',
                'after' => 'phone'
            ],
            'google_scholar' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'comment' => 'Google Scholar profile URL',
                'after' => 'institution'
            ],
            'orcid' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'comment' => 'ORCID profile URL',
                'after' => 'google_scholar'
            ],
            'scopus' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'comment' => 'Scopus profile URL',
                'after' => 'orcid'
            ],
            'researchgate' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'comment' => 'ResearchGate profile URL',
                'after' => 'scopus'
            ],
            'linkedin' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'comment' => 'LinkedIn profile URL',
                'after' => 'researchgate'
            ],
        ];

        // Check if columns exist before adding
        $existingColumns = $this->db->getFieldNames('user');
        
        foreach ($fields as $fieldName => $fieldDef) {
            if (!in_array($fieldName, $existingColumns)) {
                $this->forge->addColumn('user', [$fieldName => $fieldDef]);
            }
        }
    }

    public function down()
    {
        $fieldsToDrop = [
            'bio',
            'expertise',
            'phone',
            'institution',
            'google_scholar',
            'orcid',
            'scopus',
            'researchgate',
            'linkedin'
        ];

        $this->forge->dropColumn('user', $fieldsToDrop);
    }
}








