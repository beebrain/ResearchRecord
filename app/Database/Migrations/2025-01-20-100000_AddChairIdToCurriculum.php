<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddChairIdToCurriculum extends Migration
{
    public function up()
    {
        $fields = [
            'chair_id' => [
                'type' => 'INT',
                'constraint' => 3,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Foreign key to user.uid - ประธานหลักสูตร',
                'after' => 'status'
            ]
        ];

        $this->forge->addColumn('curriculum', $fields);

        // Add foreign key constraint
        $this->forge->addForeignKey('chair_id', 'user', 'uid', 'CASCADE', 'SET NULL', 'fk_curriculum_chair');
    }

    public function down()
    {
        // Drop foreign key first
        $this->forge->dropForeignKey('curriculum', 'fk_curriculum_chair');
        
        // Drop column
        $this->forge->dropColumn('curriculum', 'chair_id');
    }
}

