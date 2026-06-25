<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * เพิ่มคอลัมน์ is_ministry_pending และ is_university_pending ให้ student_admission_forms
 * เพื่อรองรับกรณีที่อยู่ระหว่างรอการอนุมัติจาก สป.อว. หรือสภามหาวิทยาลัย
 */
class AddPendingApprovalToAdmissionForms extends Migration
{
    public function up()
    {
        $fields = [];

        if (!$this->db->fieldExists('is_ministry_pending', 'student_admission_forms')) {
            $fields['is_ministry_pending'] = [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => true,
                'after'      => 'university_approval_date',
                'comment'    => 'รอการอนุมัติจาก สป.อว.',
            ];
        }

        if (!$this->db->fieldExists('is_university_pending', 'student_admission_forms')) {
            $fields['is_university_pending'] = [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => true,
                'after'      => 'is_ministry_pending',
                'comment'    => 'รอการอนุมัติจากสภามหาวิทยาลัย',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('student_admission_forms', $fields);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('is_ministry_pending', 'student_admission_forms')) {
            $this->forge->dropColumn('student_admission_forms', 'is_ministry_pending');
        }

        if ($this->db->fieldExists('is_university_pending', 'student_admission_forms')) {
            $this->forge->dropColumn('student_admission_forms', 'is_university_pending');
        }
    }
}
