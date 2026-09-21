#!/usr/bin/env php
<?php

/**
 * Local verification — multidisciplinary flag + coordinator 1+1 quota.
 * Usage: php scripts/verify-multidisciplinary-rules.php
 */

declare(strict_types=1);

use App\Models\CurriculumModel;
use App\Models\UserModel;
use App\Services\ChairSelectionService;
use CodeIgniter\Boot;
use Config\Database;
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

echo "=== Multidisciplinary / coordinator 1+1 — local verify ===\n\n";

$db = Database::connect();
ok($db->getDatabase() === 'rac', 'connected to rac');
ok($db->fieldExists('is_multidisciplinary', 'curriculum'), 'column is_multidisciplinary exists');

echo "\n1. displayName helper\n";
ok(
    CurriculumModel::displayName(['name' => 'CS', 'is_multidisciplinary' => 0]) === 'CS',
    'regular name unchanged'
);
ok(
    CurriculumModel::displayName(['name' => 'CS', 'is_multidisciplinary' => 1]) === 'CS (พหุสาขา)',
    'multidisciplinary gets (พหุสาขา)'
);

echo "\n2. Coordinator quota matrix (pure)\n";
ok(ChairSelectionService::coordinatorQuotaAllows(false, 0, 0), 'empty → can take regular');
ok(ChairSelectionService::coordinatorQuotaAllows(true, 1, 0), 'has regular → can take multi');
ok(! ChairSelectionService::coordinatorQuotaAllows(false, 1, 0), 'has regular → cannot take 2nd regular');
ok(! ChairSelectionService::coordinatorQuotaAllows(true, 0, 1), 'has multi → cannot take 2nd multi');

echo "\n3. DB seed fixtures (rollback at end)\n";
$db->transStart();

$facultyId = (int) ($db->table('faculties')->select('id')->where('status', 1)->get()->getRowArray()['id'] ?? 0);
ok($facultyId > 0, "active faculty id={$facultyId}");

$teacher = $db->table('user')
    ->select('email')
    ->where('active', 1)
    ->where('user_type', 'TEACHER')
    ->where('email IS NOT NULL')
    ->where('email !=', '')
    ->limit(1)
    ->get()
    ->getRowArray();
$teacherEmail = (string) ($teacher['email'] ?? '');
ok($teacherEmail !== '', "teacher email={$teacherEmail}");

// clear this teacher's coordinator rows for clean quota
$db->table('teacher_curriculum')
    ->where('teacher_email', $teacherEmail)
    ->where('role', 'coordinator')
    ->delete();

$ts = time();
$db->table('curriculum')->insert([
    'faculty_id'           => $facultyId,
    'name'                 => "VERIFY REG {$ts}",
    'code'                 => 'VR' . substr((string) $ts, -4),
    'degree_level'         => 'bachelor',
    'is_multidisciplinary' => 0,
    'status'               => 1,
    'chair_email'          => null,
]);
$regularId = (int) $db->insertID();
$db->table('curriculum')->insert([
    'faculty_id'           => $facultyId,
    'name'                 => "VERIFY MULTI {$ts}",
    'code'                 => 'VM' . substr((string) $ts, -4),
    'degree_level'         => 'master',
    'is_multidisciplinary' => 1,
    'status'               => 1,
    'chair_email'          => null,
]);
$multiId = (int) $db->insertID();
$db->table('curriculum')->insert([
    'faculty_id'           => $facultyId,
    'name'                 => "VERIFY REG2 {$ts}",
    'code'                 => 'V2' . substr((string) $ts, -4),
    'degree_level'         => 'bachelor',
    'is_multidisciplinary' => 0,
    'status'               => 1,
    'chair_email'          => null,
]);
$regular2Id = (int) $db->insertID();
ok($regularId > 0 && $multiId > 0 && $regular2Id > 0, "created fixture curricula {$regularId}/{$multiId}/{$regular2Id}");

$row = $db->table('curriculum')->where('id', $multiId)->get()->getRowArray();
ok(CurriculumModel::displayName($row) === "VERIFY MULTI {$ts} (พหุสาขา)", 'DB row displayName has suffix');

$svc = new ChairSelectionService($db);
$userModel = new UserModel();

echo "\n4. Assign coordinator 1 regular + 1 multi (allowed)\n";
try {
    $userModel->assignTeacherToCurriculum($teacherEmail, $regularId, 'coordinator', true);
    ok(true, 'assign coordinator to regular');
} catch (Throwable $e) {
    ok(false, 'assign coordinator to regular: ' . $e->getMessage());
}

$q = $svc->getCoordinatorAssignmentConflicts($teacherEmail, $multiId);
ok($q['allowed'], 'quota allows multi after one regular');
try {
    $userModel->assignTeacherToCurriculum($teacherEmail, $multiId, 'coordinator', false);
    ok(true, 'assign coordinator to multi');
} catch (Throwable $e) {
    ok(false, 'assign coordinator to multi: ' . $e->getMessage());
}

echo "\n5. Block 2nd regular / 2nd multi\n";
$q2 = $svc->getCoordinatorAssignmentConflicts($teacherEmail, $regular2Id);
ok(! $q2['allowed'], 'block 2nd regular: ' . ($q2['message'] ?: '(no message)'));
$blocked = false;
try {
    $userModel->assignTeacherToCurriculum($teacherEmail, $regular2Id, 'coordinator', false);
} catch (RuntimeException $e) {
    $blocked = true;
    echo "     message: {$e->getMessage()}\n";
}
ok($blocked, 'assignTeacher throws on 2nd regular');

echo "\n6. Instructor may join many curricula\n";
try {
    $userModel->assignTeacherToCurriculum($teacherEmail, $regular2Id, 'instructor', false);
    ok(true, 'instructor on 3rd curriculum OK');
} catch (Throwable $e) {
    ok(false, 'instructor assign failed: ' . $e->getMessage());
}

echo "\n7. Chair is separate from responsible list\n";
$db->table('curriculum')->where('id', $regularId)->update(['chair_email' => $teacherEmail]);
// ensure teacher NOT in teacher_curriculum as member of regular (remove only instructor/coordinator? keep coordinator)
$teachers = $userModel->getCurriculumResponsibleTeachers($regularId, $teacherEmail, 8);
$emails = array_map(
    static fn (array $r): string => strtolower((string) ($r['email'] ?? '')),
    $teachers
);
$asChairOnly = in_array(strtolower($teacherEmail), $emails, true);
// teacher IS coordinator on regularId, so they should appear with role coordinator — not auto-injected as chair
$roles = [];
foreach ($teachers as $t) {
    if (strtolower((string) ($t['email'] ?? '')) === strtolower($teacherEmail)) {
        $roles[] = (string) ($t['role'] ?? '');
    }
}
ok(! in_array('chair', $roles, true), 'responsible list does not inject role=chair');
ok(in_array('coordinator', $roles, true), 'coordinator still listed from teacher_curriculum');

// remove coordinator, keep chair only — should NOT appear in responsible list
$db->table('teacher_curriculum')
    ->where('teacher_email', $teacherEmail)
    ->where('curriculum_id', $regularId)
    ->delete();
$teachers2 = $userModel->getCurriculumResponsibleTeachers($regularId, $teacherEmail, 8);
$emails2 = array_map(
    static fn (array $r): string => strtolower((string) ($r['email'] ?? '')),
    $teachers2
);
ok(! in_array(strtolower($teacherEmail), $emails2, true), 'chair-only person not in responsible list');

// Chair conflict still only warns about other chairs (not coordinator quota)
$chairConflict = $svc->getChairSelectionConflicts($teacherEmail, $regular2Id);
ok(is_array($chairConflict['warnings']), 'chair conflict returns warnings array');
$coordWarnings = array_filter(
    $chairConflict['warnings'],
    static fn (string $w): bool => str_contains($w, 'ผู้รับผิดชอบ')
);
ok($coordWarnings === [], 'chair conflict does not treat coordinator as hard chair conflict');

echo "\nRolling back fixture transaction...\n";
$db->transRollback();

// Hard cleanup in case model writes used a separate connection/autocommit
$db->table('teacher_curriculum')->where('teacher_email', $teacherEmail)->whereIn('curriculum_id', [$regularId, $multiId, $regular2Id])->delete();
$db->table('curriculum')->whereIn('id', [$regularId, $multiId, $regular2Id])->delete();
$db->table('curriculum')->like('name', 'VERIFY REG', 'after')->delete();
$db->table('curriculum')->like('name', 'VERIFY MULTI', 'after')->delete();

echo "\n=== Result: {$passed} passed, {$failed} failed ===\n";
exit($failed > 0 ? 1 : 0);
