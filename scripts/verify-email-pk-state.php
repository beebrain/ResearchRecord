#!/usr/bin/env php
<?php
/**
 * Read-only health check for the email-as-PK migration state.
 * Boots CI (reads .env internally) and prints schema facts + row counts.
 * Prints NO credentials. Safe to run on production.
 */

use CodeIgniter\Boot;
use Config\Paths;

define('FCPATH', __DIR__ . '/../public/');
chdir(FCPATH);

$_SERVER['CI_ENVIRONMENT'] = $_ENV['CI_ENVIRONMENT'] ?? 'production';
putenv('CI_ENVIRONMENT=' . $_SERVER['CI_ENVIRONMENT']);
define('ENVIRONMENT', $_SERVER['CI_ENVIRONMENT']);

require FCPATH . '../app/Config/Paths.php';
$paths = new Paths();
require $paths->systemDirectory . '/Boot.php';
Boot::bootConsole($paths);

$db = \Config\Database::connect();

$line = static fn (string $k, $v) => printf("%-42s : %s\n", $k, $v);

echo "=== EMAIL-PK STATE (read-only) ===\n";

// PRIMARY KEY of user
$pk = $db->query("SHOW KEYS FROM `user` WHERE Key_name = 'PRIMARY'")->getResultArray();
$pkCols = implode(',', array_map(static fn ($r) => $r['Column_name'], $pk));
$line('user PRIMARY KEY', $pkCols !== '' ? $pkCols : '(none)');

// uid column should be gone
$uid = $db->query("SHOW COLUMNS FROM `user` LIKE 'uid'")->getResultArray();
$line('user.uid column exists?', $uid ? 'YES (unexpected)' : 'no (expected)');

// counts
$line('user rows', $db->table('user')->countAllResults());
$dup = $db->query("SELECT COUNT(*) c FROM (SELECT email FROM `user` GROUP BY email HAVING COUNT(*) > 1) x")->getRow()->c;
$line('duplicate emails', $dup);

// views usable
foreach (['publication_view', 'teacher_curriculum_view'] as $v) {
    try {
        $c = $db->query("SELECT COUNT(*) c FROM `{$v}`")->getRow()->c;
        $line("view {$v} rows", $c);
    } catch (\Throwable $e) {
        $line("view {$v}", 'ERROR: ' . $e->getMessage());
    }
}

// email-keyed child columns present
foreach ([
    'teacher_curriculum' => 'teacher_email',
    'publications'       => 'created_by_email',
    'user_roles'         => 'user_email',
    'cv_sections'        => 'owner_email_norm',
] as $t => $col) {
    $exists = $db->query("SHOW COLUMNS FROM `{$t}` LIKE " . $db->escape($col))->getResultArray();
    $line("{$t}.{$col}", $exists ? 'present' : 'MISSING');
}

echo "=== END ===\n";
