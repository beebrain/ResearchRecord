#!/usr/bin/env php
<?php
declare(strict_types=1);

$m = new mysqli('localhost', 'rac', $argv[1] ?? getenv('RR_MYSQL_PASS') ?: '', 'rac');
$m->set_charset('utf8mb4');

echo "=== ids 113, 114 ===\n";
$r = $m->query('SELECT id, name, code, faculty_id, degree_level, chair_email FROM curriculum WHERE id IN (113, 114)');
while ($row = $r->fetch_assoc()) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== SCMA/SCPH codes ===\n";
$r = $m->query("SELECT id, name, code, degree_level FROM curriculum WHERE code IN ('SCMA01','SCPH01')");
while ($row = $r->fetch_assoc()) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== sample names with ป.โท/ป.เอก in name ===\n";
$r = $m->query("SELECT id, name, code, degree_level FROM curriculum WHERE status=1 AND (name LIKE '%ป.โท%' OR name LIKE '%ป.เอก%') LIMIT 10");
while ($row = $r->fetch_assoc()) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
}

$m->close();
