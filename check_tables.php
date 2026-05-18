<?php

/**
 * ตรวจสอบชื่อตารางจริงในฐานข้อมูล
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Load environment
    if (file_exists('.env')) {
        $env = parse_ini_file('.env');
    } else {
        $env = parse_ini_file('env');
    }

    $hostname = $env['database.default.hostname'] ?? 'localhost';
    $database = $env['database.default.database'] ?? 'researchrecord';
    $username = $env['database.default.username'] ?? 'root';
    $password = $env['database.default.password'] ?? '';
    $port = $env['database.default.port'] ?? 3306;

    $conn = new mysqli($hostname, $username, $password, $database, $port);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    echo "=== รายชื่อตารางในฐานข้อมูล ===\n\n";

    $result = $conn->query("SHOW TABLES");
    $tables = [];
    if ($result) {
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
    }

    echo "พบตารางทั้งหมด " . count($tables) . " ตาราง:\n\n";
    foreach ($tables as $table) {
        // นับจำนวนแถว
        $countResult = $conn->query("SELECT COUNT(*) as count FROM `{$table}`");
        $count = $countResult ? $countResult->fetch_assoc()['count'] : 0;
        echo "  - {$table} ({$count} records)\n";
    }

    // ตรวจสอบตารางที่เกี่ยวข้องกับ users
    echo "\n=== ตรวจสอบตารางที่เกี่ยวข้องกับ Users ===\n";
    $userRelated = array_filter($tables, function ($table) {
        return stripos($table, 'user') !== false;
    });
    if (count($userRelated) > 0) {
        foreach ($userRelated as $table) {
            echo "  ✅ {$table}\n";
        }
    } else {
        echo "  ⚠️  ไม่พบตารางที่เกี่ยวข้องกับ users\n";
    }

    // ตรวจสอบตารางที่เกี่ยวข้องกับ curriculum
    echo "\n=== ตรวจสอบตารางที่เกี่ยวข้องกับ Curriculum ===\n";
    $curriculumRelated = array_filter($tables, function ($table) {
        return stripos($table, 'curriculum') !== false;
    });
    if (count($curriculumRelated) > 0) {
        foreach ($curriculumRelated as $table) {
            echo "  ✅ {$table}\n";
        }
    } else {
        echo "  ⚠️  ไม่พบตารางที่เกี่ยวข้องกับ curriculum\n";
    }

    $conn->close();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
