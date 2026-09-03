#!/usr/bin/env php
<?php
/**
 * ONE-TIME: rename duplicate applied-science 2568 curricula to include degree in name.
 *
 *   id 113 SCMA01 master  → ... ป.โท 2568
 *   id 114 SCPH01 doctoral → ... ป.เอก 2568
 *
 * Usage (win-kc):
 *   php scripts/rename-applied-science-2568-names.php --dry-run --pass=...
 *   php scripts/rename-applied-science-2568-names.php --apply --pass=...
 */
declare(strict_types=1);

$apply = in_array('--apply', $argv, true);
$pass  = '';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--pass=')) {
        $pass = substr($arg, 7);
    }
}
if ($pass === '') {
    $pass = getenv('RR_MYSQL_PASS') ?: '';
}
if ($pass === '') {
    fwrite(STDERR, "Set --pass= or RR_MYSQL_PASS\n");
    exit(2);
}

$m = new mysqli('localhost', 'rac', $pass, 'rac');
$m->set_charset('utf8mb4');

$updates = [
    113 => 'สาขาวิชาวิทยาศาสตร์ประยุกต์ ป.โท 2568',
    114 => 'สาขาวิชาวิทยาศาสตร์ประยุกต์ ป.เอก 2568',
];

foreach ($updates as $id => $newName) {
    $st = $m->prepare('SELECT id, name, code, degree_level FROM curriculum WHERE id = ?');
    $st->bind_param('i', $id);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    if ($row === null) {
        echo "SKIP id={$id} not found\n";
        continue;
    }

    echo "id={$id} code={$row['code']} degree={$row['degree_level']}\n";
    echo "  before: {$row['name']}\n";
    echo '  after:  ' . $newName . "\n";

    if ($apply) {
        $st = $m->prepare('UPDATE curriculum SET name = ? WHERE id = ?');
        $st->bind_param('si', $newName, $id);
        $st->execute();
        $st->close();
        echo "  -> updated\n";
    }
    echo "\n";
}

if (! $apply) {
    echo "Re-run with --apply to write.\n";
}
