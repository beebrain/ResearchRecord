<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCitizenIdAndPassportIdToUserTable extends Migration
{
    public function up()
    {
        $fields = [
            'citizen_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'comment' => 'Thai citizen ID (13 digits)',
                'after' => 'nationality'
            ],
            'passport_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'comment' => 'Passport number for international users',
                'after' => 'citizen_id'
            ]
        ];

        $this->forge->addColumn('user', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('user', [
            'citizen_id',
            'passport_id'
        ]);
    }
}
