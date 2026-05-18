<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRolesTables extends Migration
{
    public function up()
    {
        // Create roles table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'unique' => true,
            ],
            'display_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('roles');

        // Create user_roles junction table (many-to-many)
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 3,
                'unsigned' => true,
            ],
            'role_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'faculty_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'comment' => 'Optional: Scope role to specific faculty (for faculty_admin role)',
            ],
            'assigned_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'assigned_by' => [
                'type' => 'INT',
                'constraint' => 3,
                'unsigned' => true,
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'user', 'uid', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('faculty_id', 'faculties', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_roles');

        // Insert default roles
        $roles = [
            [
                'name' => 'user',
                'display_name' => 'User',
                'description' => 'Regular user - can manage own publications',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'faculty_admin',
                'display_name' => 'Faculty Administrator',
                'description' => 'Faculty admin - can manage users and publications within assigned faculties',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'super_admin',
                'display_name' => 'Super Administrator',
                'description' => 'Super admin - full system access',
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('roles')->insertBatch($roles);

        // Migrate existing users with admin=1 to super_admin role
        $superAdminRoleId = $this->db->table('roles')->where('name', 'super_admin')->get()->getRow()->id;

        $admins = $this->db->table('user')->where('admin', 1)->get()->getResultArray();

        if (!empty($admins)) {
            $userRoles = [];
            foreach ($admins as $admin) {
                $userRoles[] = [
                    'user_id' => $admin['uid'],
                    'role_id' => $superAdminRoleId,
                    'faculty_id' => null,
                    'assigned_at' => date('Y-m-d H:i:s'),
                    'assigned_by' => null,
                ];
            }
            $this->db->table('user_roles')->insertBatch($userRoles);
        }

        // Migrate existing role column data to user_roles table
        $usersWithRole = $this->db->table('user')
            ->where('role IS NOT NULL')
            ->whereNotIn('role', ['user'])
            ->get()
            ->getResultArray();

        if (!empty($usersWithRole)) {
            $userRoles = [];
            foreach ($usersWithRole as $user) {
                $role = $this->db->table('roles')->where('name', $user['role'])->get()->getRow();
                if ($role) {
                    // If user has faculty_admin role and managed_faculties
                    if ($user['role'] === 'faculty_admin' && !empty($user['managed_faculties'])) {
                        $faculties = json_decode($user['managed_faculties'], true);
                        if (is_array($faculties)) {
                            foreach ($faculties as $facultyId) {
                                $userRoles[] = [
                                    'user_id' => $user['uid'],
                                    'role_id' => $role->id,
                                    'faculty_id' => $facultyId,
                                    'assigned_at' => date('Y-m-d H:i:s'),
                                    'assigned_by' => null,
                                ];
                            }
                        }
                    } else {
                        $userRoles[] = [
                            'user_id' => $user['uid'],
                            'role_id' => $role->id,
                            'faculty_id' => null,
                            'assigned_at' => date('Y-m-d H:i:s'),
                            'assigned_by' => null,
                        ];
                    }
                }
            }
            if (!empty($userRoles)) {
                $this->db->table('user_roles')->insertBatch($userRoles);
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('user_roles', true);
        $this->forge->dropTable('roles', true);
    }
}
