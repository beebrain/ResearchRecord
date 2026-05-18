<?php
// Direct database test script
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = new mysqli('localhost', 'root', '', 'researchrecord');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$db->set_charset("utf8mb4");

// Find user
$result = $db->query("SELECT * FROM user WHERE email = 'pisit.nak@live.uru.ac.th' LIMIT 1");
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found\n");
}

echo "User: {$user['gf_name']} {$user['gl_name']} (UID: {$user['uid']})\n";
echo "Email: {$user['email']}\n\n";

$userId = $user['uid'];

// Test ORCID ID
$orcidId = "0000-0002-1825-0097";
echo "Testing ORCID ID: $orcidId\n\n";

// Call ORCID API
$apiUrl = "https://pub.orcid.org/v3.0/" . urlencode($orcidId);
echo "API URL: $apiUrl\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";

$rawData = json_decode($response, true);

// Parse works
$activitiesSummary = $rawData['activities-summary'] ?? [];
$worksGroup = $activitiesSummary['works']['group'] ?? [];
echo "Found " . count($worksGroup) . " works from ORCID\n\n";

// Check if author exists
$authorResult = $db->query("SELECT * FROM authors WHERE email = '{$db->real_escape_string($user['email'])}' LIMIT 1");
$author = $authorResult->fetch_assoc();

if (!$author) {
    echo "Author not found, creating...\n";
    $stmt = $db->prepare("INSERT INTO authors (email, user_uid, created_by) VALUES (?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $db->error . "\n");
    }
    $stmt->bind_param("sss", $user['email'], $userId, $userId);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error . "\n");
    }
    $authorId = $db->insert_id;
    echo "Created author ID: $authorId\n";
} else {
    $authorId = $author['id'];
    echo "Using existing author ID: $authorId\n";
}

$authorName = trim(($user['gf_name'] ?? '') . ' ' . ($user['gl_name'] ?? ''));
if (empty($authorName)) {
    $authorName = $user['email'];
}
echo "Author name: $authorName\n\n";

// Process works
$importedCount = 0;
$skippedCount = 0;
$errorCount = 0;

foreach ($worksGroup as $i => $group) {
    $workSummary = $group['work-summary'][0] ?? [];
    if (!$workSummary) continue;
    
    $workTitle = $workSummary['title']['title']['value'] ?? '';
    $workType = $workSummary['type'] ?? '';
    $workJournal = $workSummary['journal-title']['value'] ?? '';
    $workUrl = $workSummary['url']['value'] ?? '';
    $workYear = null;
    if (isset($workSummary['publication-date']['year']['value'])) {
        $workYear = (int)$workSummary['publication-date']['year']['value'];
    }
    
    echo "\n--- Work " . ($i+1) . " ---\n";
    echo "Title: $workTitle\n";
    echo "Type: $workType | Year: $workYear\n";
    
    if (empty($workTitle)) {
        echo "SKIP: No title\n";
        $errorCount++;
        continue;
    }
    
    // Extract DOI
    $doi = '';
    if ($workUrl && preg_match('/10\.\d{4,}\/[^\s]+/', $workUrl, $matches)) {
        $doi = $matches[0];
    }
    
    // Check for existing
    $existingPub = null;
    
    if ($doi) {
        $check = $db->query("SELECT id FROM publications WHERE doi = '{$db->real_escape_string($doi)}' LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $existingPub = $check->fetch_assoc();
        }
    }
    
    if (!$existingPub && $workUrl) {
        $check = $db->query("SELECT id FROM publications WHERE ref_url = '{$db->real_escape_string($workUrl)}' LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $existingPub = $check->fetch_assoc();
        }
    }
    
    if (!$existingPub && $workTitle && $workYear) {
        $check = $db->query("SELECT id FROM publications WHERE title = '{$db->real_escape_string($workTitle)}' AND publication_year = $workYear LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $existingPub = $check->fetch_assoc();
        }
    }
    
    if ($existingPub) {
        echo "SKIP: Already exists (ID: {$existingPub['id']})\n";
        $skippedCount++;
        continue;
    }
    
    // Map type
    $publicationType = 'journal-article';
    if (stripos($workType, 'conference') !== false) {
        $publicationType = 'conference-paper';
    } elseif (stripos($workType, 'book') !== false) {
        $publicationType = 'book';
    }
    
    // Insert publication
    $source = $workJournal ?: '';
    $doimaybe = $doi ?: '';
    $urlmaybe = $workUrl ?: '';
    $notes = 'Imported from ORCID iD: ' . $orcidId;
    
    $sql = "INSERT INTO publications (title, publication_type, source, publication_year, doi, ref_url, created_by, approve, notes, created_at) 
            VALUES ('" . $db->real_escape_string($workTitle) . "', 
                    '" . $db->real_escape_string($publicationType) . "', 
                    '" . $db->real_escape_string($source) . "', 
                    " . ($workYear ? $workYear : 'NULL') . ", 
                    '" . $db->real_escape_string($doimaybe) . "', 
                    '" . $db->real_escape_string($urlmaybe) . "', 
                    '" . $db->real_escape_string($userId) . "', 
                    0, 
                    '" . $db->real_escape_string($notes) . "', 
                    NOW())";
    
    if ($db->query($sql)) {
        $publicationId = $db->insert_id;
        echo "SUCCESS: Created publication ID: $publicationId\n";
        
        // Link author
        $sql2 = "INSERT INTO publication_authors (publication_id, author_id, author_name, author_email, author_order, uid) 
                 VALUES ($publicationId, $authorId, '" . $db->real_escape_string($authorName) . "', 
                         '" . $db->real_escape_string($user['email']) . "', 1, '" . $db->real_escape_string($userId) . "')";
        
        if ($db->query($sql2)) {
            echo "Linked author to publication\n";
        } else {
            echo "Warning: Failed to link author: " . $db->error . "\n";
        }
        
        $importedCount++;
    } else {
        echo "ERROR: " . $db->error . "\n";
        $errorCount++;
    }
}

echo "\n\n=== SUMMARY ===\n";
echo "Imported: $importedCount\n";
echo "Skipped: $skippedCount\n";
echo "Errors: $errorCount\n";

$db->close();
