<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Existing curriculum members were stored as instructor before the
 * instructor vs coordinator distinction. Treat those rows as ผู้รับผิดชอบ.
 */
class BackfillTeacherCurriculumAsCoordinator extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('teacher_curriculum')) {
            return;
        }

        $this->db->table('teacher_curriculum')
            ->whereIn('role', ['instructor', 'assistant'])
            ->where('status', 1)
            ->update(['role' => 'coordinator']);
    }

    public function down()
    {
        // Irreversible without a pre-backfill snapshot; leave no-op.
    }
}
