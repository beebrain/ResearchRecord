<?php
/**
 * Script to update teacher faculty_id from Teacher_matchFaculty.csv
 *
 * This script reads the CSV file and generates SQL UPDATE commands
 * to update the faculty_id for users based on their email address.
 *
 * Usage:
 * php update_teacher_faculty.php
 *
 * Or run in browser:
 * http://localhost/researchRecord/update_teacher_faculty.php
 */

// Database configuration
$host = 'localhost';
$database = 'researchrecord';
$username = 'root';
$password = '';

// CSV file path
$csvFile = __DIR__ . '/Teacher_matchFaculty.csv';

// Connect to database
try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    echo "✓ Database connected successfully\n\n";
} catch (PDOException $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

// Check if CSV file exists
if (!file_exists($csvFile)) {
    die("✗ CSV file not found: {$csvFile}\n");
}

// Load faculties from database into a lookup array
echo "Loading faculties from database...\n";
$stmt = $pdo->query("SELECT id, name, code FROM faculties ORDER BY id");
$faculties = $stmt->fetchAll();

$facultyMap = [];
foreach ($faculties as $faculty) {
    $facultyMap[$faculty['name']] = $faculty['id'];
    echo "  - [{$faculty['id']}] {$faculty['name']} ({$faculty['code']})\n";
}
echo "\n";

// Read CSV file with UTF-8 encoding
echo "Reading CSV file: {$csvFile}\n\n";

// Try to detect and convert encoding
$content = file_get_contents($csvFile);
$encoding = mb_detect_encoding($content, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'ASCII'], true);
echo "Detected encoding: " . ($encoding ? $encoding : 'Unknown') . "\n";

// If not UTF-8, try to convert
if ($encoding && $encoding !== 'UTF-8') {
    $converted = mb_convert_encoding($content, 'UTF-8', $encoding);
    if ($converted) {
        file_put_contents($csvFile . '.utf8', $converted);
        $csvFile = $csvFile . '.utf8';
        echo "Converted to UTF-8 and saved to: {$csvFile}\n";
    }
} else if (!$encoding) {
    // Try Windows-1252 as fallback for Thai encoding issues
    $converted = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
    if ($converted) {
        file_put_contents($csvFile . '.utf8', $converted);
        $csvFile = $csvFile . '.utf8';
        echo "Converted from Windows-1252 to UTF-8 and saved to: {$csvFile}\n";
    }
}

$handle = fopen($csvFile, 'r');

if ($handle === false) {
    die("✗ Cannot open CSV file\n");
}

// Skip header row
$header = fgetcsv($handle);
if ($header === false) {
    fclose($handle);
    die("✗ Cannot read CSV header\n");
}

echo "CSV Header: " . implode(', ', $header) . "\n";
echo "Total columns: " . count($header) . "\n\n";

// Find column indices
$emailCol = array_search('EMAIL', $header);
$facultyIdCol = array_search('IDFaculty', $header);

if ($emailCol === false) {
    fclose($handle);
    die("✗ EMAIL column not found in CSV\n");
}

echo "Using columns: EMAIL (col {$emailCol}), IDFaculty (col " . ($facultyIdCol !== false ? $facultyIdCol : 'NOT FOUND') . ")\n\n";
echo "Processing records...\n";
echo str_repeat('=', 80) . "\n\n";

// Counters
$totalRows = 0;
$updatedCount = 0;
$skippedNoFaculty = 0;
$skippedNotFound = 0;
$skippedNoEmail = 0;
$skippedInvalidFacultyId = 0;
$errors = [];

// Prepare UPDATE statement
$updateStmt = $pdo->prepare("
    UPDATE user
    SET faculty_id = :faculty_id
    WHERE email = :email
    AND active = 1
");

// Process each row
while (($row = fgetcsv($handle)) !== false) {
    $totalRows++;

    // Map CSV columns
    $email = isset($row[$emailCol]) ? trim($row[$emailCol]) : '';
    $facultyId = ($facultyIdCol !== false && isset($row[$facultyIdCol])) ? trim($row[$facultyIdCol]) : '';

    // Skip if email is empty
    if (empty($email)) {
        $skippedNoEmail++;
        echo "[SKIP] Row {$totalRows}: No email address\n";
        continue;
    }

    // Skip if faculty ID is empty
    if (empty($facultyId)) {
        $skippedNoFaculty++;
        echo "[SKIP] Row {$totalRows}: {$email} - No faculty ID specified\n";
        continue;
    }

    // Validate faculty ID is numeric
    if (!is_numeric($facultyId)) {
        $skippedInvalidFacultyId++;
        echo "[SKIP] Row {$totalRows}: {$email} - Invalid faculty ID: '{$facultyId}'\n";
        $errors[] = "Invalid faculty ID: '{$facultyId}' for {$email}";
        continue;
    }

    $facultyId = (int)$facultyId;

    // Check if faculty exists
    $facultyExists = false;
    foreach ($faculties as $faculty) {
        if ($faculty['id'] == $facultyId) {
            $facultyExists = true;
            $facultyName = $faculty['name'];
            break;
        }
    }

    if (!$facultyExists) {
        $skippedNotFound++;
        echo "[SKIP] Row {$totalRows}: {$email} - Faculty ID {$facultyId} not found in database\n";
        $errors[] = "Faculty ID {$facultyId} not found for {$email}";
        continue;
    }

    try {
        // Execute UPDATE
        $updateStmt->execute([
            'faculty_id' => $facultyId,
            'email' => $email
        ]);

        $rowsAffected = $updateStmt->rowCount();

        if ($rowsAffected > 0) {
            $updatedCount++;
            echo "[OK] Row {$totalRows}: Updated {$email} -> Faculty ID {$facultyId} ({$facultyName})\n";
        } else {
            $skippedNotFound++;
            echo "[SKIP] Row {$totalRows}: {$email} - User not found in database\n";
        }
    } catch (PDOException $e) {
        $errors[] = "Error updating {$email}: " . $e->getMessage();
        echo "[ERROR] Row {$totalRows}: {$email} - " . $e->getMessage() . "\n";
    }
}

fclose($handle);

// Summary
echo "\n" . str_repeat('=', 80) . "\n";
echo "SUMMARY\n";
echo str_repeat('=', 80) . "\n";
echo "Total rows processed:         {$totalRows}\n";
echo "Successfully updated:         {$updatedCount}\n";
echo "Skipped (no email):           {$skippedNoEmail}\n";
echo "Skipped (no faculty):         {$skippedNoFaculty}\n";
echo "Skipped (invalid faculty ID): {$skippedInvalidFacultyId}\n";
echo "Skipped (user not found):     {$skippedNotFound}\n";
echo "Errors:                       " . count($errors) . "\n";
echo str_repeat('=', 80) . "\n";

if (!empty($errors)) {
    echo "\nERROR DETAILS:\n";
    foreach ($errors as $i => $error) {
        echo ($i + 1) . ". {$error}\n";
    }
}

echo "\n✓ Script completed successfully!\n";
