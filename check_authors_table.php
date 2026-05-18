<?php
$db = new mysqli('localhost', 'root', '', 'researchrecord');
if ($db->connect_error) die('Connection failed: ' . $db->connect_error);

echo "=== authors table structure ===\n";
$result = $db->query('DESCRIBE authors');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | Null:' . $row['Null'] . ' | Default:' . $row['Default'] . "\n";
}

echo "\n=== Sample data from authors ===\n";
$result = $db->query('SELECT * FROM authors LIMIT 5');
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

echo "\n=== user table structure ===\n";
$result = $db->query('DESCRIBE user');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | Null:' . $row['Null'] . ' | Default:' . $row['Default'] . "\n";
}

$db->close();
