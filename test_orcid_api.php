<?php
// Test ORCID Public API directly
$orcidId = "0000-0002-1825-0097"; // Example ORCID ID (Josiah Carberry - test account)
$apiUrl = "https://pub.orcid.org/v3.0/" . urlencode($orcidId);

echo "Testing ORCID Public API: $apiUrl\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
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

$data = json_decode($response, true);

// Parse person info
$person = $data['person'] ?? [];
$name = $person['name'] ?? [];
$firstName = $name['given-names']['value'] ?? 'N/A';
$lastName = $name['family-name']['value'] ?? 'N/A';

echo "\n=== Personal Info ===\n";
echo "Name: $firstName $lastName\n";
echo "Biography: " . ($person['biography']['content'] ?? 'N/A') . "\n";

// Parse works
$activitiesSummary = $data['activities-summary'] ?? [];
$worksGroup = $activitiesSummary['works']['group'] ?? [];
echo "\n=== Works (" . count($worksGroup) . " total) ===\n";

foreach (array_slice($worksGroup, 0, 5) as $i => $group) {
    $workSummary = $group['work-summary'][0] ?? [];
    $title = $workSummary['title']['title']['value'] ?? 'N/A';
    $type = $workSummary['type'] ?? 'N/A';
    $journal = $workSummary['journal-title']['value'] ?? 'N/A';
    $year = $workSummary['publication-date']['year']['value'] ?? 'N/A';
    
    echo "\n" . ($i+1) . ". $title\n";
    echo "   Type: $type | Year: $year | Journal: $journal\n";
}

// Parse employments
$employmentsGroup = $activitiesSummary['employments']['affiliation-group'] ?? [];
echo "\n=== Employments (" . count($employmentsGroup) . " total) ===\n";

foreach ($employmentsGroup as $group) {
    $empSummary = $group['summaries'][0]['employment-summary'] ?? [];
    $org = $empSummary['organization']['name'] ?? 'N/A';
    echo "- $org\n";
}

// Parse educations
$educationsGroup = $activitiesSummary['educations']['affiliation-group'] ?? [];
echo "\n=== Educations (" . count($educationsGroup) . " total) ===\n";

foreach ($educationsGroup as $group) {
    $eduSummary = $group['summaries'][0]['education-summary'] ?? [];
    $org = $eduSummary['organization']['name'] ?? 'N/A';
    $degree = $eduSummary['role-title'] ?? 'N/A';
    echo "- $degree at $org\n";
}
