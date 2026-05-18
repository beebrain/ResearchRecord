<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApiFieldsToUserTable extends Migration
{
    public function up()
    {
        $fields = [
            'faculty_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'curriculum_id'
            ],
            'department_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'faculty_id'
            ],
            'user_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'TEACHER, STUDENT, STAFF, etc.',
                'after' => 'department_id'
            ],
            'degree' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'BACHELOR, MASTER, DOCTORAL',
                'after' => 'user_type'
            ],
            'gender' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'after' => 'degree'
            ],
            'nickname' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'gender'
            ],
            'birth_date' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'nickname'
            ],
            'nationality' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'birth_date'
            ]
        ];

        $this->forge->addColumn('user', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('user', [
            'faculty_id',
            'department_id',
            'user_type',
            'degree',
            'gender',
            'nickname',
            'birth_date',
            'nationality'
        ]);
    }
}
