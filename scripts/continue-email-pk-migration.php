#!/usr/bin/env php
<?php

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

require APPPATH . 'Database/Migrations/20260530180000_UserEmailPrimaryKey.php';

$m = new App\Database\Migrations\UserEmailPrimaryKey();
$ref = new ReflectionClass($m);

foreach (['dropUidForeignKeys', 'dropUidColumns', 'promoteUserEmailPrimaryKey', 'addEmailForeignKeys', 'recreateViews'] as $step) {
    echo "==> {$step}\n";
    $ref->getMethod($step)->setAccessible(true);
    $ref->getMethod($step)->invoke($m);
}

$db = \Config\Database::connect();
if ($db->table('migrations')->where('version', '20260530180000')->countAllResults() === 0) {
    $batch = (int) $db->query('SELECT COALESCE(MAX(batch), 0) + 1 AS b FROM migrations')->getRow()->b;
    $db->table('migrations')->insert([
        'version' => '20260530180000',
        'class'   => 'App\\Database\\Migrations\\UserEmailPrimaryKey',
        'group'   => 'default',
        'namespace' => 'App',
        'time'    => time(),
        'batch'   => $batch,
    ]);
}

echo "Continue migration finished.\n";
