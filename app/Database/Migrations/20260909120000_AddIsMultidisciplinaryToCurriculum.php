<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * ธงหลักสูตรพหุสาขา — ใช้กับกฎผู้รับผิดชอบ 1 สาขาหลัก + 1 พหุสาขา
 */
class AddIsMultidisciplinaryToCurriculum extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('is_multidisciplinary', 'curriculum')) {
            return;
        }

        $this->forge->addColumn('curriculum', [
            'is_multidisciplinary' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'null'       => false,
                'default'    => 0,
                'after'      => 'degree_level',
                'comment'    => '1 = หลักสูตรพหุสาขา',
            ],
        ]);
    }

    public function down()
    {
        if ($this->db->fieldExists('is_multidisciplinary', 'curriculum')) {
            $this->forge->dropColumn('curriculum', 'is_multidisciplinary');
        }
    }
}
