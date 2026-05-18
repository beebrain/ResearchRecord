<?php
$db = new mysqli('localhost', 'root', '', 'researchrecord');
if ($db->connect_error) die('Connection failed: ' . $db->connect_error);

echo "=== author table structure ===\n";
$result = $db->query('DESCRIBE author');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | Null:' . $row['Null'] . ' | Default:' . $row['Default'] . "\n";
}

echo "\n=== Sample data from author table ===\n";
$result = $db->query('SELECT * FROM author LIMIT 5');
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

echo "\n=== publication_authors table structure ===\n";
$result = $db->query('DESCRIBE publication_authors');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | Null:' . $row['Null'] . ' | Default:' . $row['Default'] . "\n";
}

echo "\n=== Sample data from publication_authors ===\n";
$result = $db->query('SELECT * FROM publication_authors LIMIT 5');
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

$db->close();
