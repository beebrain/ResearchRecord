<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * เพิ่มคอลัมน์ majors_detail (JSON ใน TEXT) ให้ student_admission_forms
 * เก็บรายละเอียดวิชาเอก/แขนง พร้อมคุณสมบัติผู้เรียนรายแขนง:
 *   [{ "major_name": "...", "admission_count": 0, "qualifications": ["...", "..."] }]
 */
class AddMajorsDetailToAdmissionForms extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('majors_detail', 'student_admission_forms')) {
            $this->forge->addColumn('student_admission_forms', [
                'majors_detail' => [
                    'type'       => 'TEXT',
                    'null'       => true,
                    'after'      => 'major_count',
                    'comment'    => 'JSON: รายละเอียดวิชาเอก/แขนง + คุณสมบัติรายแขนง',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('majors_detail', 'student_admission_forms')) {
            $this->forge->dropColumn('student_admission_forms', 'majors_detail');
        }
    }
}
