<?php
/**
 * Match Users with Curriculum and Faculty from CSV
 *
 * This script reads match.csv and updates user records with their curriculum and faculty
 * Matches based on thai_name and thai_lastname
 *
 * Can be run via:
 * - Web browser: http://localhost/researchRecord/match_users_curriculum.php
 * - Command line: php match_users_curriculum.php
 */

// Database configuration - Auto-detect environment
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'research.academic.uru.ac.th') !== false) {
    // Production server
    define('DB_HOST', 'localhost');
    define('DB_USER', 'rac');
    define('DB_PASS', 'rac@URU@2025');
    define('DB_NAME', 'rac');
} else {
    // Local development
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'researchrecord');
}
define('CSV_FILE', __DIR__ . '/match.csv');

// Set charset for proper Thai display
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
}

echo "=== User-Curriculum-Faculty Matching Script ===\n\n";

// Check if CSV file exists
if (!file_exists(CSV_FILE)) {
    die("Error: CSV file not found at " . CSV_FILE . "\n");
}

try {
    // Connect to database
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );

    echo "✓ Connected to database: " . DB_NAME . "\n\n";

    // Read and process CSV file
    echo "Reading CSV file...\n";
    $content = file_get_contents(CSV_FILE);

    // Convert from Windows-874 (Thai) to UTF-8 if needed
    if (!mb_check_encoding($content, 'UTF-8')) {
        $converted = @iconv('Windows-874', 'UTF-8//IGNORE', $content);
        if ($converted !== false) {
            $content = $converted;
            echo "✓ Converted encoding from Windows-874 to UTF-8\n";
        }
    }

    // Parse CSV
    $lines = explode("\n", $content);
    $totalLines = count($lines);
    echo "✓ Found " . ($totalLines - 1) . " records in CSV\n\n";

    // Statistics
    $stats = [
        'processed' => 0,
        'user_found' => 0,
        'user_not_found' => 0,
        'updated' => 0,
        'skipped' => 0,
        'faculty_created' => 0,
        'curriculum_created' => 0,
        'errors' => 0
    ];

    // Cache for faculties and curriculums to avoid duplicate queries
    $facultyCache = [];
    $curriculumCache = [];

    // Skip header row
    for ($i = 1; $i < $totalLines; $i++) {
        $line = trim($lines[$i]);
        if (empty($line)) {
            continue;
        }

        $stats['processed']++;

        // Parse CSV line (handle commas in curriculum names)
        $fields = str_getcsv($line, ',');

        if (count($fields) < 4) {
            echo "⚠ Line " . ($i + 1) . ": Invalid format (need 4 columns)\n";
            $stats['errors']++;
            continue;
        }

        $firstName = trim($fields[0]);
        $lastName = trim($fields[1]);
        $curriculumName = trim($fields[2]);
        $facultyName = trim($fields[3]);

        if (empty($firstName) || empty($lastName)) {
            echo "⚠ Line " . ($i + 1) . ": Empty name fields\n";
            $stats['skipped']++;
            continue;
        }

        echo "Processing: {$firstName} {$lastName}";

        try {
            // Find user by thai_name and thai_lastname
            $stmt = $pdo->prepare("
                SELECT uid, thai_name, thai_lastname, email, curriculum_id, faculty_id
                FROM user
                WHERE thai_name = ? AND thai_lastname = ?
                LIMIT 1
            ");
            $stmt->execute([$firstName, $lastName]);
            $user = $stmt->fetch();

            if (!$user) {
                echo " → ❌ User not found\n";
                $stats['user_not_found']++;
                continue;
            }

            $stats['user_found']++;
            echo " → ✓ Found (UID: {$user['uid']}, Email: {$user['email']})";

            // Get or create faculty
            $facultyId = null;
            if (!empty($facultyName)) {
                // Check cache first
                if (isset($facultyCache[$facultyName])) {
                    $facultyId = $facultyCache[$facultyName];
                } else {
                    // Check if faculty exists
                    $stmt = $pdo->prepare("SELECT id FROM faculties WHERE name = ? LIMIT 1");
                    $stmt->execute([$facultyName]);
                    $faculty = $stmt->fetch();

                    if ($faculty) {
                        $facultyId = $faculty['id'];
                    } else {
                        // Create new faculty
                        $stmt = $pdo->prepare("
                            INSERT INTO faculties (name, code, status, created_at)
                            VALUES (?, ?, 1, NOW())
                        ");
                        // Generate a simple code (first 2 characters)
                        $code = mb_substr($facultyName, 0, 2, 'UTF-8');
                        $stmt->execute([$facultyName, $code]);
                        $facultyId = $pdo->lastInsertId();
                        $stats['faculty_created']++;
                        echo " → ➕ Created faculty: {$facultyName}";
                    }

                    $facultyCache[$facultyName] = $facultyId;
                }
            }

            // Get or create curriculum
            $curriculumId = null;
            if (!empty($curriculumName) && $facultyId) {
                // Check cache first
                $cacheKey = $facultyId . '|' . $curriculumName;
                if (isset($curriculumCache[$cacheKey])) {
                    $curriculumId = $curriculumCache[$cacheKey];
                } else {
                    // Check if curriculum exists
                    $stmt = $pdo->prepare("
                        SELECT id FROM curriculum
                        WHERE name = ? AND faculty_id = ?
                        LIMIT 1
                    ");
                    $stmt->execute([$curriculumName, $facultyId]);
                    $curriculum = $stmt->fetch();

                    if ($curriculum) {
                        $curriculumId = $curriculum['id'];
                    } else {
                        // Create new curriculum (default to bachelor level)
                        $stmt = $pdo->prepare("
                            INSERT INTO curriculum (faculty_id, name, code, degree_level, status, created_at)
                            VALUES (?, ?, ?, 'bachelor', 1, NOW())
                        ");
                        // Generate a simple code
                        $code = 'CUR' . str_pad($facultyId, 3, '0', STR_PAD_LEFT);
                        $stmt->execute([$facultyId, $curriculumName, $code]);
                        $curriculumId = $pdo->lastInsertId();
                        $stats['curriculum_created']++;
                        echo " → ➕ Created curriculum: {$curriculumName}";
                    }

                    $curriculumCache[$cacheKey] = $curriculumId;
                }
            }

            // Update user record
            $needsUpdate = false;
            $updateFields = [];

            if ($curriculumId && $user['curriculum_id'] != $curriculumId) {
                $updateFields[] = "curriculum_id = " . intval($curriculumId);
                $needsUpdate = true;
            }

            if ($facultyId && $user['faculty_id'] != $facultyId) {
                $updateFields[] = "faculty_id = " . intval($facultyId);
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                $sql = "UPDATE user SET " . implode(', ', $updateFields) . " WHERE uid = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user['uid']]);

                echo " → ✅ Updated";
                $stats['updated']++;
            } else {
                echo " → ⏭ Already up to date";
                $stats['skipped']++;
            }

            echo "\n";

        } catch (PDOException $e) {
            echo " → ❌ Error: " . $e->getMessage() . "\n";
            $stats['errors']++;
        }

        // Progress indicator every 50 records
        if ($stats['processed'] % 50 == 0) {
            echo "\n--- Progress: {$stats['processed']} records processed ---\n\n";
        }
    }

    // Summary
    echo "\n=== Summary ===\n";
    echo "Total processed:       " . $stats['processed'] . "\n";
    echo "Users found:           " . $stats['user_found'] . "\n";
    echo "Users not found:       " . $stats['user_not_found'] . "\n";
    echo "Users updated:         " . $stats['updated'] . "\n";
    echo "Already up to date:    " . $stats['skipped'] . "\n";
    echo "Faculties created:     " . $stats['faculty_created'] . "\n";
    echo "Curriculums created:   " . $stats['curriculum_created'] . "\n";
    echo "Errors:                " . $stats['errors'] . "\n";
    echo "\n✅ Script completed successfully!\n";

} catch (PDOException $e) {
    die("❌ Database Error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}
?>
