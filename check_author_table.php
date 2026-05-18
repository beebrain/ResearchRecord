<?php
$db = mysqli_connect('localhost', 'root', '', 'research_record');
if (!$db) die('Connection failed: ' . mysqli_connect_error());

echo "=== publication_authors table structure ===\n";
$result = mysqli_query($db, 'DESCRIBE publication_authors');
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | Null:' . $row['Null'] . ' | Default:' . $row['Default'] . "\n";
}

echo "\n=== Sample data from publication_authors ===\n";
$result = mysqli_query($db, 'SELECT * FROM publication_authors LIMIT 5');
while ($row = mysqli_fetch_assoc($result)) {
    print_r($row);
}

mysqli_close($db);
