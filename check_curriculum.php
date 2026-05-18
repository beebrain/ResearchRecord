<?php
$db = new mysqli('localhost', 'root', '', 'researchrecord');
if ($db->connect_error) die('Connection failed: ' . $db->connect_error);

echo "=== Curriculum table structure ===\n";
$result = $db->query('DESCRIBE curriculum');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | Null:' . $row['Null'] . ' | Default:' . $row['Default'] . "\n";
}

echo "\n=== Sample curriculum data ===\n";
$result = $db->query('SELECT * FROM curriculum LIMIT 5');
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

$db->close();
