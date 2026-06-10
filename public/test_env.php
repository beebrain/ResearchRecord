<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
require FCPATH . '../app/Config/Paths.php';
$paths = new \Config\Paths();

require $paths->systemDirectory . '/Boot.php';
\CodeIgniter\Boot::preload($paths);

echo "CI_ENVIRONMENT: " . env('CI_ENVIRONMENT') . "\n";
echo "database.default.hostname: " . env('database.default.hostname') . "\n";
