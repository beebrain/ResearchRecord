<?php

/**
 * Test script for dean feature
 * Tests database structure and sample data
 */

$hostname = 'localhost';
$username = 'root';
$password = '';
$database = 'researchrecord';
$port = 3306;

$mysqli = new mysqli($hostname, $username, $password, $database, $port);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}

echo "=== Testing Dean Feature ===\n\n";

// 1. Check column exists
echo "1. Checking dean_id column...\n";
$result = $mysqli->query("SHOW COLUMNS FROM faculties WHERE Field = 'dean_id'");
if ($result->num_rows > 0) {
    $col = $result->fetch_assoc();
    echo "   ✓ Column exists: {$col['Type']} {$col['Null']}\n";
} else {
    echo "   ✗ Column not found!\n";
    exit(1);
}

// 2. Check foreign key exists
echo "\n2. Checking foreign key...\n";
$result = $mysqli->query("
    SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = '{$database}'
    AND TABLE_NAME = 'faculties'
    AND CONSTRAINT_NAME = 'fk_faculties_dean'
");
if ($result->num_rows > 0) {
    $fk = $result->fetch_assoc();
    echo "   ✓ Foreign key exists: {$fk['CONSTRAINT_NAME']}\n";
    echo "     References: {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
} else {
    echo "   ✗ Foreign key not found!\n";
}

// 3. Get sample users for testing
echo "\n3. Getting sample users for dean selection...\n";
$result = $mysqli->query("
    SELECT uid, titleThai, thai_name, thai_lastname, gf_name, gl_name, email, faculty_id
    FROM user
    WHERE active = 1
    AND (thai_name IS NOT NULL OR gf_name IS NOT NULL)
    LIMIT 5
");
$users = $result->fetch_all(MYSQLI_ASSOC);
echo "   Found " . count($users) . " sample users:\n";
foreach ($users as $user) {
    $name = '';
    if (!empty($user['thai_name']) && !empty($user['thai_lastname'])) {
        $title = !empty($user['titleThai']) ? $user['titleThai'] . ' ' : '';
        $name = $title . $user['thai_name'] . ' ' . $user['thai_lastname'];
    } elseif (!empty($user['gf_name']) && !empty($user['gl_name'])) {
        $name = $user['gf_name'] . ' ' . $user['gl_name'];
    } else {
        $name = $user['email'];
    }
    echo "     - UID {$user['uid']}: {$name} (Faculty: {$user['faculty_id']})\n";
}

// 4. Test setting a dean (optional - just show the SQL)
if (count($users) > 0) {
    $testUser = $users[0];
    $testFaculty = 16; // คณะวิทยาศาสตร์และเทคโนโลยี
    
    echo "\n4. Testing dean assignment (dry run)...\n";
    echo "   Would set faculty ID {$testFaculty} dean to user UID {$testUser['uid']}\n";
    echo "   SQL: UPDATE faculties SET dean_id = {$testUser['uid']} WHERE id = {$testFaculty}\n";
    
    // Uncomment to actually test:
    // $mysqli->query("UPDATE faculties SET dean_id = {$testUser['uid']} WHERE id = {$testFaculty}");
    // echo "   ✓ Dean assigned successfully\n";
}

// 5. Check current faculties with dean info
echo "\n5. Current faculties and their deans:\n";
$result = $mysqli->query("
    SELECT f.id, f.name, f.code, f.dean_id,
           u.titleThai, u.thai_name, u.thai_lastname, u.gf_name, u.gl_name
    FROM faculties f
    LEFT JOIN user u ON u.uid = f.dean_id
    ORDER BY f.id
");
$faculties = $result->fetch_all(MYSQLI_ASSOC);
foreach ($faculties as $faculty) {
    $deanName = '-';
    if ($faculty['dean_id']) {
        if (!empty($faculty['thai_name']) && !empty($faculty['thai_lastname'])) {
            $title = !empty($faculty['titleThai']) ? $faculty['titleThai'] . ' ' : '';
            $deanName = $title . $faculty['thai_name'] . ' ' . $faculty['thai_lastname'];
        } elseif (!empty($faculty['gf_name']) && !empty($faculty['gl_name'])) {
            $deanName = $faculty['gf_name'] . ' ' . $faculty['gl_name'];
        }
    }
    echo "   - {$faculty['code']}: {$faculty['name']} - คณบดี: {$deanName}\n";
}

$mysqli->close();

echo "\n✅ All tests completed!\n";
echo "\nNext steps:\n";
echo "1. Open the faculty management page in your browser\n";
echo "2. Click 'Edit' on any faculty\n";
echo "3. Select a dean from the dropdown\n";
echo "4. Save and verify the dean appears in the table\n";

