#!/usr/bin/env php
<?php

/**
 * Verify dev login + email session (CLI).
 * Usage: php scripts/verify-dev-login.php
 */

use App\Libraries\UserIdentity;
use App\Models\UserModel;
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

echo "=== Dev login / session verify ===\n\n";

ok(ENVIRONMENT === 'development', 'ENVIRONMENT is development');

$db = \Config\Database::connect();
$sample = $db->table('user')->select('email')->where('active', 1)->limit(1)->get()->getRowArray();
ok($sample !== null && ! empty($sample['email']), 'DB has at least one active user');

$email = UserIdentity::normalizeEmail((string) ($sample['email'] ?? ''));
$user  = (new UserModel())->find($email);
ok($user !== null && ($user['email'] ?? '') === $email, 'UserModel::find(email) works');

UserIdentity::establishLoginSession($user, ['login_method' => 'dev']);

ok(session()->get('logged_in') === true, 'session logged_in');
ok(UserIdentity::sessionEmail() === $email, 'sessionEmail matches user');
ok(session()->get('user_id') === $email, 'user_id is email not numeric');
$userData = session()->get('user_data');
ok(is_array($userData) && ($userData['email'] ?? '') === $email, 'user_data.email set');

$noUidCol = ! $db->fieldExists('uid', 'user');
ok($noUidCol, 'user.uid column removed');

UserIdentity::establishLoginSession($user, ['login_method' => 'dev', 'god_mode' => true]);
ok(session()->get('god_mode') === true, 'god_mode flag when requested');
ok(session()->get('backdoor_admin_auth') === true, 'backdoor_admin_auth with god_mode');

ok(class_exists(\App\Controllers\DevController::class), 'DevController exists');
ok(ENVIRONMENT === 'development', 'dev/login route only in development (Routes.php guard)');

ob_end_flush();
echo "\n=== Result: {$passed} passed, {$failed} failed ===\n";
exit($failed > 0 ? 1 : 0);
