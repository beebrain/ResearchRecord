#!/usr/bin/env php
<?php

/**
 * Run only SchemaClarityImprovements (safe when full spark migrate cannot replay history).
 */
use CodeIgniter\Boot;
use Config\Paths;

define('FCPATH', __DIR__ . '/../public/');
chdir(FCPATH);

$_SERVER['CI_ENVIRONMENT'] = $_ENV['CI_ENVIRONMENT'] ?? 'development';
putenv('CI_ENVIRONMENT=' . $_SERVER['CI_ENVIRONMENT']);
define('ENVIRONMENT', $_SERVER['CI_ENVIRONMENT']);

require FCPATH . '../app/Config/Paths.php';
$paths = new Paths();
require $paths->systemDirectory . '/Boot.php';
Boot::bootConsole($paths);

require FCPATH . '../app/Database/Migrations/20260530170000_SchemaClarityImprovements.php';

$migration = new App\Database\Migrations\SchemaClarityImprovements();
$migration->up();

$db      = \Config\Database::connect();
$version = '20260530170000';
$class   = 'App\\Database\\Migrations\\SchemaClarityImprovements';
$exists  = $db->table('migrations')->where('version', $version)->countAllResults();

if ($exists === 0) {
    $db->table('migrations')->insert([
        'version'   => $version,
        'class'     => $class,
        'group'     => 'default',
        'namespace' => 'App',
        'time'      => time(),
        'batch'     => (int) $db->query('SELECT COALESCE(MAX(batch), 0) + 1 AS b FROM migrations')->getRow()->b,
    ]);
}

echo "SchemaClarityImprovements applied.\n";
