#!/usr/bin/env php
<?php
declare(strict_types=1);

$host = $argv[1] ?? 'localhost';
$user = $argv[2] ?? 'rac';
$pass = $argv[3] ?? getenv('RR_MYSQL_PASS') ?: '';
$db   = $argv[4] ?? 'rac';

if ($pass === '') {
    fwrite(STDERR, "Usage: php check-curriculum-duplicates.php [host] [user] [pass] [db]\n");
    exit(2);
}

$m = new mysqli($host, $user, $pass, $db);
$m->set_charset('utf8mb4');

echo "=== Curricula with 2568 in name ===\n";
$r = $m->query("SELECT id, name, code, faculty_id, chair_email FROM curriculum WHERE status = 1 AND name LIKE '%2568%' ORDER BY name, id");
while ($row = $r->fetch_assoc()) {
    echo sprintf(
        "id=%d faculty_id=%d code=%s chair=%s name=%s\n",
        $row['id'],
        $row['faculty_id'],
        $row['code'],
        $row['chair_email'] ?? '',
        $row['name']
    );
}

echo "\n=== Duplicate (name, faculty_id) among active ===\n";
$r2 = $m->query(
    "SELECT name, faculty_id, COUNT(*) AS cnt,
            GROUP_CONCAT(id ORDER BY id) AS ids,
            GROUP_CONCAT(code ORDER BY id) AS codes,
            GROUP_CONCAT(IFNULL(chair_email,'') ORDER BY id SEPARATOR ' | ') AS chairs
     FROM curriculum WHERE status = 1
     GROUP BY name, faculty_id HAVING cnt > 1
     ORDER BY cnt DESC"
);
while ($row = $r2->fetch_assoc()) {
    echo sprintf(
        "faculty_id=%d cnt=%d ids=%s codes=%s chairs=[%s]\n  name=%s\n",
        $row['faculty_id'],
        $row['cnt'],
        $row['ids'],
        $row['codes'],
        $row['chairs'],
        $row['name']
    );
}

$m->close();
