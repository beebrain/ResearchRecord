<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRoleSystem extends Migration
{
    public function up()
    {
        // Add role and managed_faculties columns to user table
        $fields = [
            'role' => [
                'type' => 'ENUM',
                'constraint' => ['user', 'faculty_admin', 'super_admin'],
                'default' => 'user',
                'null' => false,
                'after' => 'admin'
            ],
            'managed_faculties' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON array of faculty IDs for faculty admins',
                'after' => 'role'
            ]
        ];

        $this->forge->addColumn('user', $fields);

        // Update existing admin users to super_admin role
        $this->db->query("UPDATE `user` SET `role` = 'super_admin' WHERE `admin` = 1");
    }

    public function down()
    {
        // Remove the columns
        $this->forge->dropColumn('user', ['role', 'managed_faculties']);
    }
}
