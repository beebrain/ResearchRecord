<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserProfileTable extends Migration
{
    public function up()
    {
        // Create user_profile table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_uid' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => false,
                'comment'    => 'Foreign key to user.uid',
            ],
            'bio' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Professional biography/summary for CV',
            ],
            'expertise' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Areas of expertise (comma-separated)',
            ],
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'Contact phone number',
            ],
            'institution' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Current institution/affiliation',
            ],
            'google_scholar' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'comment'    => 'Google Scholar profile URL',
            ],
            'orcid' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'comment'    => 'ORCID profile URL',
            ],
            'scopus' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'comment'    => 'Scopus profile URL',
            ],
            'researchgate' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'comment'    => 'ResearchGate profile URL',
            ],
            'linkedin' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'comment'    => 'LinkedIn profile URL',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('user_uid');
        $this->forge->createTable('user_profile', true);

        // Migrate existing data from user table to user_profile table
        $db = \Config\Database::connect();
        
        // Check if CV columns exist in user table before migrating
        $existingColumns = $db->getFieldNames('user');
        $cvColumns = ['bio', 'expertise', 'phone', 'institution', 'google_scholar', 'orcid', 'scopus', 'researchgate', 'linkedin'];
        $columnsExist = array_intersect($cvColumns, $existingColumns);
        
        if (!empty($columnsExist)) {
            // Get all users with CV data
            $users = $db->table('user')
                ->select('uid, bio, expertise, phone, institution, google_scholar, orcid, scopus, researchgate, linkedin')
                ->get()
                ->getResultArray();
            
            foreach ($users as $user) {
                // Only insert if user has at least one CV field with data
                $hasData = false;
                $profileData = [
                    'user_uid' => $user['uid'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                
                foreach ($cvColumns as $column) {
                    if (isset($user[$column]) && !empty($user[$column])) {
                        $profileData[$column] = $user[$column];
                        $hasData = true;
                    }
                }
                
                // Insert profile record if there's any data, or create empty record for all users
                if ($hasData || true) { // Create for all users to maintain one-to-one relationship
                    $db->table('user_profile')->insert($profileData);
                }
            }
        } else {
            // If columns don't exist, create empty profile records for all users
            $users = $db->table('user')->select('uid')->get()->getResultArray();
            foreach ($users as $user) {
                $db->table('user_profile')->insert([
                    'user_uid' => $user['uid'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // Remove CV columns from user table
        if (!empty($columnsExist)) {
            $this->forge->dropColumn('user', $cvColumns);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        
        // Migrate data back from user_profile to user table
        $profiles = $db->table('user_profile')->get()->getResultArray();
        
        // Add columns back to user table
        $fields = [
            'bio' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Professional biography/summary for CV',
            ],
            'expertise' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Areas of expertise (comma-separated)',
            ],
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'Contact phone number',
            ],
            'institution' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Current institution/affiliation',
            ],
            'google_scholar' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'comment'    => 'Google Scholar profile URL',
            ],
            'orcid' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'comment'    => 'ORCID profile URL',
            ],
            'scopus' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'comment'    => 'Scopus profile URL',
            ],
            'researchgate' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'comment'    => 'ResearchGate profile URL',
            ],
            'linkedin' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'comment'    => 'LinkedIn profile URL',
            ],
        ];
        
        $existingColumns = $db->getFieldNames('user');
        foreach ($fields as $fieldName => $fieldDef) {
            if (!in_array($fieldName, $existingColumns)) {
                $this->forge->addColumn('user', [$fieldName => $fieldDef]);
            }
        }
        
        // Migrate data back
        foreach ($profiles as $profile) {
            $updateData = [];
            foreach (['bio', 'expertise', 'phone', 'institution', 'google_scholar', 'orcid', 'scopus', 'researchgate', 'linkedin'] as $field) {
                if (isset($profile[$field]) && !empty($profile[$field])) {
                    $updateData[$field] = $profile[$field];
                }
            }
            
            if (!empty($updateData)) {
                $db->table('user')
                    ->where('uid', $profile['user_uid'])
                    ->update($updateData);
            }
        }
        
        // Drop user_profile table
        $this->forge->dropTable('user_profile', true);
    }
}








