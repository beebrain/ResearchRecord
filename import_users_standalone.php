<?php

/**
 * Standalone User Import Script
 *
 * This script imports users from a CSV file directly into the database
 * without requiring CodeIgniter CLI.
 *
 * Usage:
 * - Run from browser: http://yourserver/import_users_standalone.php
 * - Run from command line: php import_users_standalone.php
 * - Specify CSV file: php import_users_standalone.php path/to/file.csv
 */

// Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'rac');
define('DB_PASS', 'rac@URU@2025');
define('DB_NAME', 'rac');
define('DB_CHARSET', 'utf8mb4');

// Default CSV file path
$csvFilePath = __DIR__ . '/user.csv';

// If running from command line, check for file argument
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $csvFilePath = $argv[1];
}

// Set output type
$isWeb = php_sapi_name() !== 'cli';
if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>User Import</title>';
    echo '<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}';
    echo '.success{color:#4ec9b0;} .error{color:#f48771;} .info{color:#dcdcaa;}</style></head><body>';
    echo '<h2>User Import Script</h2>';
}

function output($message, $type = 'info')
{
    global $isWeb;
    if ($isWeb) {
        $class = $type;
        echo "<div class='$class'>" . htmlspecialchars($message) . "</div>";
        flush();
        ob_flush();
    } else {
        echo $message . PHP_EOL;
    }
}

// Check if file exists
if (!file_exists($csvFilePath)) {
    output("Error: CSV file not found at: $csvFilePath", 'error');
    if ($isWeb) echo '</body></html>';
    exit(1);
}

output("Starting import from: $csvFilePath", 'info');

// Connect to database
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    output("Database connection established", 'success');
} catch (PDOException $e) {
    output("Database connection failed: " . $e->getMessage(), 'error');
    if ($isWeb) echo '</body></html>';
    exit(1);
}

// Read and convert file encoding
$content = file_get_contents($csvFilePath);

// Remove BOM if present
$content = str_replace("\xEF\xBB\xBF", '', $content);

// Convert from Windows-874 (Thai) to UTF-8 if needed
if (!mb_check_encoding($content, 'UTF-8')) {
    output("Converting file encoding from Windows-874 to UTF-8...", 'info');
    $converted = @iconv('Windows-874', 'UTF-8//IGNORE', $content);
    if ($converted !== false) {
        $content = $converted;
        output("Encoding conversion successful", 'success');
    } else {
        // Fallback to Windows-1252
        $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        output("Encoding conversion using Windows-1252 fallback", 'info');
    }
}

// Save to temporary file
$tempFile = tempnam(sys_get_temp_dir(), 'import_');
file_put_contents($tempFile, $content);

// Prepare SQL statement
$insertSQL = "INSERT INTO user (email, thai_name, thai_lastname, gf_name, gl_name, role, active, created_at)
              VALUES (:email, :thai_name, :thai_lastname, :gf_name, :gl_name, :role, :active, :created_at)";
$insertStmt = $pdo->prepare($insertSQL);

$checkSQL = "SELECT uid FROM user WHERE email = :email LIMIT 1";
$checkStmt = $pdo->prepare($checkSQL);

// Open CSV file
$file = new SplFileObject($tempFile);
$file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

// Detect delimiter (comma for CSV, tab for TSV)
$delimiter = (pathinfo($csvFilePath, PATHINFO_EXTENSION) === 'tsv') ? "\t" : ",";
$file->setCsvControl($delimiter);

$inserted = 0;
$skipped = 0;
$line = 0;

output("", 'info');
output("Processing records...", 'info');
output("", 'info');

foreach ($file as $row) {
    if (!is_array($row) || count(array_filter($row, fn($value) => $value !== null && $value !== '')) === 0) {
        continue;
    }

    // Skip header
    if ($line === 0 && (stripos($row[0] ?? '', 'email') !== false || stripos($row[0] ?? '', 'c') === 0)) {
        $line++;
        continue;
    }

    $line++;

    $email = strtolower(trim($row[0] ?? ''));

    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $skipped++;
        continue;
    }

    // Check if email already exists
    try {
        $checkStmt->execute([':email' => $email]);
        if ($checkStmt->fetch()) {
            $skipped++;
            continue;
        }
    } catch (PDOException $e) {
        output("Error checking email {$email}: " . $e->getMessage(), 'error');
        $skipped++;
        continue;
    }

    $thaiName = trim($row[1] ?? '');
    $thaiLast = trim($row[2] ?? '');

    // Only import if we have at least email and name
    if (empty($thaiName)) {
        $skipped++;
        continue;
    }

    // Insert user
    try {
        $insertStmt->execute([
            ':email' => $email,
            ':thai_name' => $thaiName,
            ':thai_lastname' => $thaiLast,
            ':gf_name' => $thaiName,
            ':gl_name' => $thaiLast,
            ':role' => 'user',
            ':active' => 1,
            ':created_at' => date('Y-m-d H:i:s')
        ]);

        $inserted++;
        output("✓ Imported: {$email} - {$thaiName} {$thaiLast}", 'success');
    } catch (PDOException $e) {
        output("✗ Failed to insert {$email}: " . $e->getMessage(), 'error');
        $skipped++;
    }
}

// Clean up temp file
@unlink($tempFile);

output("", 'info');
output("═══════════════════════════════════════", 'info');
output("Import Complete!", 'success');
output("═══════════════════════════════════════", 'info');
output("Total Inserted: {$inserted}", 'success');
output("Total Skipped:  {$skipped}", 'info');
output("═══════════════════════════════════════", 'info');

if ($isWeb) {
    echo '</body></html>';
}

exit(0);
