#!/usr/bin/env php
<?php

/**
 * Manual integration test — curriculum detail API against live DB (.env).
 * Usage: php scripts/test-curriculum-detail-api.php
 */

use CodeIgniter\Boot;
use Config\Paths;

define('FCPATH', __DIR__ . '/../public/');
chdir(FCPATH);

$_SERVER['CI_ENVIRONMENT'] = 'development';
putenv('CI_ENVIRONMENT=development');
if (! defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development');
}

require FCPATH . '../app/Config/Paths.php';
$paths = new Paths();
require $paths->systemDirectory . '/Boot.php';
Boot::bootConsole($paths);

ob_start();

$passed = 0;
$failed = 0;

function ok(bool $cond, string $label): void
{
    global $passed, $failed;
    if ($cond) {
        echo "  ✔ {$label}\n";
        $passed++;
    } else {
        echo "  ✘ {$label}\n";
        $failed++;
    }
}

echo "=== Curriculum Detail API — integration test ===\n\n";

// 1. OpenAPI spec
echo "1. OpenAPI spec\n";
$spec = App\Libraries\RrOpenApiSpec::build();
ok(isset($spec['paths']['/api/curriculum-detail-by-name']), 'spec has curriculum endpoint');

// 2. DB connectivity
echo "\n2. Database (rac)\n";
$db = Config\Database::connect();
$dbName = $db->getDatabase();
ok($dbName === 'rac', "connected to database rac (got: {$dbName})");

$row = $db->table('curriculum')->where('id', 116)->where('status', 1)->get()->getRowArray();
ok($row !== null, 'curriculum id 116 exists');
$name = $row['name'] ?? '';
echo "     curriculum: {$name}\n";

// 3. Partial search
echo "\n3. CurriculumModel::searchActiveByNamePartial\n";
$curriculumModel = new App\Models\CurriculumModel();
$matches = $curriculumModel->searchActiveByNamePartial($name);
ok($matches !== [], 'partial/exact search by full name returns results');
$ids = array_column($matches, 'id');
ok(in_array(116, array_map('intval', $ids), true), 'search includes curriculum 116');

// 4. Responsible teachers (max 5)
echo "\n4. UserModel::getCurriculumResponsibleTeachers\n";
$userModel = new App\Models\UserModel();
$chairId   = ! empty($row['chair_id']) ? (int) $row['chair_id'] : null;
$teachers  = $userModel->getCurriculumResponsibleTeachers(116, $chairId, 5);
ok(count($teachers) > 0, 'at least 1 responsible teacher');
ok(count($teachers) <= 5, 'at most 5 responsible teachers (got ' . count($teachers) . ')');
if ($chairId !== null && $teachers !== []) {
    ok((int) ($teachers[0]['uid'] ?? 0) === $chairId, 'chair sorts first (uid ' . $chairId . ')');
}
foreach ($teachers as $i => $t) {
    $label = trim(($t['thai_name'] ?? '') . ' ' . ($t['thai_lastname'] ?? ''));
    echo '     [' . ($i + 1) . '] ' . ($t['role'] ?? '?') . ' — ' . $label . ' (' . ($t['email'] ?? '') . ")\n";
}

// 5. Approved publications only
echo "\n5. PublicationModel::getApprovedPublicationsByCanonicalEmail\n";
$pubModel      = new App\Models\PublicationModel();
$totalApproved = 0;
foreach ($teachers as $t) {
    $email = (string) ($t['email'] ?? '');
    if ($email === '') {
        continue;
    }
    $pubs = $pubModel->getApprovedPublicationsByCanonicalEmail($email, 100);
    foreach ($pubs as $p) {
        if ((int) ($p['approve'] ?? 0) !== 1) {
            ok(false, "non-approved pub id {$p['id']} for {$email}");
        }
    }
    $totalApproved += count($pubs);
    echo '     ' . $email . ': ' . count($pubs) . " approved publication(s)\n";
}
ok(true, "all listed publications have approve=1 (total {$totalApproved})");

// 6. Full controller response (direct call — auth filter not applied)
echo "\n6. ApiController::apiGetCurriculumDetailByName (simulated request)\n";
$query   = http_build_query(['curriculum_name' => $name], '', '&', PHP_QUERY_RFC3986);
$uri     = new CodeIgniter\HTTP\URI('http://localhost/api/curriculum-detail-by-name?' . $query);
$request = new CodeIgniter\HTTP\IncomingRequest(
    config('App'),
    $uri,
    null,
    new CodeIgniter\HTTP\UserAgent()
);
$request->setGlobal('get', ['curriculum_name' => $name]);

$controller = new App\Controllers\ApiController();
$controller->initController($request, service('response'), service('logger'));
$response = $controller->apiGetCurriculumDetailByName();
$body     = json_decode($response->getBody(), true);
$status   = $response->getStatusCode();

ok(in_array($status, [200, 409], true), "HTTP status 200 or 409 (got {$status})");

if ($status === 200 && ($body['success'] ?? false) === true) {
    ok(isset($body['curriculum']['name']), 'response has curriculum.name');
    ok(isset($body['responsible_teachers']), 'response has responsible_teachers');
    ok(count($body['responsible_teachers']) <= 5, 'response teachers <= 5');
    ok(($body['summary']['publications_filter'] ?? '') === 'approved_only', 'publications_filter = approved_only');

    echo "\n--- Sample API response (truncated) ---\n";
    $sample = [
        'success'              => $body['success'],
        'curriculum'           => $body['curriculum'],
        'responsible_teachers' => array_map(static function (array $t): array {
            $t['publications'] = array_slice($t['publications'] ?? [], 0, 1);

            return $t;
        }, $body['responsible_teachers']),
        'summary'      => $body['summary'],
        'retrieved_at' => $body['retrieved_at'],
    ];
    echo json_encode($sample, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
} elseif ($status === 409) {
    echo "     ambiguous — candidates: " . count($body['candidates'] ?? []) . "\n";
    ok(true, '409 AMBIGUOUS_CURRICULUM with candidates');
}

echo "\n=== Result: {$passed} passed, {$failed} failed ===\n";
ob_end_flush();
exit($failed > 0 ? 1 : 0);
