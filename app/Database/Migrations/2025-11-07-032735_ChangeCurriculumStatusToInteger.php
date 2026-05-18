<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeCurriculumStatusToInteger extends Migration
{
    public function up()
    {
        // Convert existing 'active'/'inactive' values to 1/0
        $this->db->query("UPDATE curriculum SET status = '1' WHERE status = 'active'");
        $this->db->query("UPDATE curriculum SET status = '0' WHERE status = 'inactive'");

        // Change column type from ENUM to TINYINT
        $this->db->query("ALTER TABLE curriculum MODIFY COLUMN status TINYINT(1) NOT NULL DEFAULT 1");
    }

    public function down()
    {
        // Change column type back to ENUM
        $this->db->query("ALTER TABLE curriculum MODIFY COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active'");

        // Convert existing 1/0 values back to 'active'/'inactive'
        $this->db->query("UPDATE curriculum SET status = 'active' WHERE status = '1'");
        $this->db->query("UPDATE curriculum SET status = 'inactive' WHERE status = '0'");
    }
}
