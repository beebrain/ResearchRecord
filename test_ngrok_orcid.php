<?php
// Test ngrok ORCID API
$orcidId = "0000-0001-8471-4390";
$apiUrl = "https://sweetmeal-loamless-wendy.ngrok-free.dev/webhook/sync-orcid?orcid_id=" . urlencode($orcidId);

echo "Testing ngrok ORCID API\n";
echo "URL: $apiUrl\n\n";

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
    echo "Curl Error: $curlError\n";
    exit;
}

echo "\n=== RAW RESPONSE (first 2000 chars) ===\n";
echo substr($response, 0, 2000) . "\n";

$data = json_decode($response, true);
if (!$data) {
    echo "\nJSON Error: " . json_last_error_msg() . "\n";
    exit;
}

echo "\n=== PARSED DATA STRUCTURE ===\n";
echo "Top-level keys: " . implode(", ", array_keys($data)) . "\n";

// Check if it's an array with index 0
if (isset($data[0])) {
    echo "Data is array, using index 0\n";
    $data = $data[0];
    echo "Top-level keys: " . implode(", ", array_keys($data)) . "\n";
}

// Personal info
if (isset($data['personal_info'])) {
    echo "\n=== PERSONAL INFO ===\n";
    print_r($data['personal_info']);
}

// ORCID items
if (isset($data['orcid_items'])) {
    $items = $data['orcid_items'];
    echo "\n=== ORCID ITEMS (" . count($items) . " total) ===\n";
    
    // Group by category
    $categories = [];
    foreach ($items as $item) {
        $cat = $item['category'] ?? 'unknown';
        if (!isset($categories[$cat])) {
            $categories[$cat] = [];
        }
        $categories[$cat][] = $item;
    }
    
    foreach ($categories as $cat => $catItems) {
        echo "\n--- Category: $cat (" . count($catItems) . " items) ---\n";
        foreach (array_slice($catItems, 0, 3) as $i => $item) {
            echo "  " . ($i+1) . ". ";
            if (isset($item['title'])) echo "Title: " . $item['title'];
            if (isset($item['organization'])) echo "Org: " . $item['organization'];
            if (isset($item['pub_year'])) echo " (Year: " . $item['pub_year'] . ")";
            echo "\n";
            // Show all keys of first item
            if ($i == 0) {
                echo "     Keys: " . implode(", ", array_keys($item)) . "\n";
            }
        }
    }
}
