<?php
// Test ngrok ORCID API and import to database
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
$userId = $user['uid'];

// Test ORCID ID from ngrok example
$orcidId = "0000-0001-8471-4390";
echo "Testing ORCID ID: $orcidId\n";

// Call ngrok ORCID API
$apiUrl = "https://sweetmeal-loamless-wendy.ngrok-free.dev/webhook/sync-orcid?orcid_id=" . urlencode($orcidId);
echo "API URL: $apiUrl\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'ngrok-skip-browser-warning: true'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($curlError) {
    die("Curl Error: $curlError\n");
}

if ($httpCode !== 200) {
    die("API Error: HTTP $httpCode\n");
}

$rawData = json_decode($response, true);
if (!$rawData) {
    die("JSON Error: " . json_last_error_msg() . "\n");
}

// Parse ngrok response
$orcidData = is_array($rawData) && isset($rawData[0]) ? $rawData[0] : $rawData;
$personalInfo = $orcidData['personal_info'] ?? [];
$orcidItems = $orcidData['orcid_items'] ?? [];

echo "Name: " . ($personalInfo['first_name'] ?? '') . " " . ($personalInfo['last_name'] ?? '') . "\n";
echo "Total items: " . count($orcidItems) . "\n";

// Get works
$works = array_filter($orcidItems, fn($item) => ($item['category'] ?? '') === 'work');
echo "Works (publications): " . count($works) . "\n\n";

// Check author
$authorResult = $db->query("SELECT * FROM authors WHERE email = '{$db->real_escape_string($user['email'])}' LIMIT 1");
$author = $authorResult->fetch_assoc();
$authorId = $author ? $author['id'] : null;

if (!$authorId) {
    $db->query("INSERT INTO authors (email, user_uid, created_by) VALUES ('{$db->real_escape_string($user['email'])}', '$userId', '$userId')");
    $authorId = $db->insert_id;
    echo "Created author ID: $authorId\n";
} else {
    echo "Using existing author ID: $authorId\n";
}

$authorName = trim(($user['gf_name'] ?? '') . ' ' . ($user['gl_name'] ?? ''));
if (empty($authorName)) $authorName = $user['email'];

// Import works
$importedCount = 0;
$skippedCount = 0;

foreach ($works as $item) {
    $workTitle = $item['title'] ?? '';
    $workType = $item['type'] ?? '';
    $workJournal = $item['journal'] ?? '';
    $workUrl = $item['url'] ?? '';
    $workYear = isset($item['pub_year']) ? (int)$item['pub_year'] : null;
    
    if (empty($workTitle)) continue;
    
    echo "\n--- $workTitle ---\n";
    echo "Year: $workYear | Type: $workType\n";
    
    // Extract DOI
    $doi = '';
    if ($workUrl && preg_match('/10\.\d{4,}\/[^\s]+/', $workUrl, $matches)) {
        $doi = $matches[0];
    }
    
    // Check existing
    $existCheck = false;
    if ($doi) {
        $check = $db->query("SELECT id FROM publications WHERE doi = '{$db->real_escape_string($doi)}' LIMIT 1");
        if ($check && $check->num_rows > 0) $existCheck = true;
    }
    if (!$existCheck && $workUrl) {
        $check = $db->query("SELECT id FROM publications WHERE ref_url = '{$db->real_escape_string($workUrl)}' LIMIT 1");
        if ($check && $check->num_rows > 0) $existCheck = true;
    }
    if (!$existCheck && $workTitle && $workYear) {
        $check = $db->query("SELECT id FROM publications WHERE title = '{$db->real_escape_string($workTitle)}' AND publication_year = $workYear LIMIT 1");
        if ($check && $check->num_rows > 0) $existCheck = true;
    }
    
    if ($existCheck) {
        echo "SKIP: Already exists\n";
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
    
    // Insert
    $sql = "INSERT INTO publications (title, publication_type, source, publication_year, doi, ref_url, created_by, approve, notes, created_at) 
            VALUES ('" . $db->real_escape_string($workTitle) . "', 
                    '" . $db->real_escape_string($publicationType) . "', 
                    '" . $db->real_escape_string($workJournal) . "', 
                    " . ($workYear ? $workYear : 'NULL') . ", 
                    '" . $db->real_escape_string($doi) . "', 
                    '" . $db->real_escape_string($workUrl) . "', 
                    '" . $db->real_escape_string($userId) . "', 
                    0, 
                    'Imported from ORCID iD: $orcidId', 
                    NOW())";
    
    if ($db->query($sql)) {
        $publicationId = $db->insert_id;
        echo "SUCCESS: Created ID $publicationId\n";
        
        // Link author
        $db->query("INSERT INTO publication_authors (publication_id, author_id, author_name, author_email, author_order, uid) 
                    VALUES ($publicationId, $authorId, '" . $db->real_escape_string($authorName) . "', 
                            '" . $db->real_escape_string($user['email']) . "', 1, '$userId')");
        
        $importedCount++;
    } else {
        echo "ERROR: " . $db->error . "\n";
    }
}

echo "\n\n=== SUMMARY ===\n";
echo "Imported: $importedCount\n";
echo "Skipped: $skippedCount\n";

$db->close();
