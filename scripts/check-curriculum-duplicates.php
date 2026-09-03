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
if ($r2->num_rows === 0) {
    echo "(none)\n";
}

echo "\n=== Same name across different faculties (active) ===\n";
$r3 = $m->query(
    "SELECT name, COUNT(*) AS cnt, COUNT(DISTINCT faculty_id) AS faculties,
            GROUP_CONCAT(CONCAT('id', id, ':f', faculty_id, ':', code) ORDER BY id SEPARATOR ' | ') AS detail
     FROM curriculum WHERE status = 1
     GROUP BY name HAVING cnt > 1
     ORDER BY name"
);
while ($row = $r3->fetch_assoc()) {
    echo sprintf("cnt=%d faculties=%d\n  name=%s\n  %s\n\n", $row['cnt'], $row['faculties'], $row['name'], $row['detail']);
}
if ($r3->num_rows === 0) {
    echo "(none)\n";
}

echo "\n=== Same base name within faculty (strip trailing parenthesis) ===\n";
$all = $m->query("SELECT id, name, code, faculty_id, degree_level FROM curriculum WHERE status = 1 ORDER BY faculty_id, name");
$groups = [];
while ($row = $all->fetch_assoc()) {
    $base = preg_replace('/\s*\([^)]*\)\s*$/u', '', trim($row['name']));
    $key  = $row['faculty_id'] . '|' . mb_strtolower($base);
    $groups[$key][] = $row;
}
$found = false;
foreach ($groups as $key => $rows) {
    if (count($rows) <= 1) {
        continue;
    }
    $found = true;
    $base = preg_replace('/\s*\([^)]*\)\s*$/u', '', trim($rows[0]['name']));
    echo "faculty_id={$rows[0]['faculty_id']} base=\"{$base}\" (" . count($rows) . " rows)\n";
    foreach ($rows as $row) {
        echo "  id={$row['id']} code={$row['code']} degree={$row['degree_level']} name={$row['name']}\n";
    }
    echo "\n";
}
if (! $found) {
    echo "(none)\n";
}

$m->close();
