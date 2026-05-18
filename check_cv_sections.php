<?php
// Check cv_sections table
require_once 'app/Config/Paths.php';
$paths = new \Config\Paths();
require_once $paths->systemDirectory . '/bootstrap.php';

$db = \Config\Database::connect();

echo "CV Sections:\n";
$sections = $db->table('cv_sections')->get()->getResultArray();
foreach ($sections as $row) {
    echo "ID: {$row['id']}, user_uid: {$row['user_uid']}, type: {$row['type']}, title: {$row['title']}, is_default: " . ($row['is_default'] ?? 'N/A') . "\n";
}
