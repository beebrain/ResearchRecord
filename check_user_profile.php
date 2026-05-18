<?php
$db = new mysqli('localhost', 'root', '', 'research_record');
$result = $db->query('DESCRIBE user_profile');
echo "Current columns in user_profile:\n";
while($row = $result->fetch_assoc()) { 
    echo $row['Field'] . ' - ' . $row['Type'] . "\n"; 
}
$db->close();
