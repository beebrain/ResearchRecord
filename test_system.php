<?php

/**
 * System Test Script
 * ทดสอบระบบ Research Record Management
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== ระบบทดสอบ Research Record Management ===\n\n";

$errors = [];
$warnings = [];
$success = [];

// 1. ตรวจสอบ PHP Version
echo "1. ตรวจสอบ PHP Version...\n";
$minPhpVersion = '8.1';
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    $errors[] = "PHP version must be {$minPhpVersion} or higher. Current: " . PHP_VERSION;
    echo "   ❌ FAILED: PHP version is " . PHP_VERSION . " (required: {$minPhpVersion}+)\n";
} else {
    $success[] = "PHP version: " . PHP_VERSION;
    echo "   ✅ PASSED: PHP version " . PHP_VERSION . "\n";
}

// 2. ตรวจสอบ PHP Extensions
echo "\n2. ตรวจสอบ PHP Extensions...\n";
$requiredExtensions = ['mysqli', 'mbstring', 'intl', 'json', 'curl'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ {$ext}: loaded\n";
        $success[] = "Extension {$ext} is loaded";
    } else {
        $errors[] = "Required extension {$ext} is not loaded";
        echo "   ❌ {$ext}: NOT loaded\n";
    }
}

// 3. ตรวจสอบไฟล์สำคัญ
echo "\n3. ตรวจสอบไฟล์สำคัญ...\n";
$requiredFiles = [
    'index.php',
    'app/Config/Database.php',
    'app/Config/Routes.php',
    'app/Config/Paths.php',
    'composer.json'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "   ✅ {$file}: exists\n";
        $success[] = "File {$file} exists";
    } else {
        $errors[] = "Required file {$file} is missing";
        echo "   ❌ {$file}: NOT found\n";
    }
}

// 4. ตรวจสอบ Database Connection
echo "\n4. ตรวจสอบการเชื่อมต่อฐานข้อมูล...\n";
try {
    // Load environment
    if (file_exists('.env')) {
        $env = parse_ini_file('.env');
    } else {
        $env = parse_ini_file('env');
    }

    $hostname = $env['database.default.hostname'] ?? 'localhost';
    $database = $env['database.default.database'] ?? 'researchrecord';
    $username = $env['database.default.username'] ?? 'root';
    $password = $env['database.default.password'] ?? '';
    $port = $env['database.default.port'] ?? 3306;

    $conn = @new mysqli($hostname, $username, $password, $database, $port);

    if ($conn->connect_error) {
        $errors[] = "Database connection failed: " . $conn->connect_error;
        echo "   ❌ FAILED: " . $conn->connect_error . "\n";
    } else {
        $success[] = "Database connection successful";
        echo "   ✅ PASSED: Connected to database '{$database}'\n";

        // ตรวจสอบตารางสำคัญ
        echo "\n   4.1 ตรวจสอบตารางในฐานข้อมูล...\n";
        $requiredTables = ['user', 'publications', 'authors', 'faculties', 'curriculum'];
        $tables = [];
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }
        }

        foreach ($requiredTables as $table) {
            if (in_array($table, $tables)) {
                // นับจำนวนแถว
                $countResult = $conn->query("SELECT COUNT(*) as count FROM `{$table}`");
                $count = $countResult ? $countResult->fetch_assoc()['count'] : 0;
                echo "      ✅ {$table}: exists ({$count} records)\n";
                $success[] = "Table {$table} exists with {$count} records";
            } else {
                $warnings[] = "Table {$table} not found";
                echo "      ⚠️  {$table}: NOT found\n";
            }
        }

        $conn->close();
    }
} catch (Exception $e) {
    $errors[] = "Database test error: " . $e->getMessage();
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
}

// 5. ตรวจสอบ Composer Dependencies
echo "\n5. ตรวจสอบ Composer Dependencies...\n";
if (file_exists('vendor/autoload.php')) {
    echo "   ✅ vendor/autoload.php: exists\n";
    $success[] = "Composer dependencies installed";

    // ตรวจสอบ CodeIgniter
    if (file_exists('vendor/codeigniter4/framework')) {
        echo "   ✅ CodeIgniter 4: installed\n";
        $success[] = "CodeIgniter 4 framework installed";
    } else {
        $warnings[] = "CodeIgniter 4 framework not found in vendor";
        echo "   ⚠️  CodeIgniter 4: NOT found\n";
    }
} else {
    $warnings[] = "Composer dependencies not installed. Run 'composer install'";
    echo "   ⚠️  vendor/autoload.php: NOT found (run 'composer install')\n";
}

// 6. ตรวจสอบ Permissions
echo "\n6. ตรวจสอบ Permissions...\n";
$writableDirs = [
    'writable/cache',
    'writable/logs',
    'writable/session',
    'writable/uploads'
];

foreach ($writableDirs as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "   ✅ {$dir}: writable\n";
            $success[] = "Directory {$dir} is writable";
        } else {
            $warnings[] = "Directory {$dir} is not writable";
            echo "   ⚠️  {$dir}: NOT writable\n";
        }
    } else {
        $warnings[] = "Directory {$dir} does not exist";
        echo "   ⚠️  {$dir}: NOT found\n";
    }
}

// 7. ตรวจสอบ Syntax Errors ในไฟล์ PHP หลัก
echo "\n7. ตรวจสอบ Syntax Errors...\n";
$phpFiles = [
    'index.php',
    'app/Config/Database.php',
    'app/Config/Routes.php'
];

foreach ($phpFiles as $file) {
    if (file_exists($file)) {
        $output = [];
        $returnVar = 0;
        exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $returnVar);
        if ($returnVar === 0) {
            echo "   ✅ {$file}: syntax OK\n";
            $success[] = "File {$file} has valid syntax";
        } else {
            $errors[] = "Syntax error in {$file}";
            echo "   ❌ {$file}: syntax ERROR\n";
            echo "      " . implode("\n      ", $output) . "\n";
        }
    }
}

// 8. ตรวจสอบ Routes Configuration
echo "\n8. ตรวจสอบ Routes Configuration...\n";
if (file_exists('app/Config/Routes.php')) {
    $routesContent = file_get_contents('app/Config/Routes.php');
    $routeChecks = [
        'AuthenController' => 'Authentication routes',
        'DashboardController' => 'Dashboard routes',
        'PublicationController' => 'Publication routes',
        'AdminController' => 'Admin routes'
    ];

    foreach ($routeChecks as $controller => $description) {
        if (strpos($routesContent, $controller) !== false) {
            echo "   ✅ {$description}: configured\n";
            $success[] = "{$description} configured";
        } else {
            $warnings[] = "{$description} not found in routes";
            echo "   ⚠️  {$description}: NOT found\n";
        }
    }
}

// 9. ตรวจสอบ Web Server (ถ้า XAMPP ทำงานอยู่)
echo "\n9. ตรวจสอบ Web Server...\n";
$baseURL = 'http://localhost/researchRecord/';
$testURLs = [
    '/' => 'Home/Login page',
    '/auth/login' => 'Login page',
    '/dashboard' => 'Dashboard (requires auth)'
];

$curlAvailable = function_exists('curl_init');
if ($curlAvailable) {
    echo "   ✅ cURL extension: available\n";

    foreach ($testURLs as $path => $description) {
        $url = $baseURL . ltrim($path, '/');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $warnings[] = "Cannot connect to {$url}: {$error}";
            echo "   ⚠️  {$description} ({$url}): Connection error\n";
        } elseif ($httpCode >= 200 && $httpCode < 400) {
            echo "   ✅ {$description} ({$url}): HTTP {$httpCode}\n";
            $success[] = "Web server responding for {$description}";
        } elseif ($httpCode == 401 || $httpCode == 403) {
            echo "   ✅ {$description} ({$url}): HTTP {$httpCode} (Auth required - expected)\n";
            $success[] = "Web server responding for {$description} (auth required)";
        } else {
            $warnings[] = "Web server returned HTTP {$httpCode} for {$url}";
            echo "   ⚠️  {$description} ({$url}): HTTP {$httpCode}\n";
        }
    }
} else {
    $warnings[] = "cURL extension not available - cannot test web server";
    echo "   ⚠️  cURL extension: NOT available (skip web server test)\n";
}

// สรุปผลการทดสอบ
echo "\n" . str_repeat("=", 60) . "\n";
echo "สรุปผลการทดสอบ:\n";
echo str_repeat("=", 60) . "\n";
echo "✅ ผ่าน: " . count($success) . " รายการ\n";
echo "⚠️  คำเตือน: " . count($warnings) . " รายการ\n";
echo "❌ ผิดพลาด: " . count($errors) . " รายการ\n\n";

if (count($errors) > 0) {
    echo "รายการที่ผิดพลาด:\n";
    foreach ($errors as $error) {
        echo "  ❌ {$error}\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "คำเตือน:\n";
    foreach ($warnings as $warning) {
        echo "  ⚠️  {$warning}\n";
    }
    echo "\n";
}

if (count($errors) === 0 && count($warnings) === 0) {
    echo "🎉 ระบบพร้อมใช้งาน! ทุกอย่างทำงานได้ปกติ\n";
    exit(0);
} elseif (count($errors) === 0) {
    echo "✅ ระบบพร้อมใช้งาน แต่มีคำเตือนบางอย่าง\n";
    exit(0);
} else {
    echo "❌ พบข้อผิดพลาดที่ต้องแก้ไขก่อนใช้งาน\n";
    exit(1);
}







