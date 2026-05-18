<?php

require __DIR__ . '/vendor/autoload.php';

$db = \Config\Database::connect();

echo "Deleting recently imported users (last 1 hour)..." . PHP_EOL;

$result = $db->query("DELETE FROM user WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$deleted = $db->affectedRows();

echo "Deleted: {$deleted} users" . PHP_EOL;
echo "Done!" . PHP_EOL;
