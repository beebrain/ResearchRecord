<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDeanIdToFaculties extends Migration
{
    public function up()
    {
        $fields = [
            'dean_id' => [
                'type' => 'INT',
                'constraint' => 3,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Foreign key to user.uid - คณบดีของคณะ',
                'after' => 'status'
            ]
        ];

        $this->forge->addColumn('faculties', $fields);

        // Add foreign key constraint
        $this->forge->addForeignKey('dean_id', 'user', 'uid', 'CASCADE', 'SET NULL', 'fk_faculties_dean');
    }

    public function down()
    {
        // Drop foreign key first
        $this->forge->dropForeignKey('faculties', 'fk_faculties_dean');
        
        // Drop column
        $this->forge->dropColumn('faculties', 'dean_id');
    }
}

