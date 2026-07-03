#!/usr/bin/env php
<?php
/**
 * Backfill RR user names from newScience OAuth logs.
 *
 * Targets rac.user rows created via SSO with placeholder name (gf_name = 'User')
 * and restores gf_name, gl_name, thai_name, thai_lastname, login_uid from
 * newScience oauth_login logs ([callback_user] lines with Portal user_summary).
 *
 * Usage (win-kc):
 *   php scripts/backfill-sso-user-names-from-ns-logs.php --dry-run
 *   php scripts/backfill-sso-user-names-from-ns-logs.php
 *
 * Options:
 *   --dry-run              Show updates only (default)
 *   --apply                Execute UPDATE statements
 *   --log-dir=PATH         newScience writable/logs (default: C:/inetpub/newscience/writable/logs)
 *   --host=HOST            MySQL host (default: localhost)
 *   --user=USER            MySQL user (default: rac)
 *   --pass=PASS            MySQL password (or env RR_MYSQL_PASS)
 *   --database=DB          Database (default: rac)
 */

declare(strict_types=1);

function usage(): void
{
    fwrite(STDERR, <<<TXT
Usage: php backfill-sso-user-names-from-ns-logs.php [--dry-run|--apply] [options]

  --dry-run              Preview only (default)
  --apply                Run UPDATE on rac.user
  --log-dir=PATH         newScience log directory
  --host=HOST            MySQL host (default: localhost)
  --user=USER            MySQL user (default: rac)
  --pass=PASS            MySQL password
  --database=DB          Database name (default: rac)

TXT);
    exit(2);
}

function parseArgs(array $argv): array
{
    $opts = [
        'dry_run'  => true,
        'log_dir'  => 'C:/inetpub/newscience/writable/logs',
        'host'     => 'localhost',
        'user'     => 'rac',
        'pass'     => getenv('RR_MYSQL_PASS') ?: '',
        'database' => 'rac',
    ];

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--dry-run') {
            $opts['dry_run'] = true;
        } elseif ($arg === '--apply') {
            $opts['dry_run'] = false;
        } elseif (str_starts_with($arg, '--log-dir=')) {
            $opts['log_dir'] = substr($arg, 10);
        } elseif (str_starts_with($arg, '--host=')) {
            $opts['host'] = substr($arg, 7);
        } elseif (str_starts_with($arg, '--user=')) {
            $opts['user'] = substr($arg, 7);
        } elseif (str_starts_with($arg, '--pass=')) {
            $opts['pass'] = substr($arg, 7);
        } elseif (str_starts_with($arg, '--database=')) {
            $opts['database'] = substr($arg, 11);
        } elseif ($arg === '--help' || $arg === '-h') {
            usage();
        } else {
            fwrite(STDERR, "Unknown option: {$arg}\n");
            usage();
        }
    }

    if ($opts['pass'] === '') {
        fwrite(STDERR, "Set --pass=... or env RR_MYSQL_PASS\n");
        exit(2);
    }

    return $opts;
}

/** @return array<string, array{email:string,login_uid:string,gf_name:string,gl_name:string,source_file:string,source_line:int}> */
function parsePortalNamesFromLogs(string $logDir): array
{
    if (! is_dir($logDir)) {
        fwrite(STDERR, "Log directory not found: {$logDir}\n");
        exit(1);
    }

    $files = glob(rtrim(str_replace('\\', '/', $logDir), '/') . '/oauth_login-*.log');
    if ($files === false || $files === []) {
        fwrite(STDERR, "No oauth_login-*.log in {$logDir}\n");
        exit(1);
    }

    sort($files);
    $byEmail = [];

    foreach ($files as $file) {
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            continue;
        }
        foreach ($lines as $i => $line) {
            if (! str_contains($line, '[callback_user]')) {
                continue;
            }
            if (! preg_match('/\|\s*(\{.*\})\s*$/u', $line, $m)) {
                continue;
            }
            $data = json_decode($m[1], true);
            if (! is_array($data)) {
                continue;
            }

            $summary = $data['user_summary'] ?? $data;
            $email   = strtolower(trim((string) ($summary['email'] ?? $data['email'] ?? '')));
            if ($email === '' || ! str_contains($email, '@')) {
                continue;
            }

            $gf = trim((string) ($summary['gf_name'] ?? ''));
            $gl = trim((string) ($summary['gl_name'] ?? ''));
            $tf = trim((string) ($summary['tf_name'] ?? ''));
            $tl = trim((string) ($summary['tl_name'] ?? ''));
            if ($gf === '' && $gl === '') {
                continue;
            }
            if (strcasecmp($gf, 'User') === 0 && ($gl === '' || strcasecmp($gl, 'User') === 0)) {
                continue;
            }

            $byEmail[$email] = [
                'email'       => $email,
                'login_uid'   => trim((string) ($summary['login_uid'] ?? $data['login_uid'] ?? '')),
                'gf_name'     => $gf,
                'gl_name'     => $gl,
                'tf_name'     => $tf,
                'tl_name'     => $tl,
                'source_file' => basename($file),
                'source_line' => $i + 1,
            ];
        }
    }

    return $byEmail;
}

function containsThai(string $s): bool
{
    return (bool) preg_match('/[\x{0E00}-\x{0E7F}]/u', $s);
}

/** @param array<string,mixed> $row */
function buildUpdate(array $row, array $portal): ?array
{
    $email = strtolower(trim((string) ($row['email'] ?? '')));
    if ($email === '' || ! isset($portal[$email])) {
        return null;
    }

    $p = $portal[$email];
    $gf = $p['gf_name'];
    $gl = $p['gl_name'];

    $update = [
        'gf_name' => $gf,
        'gl_name' => $gl,
    ];

    if ($p['login_uid'] !== '' && ($row['login_uid'] ?? '') !== $p['login_uid']) {
        $update['login_uid'] = $p['login_uid'];
    }

    if (containsThai($gf) || containsThai($gl)) {
        $update['thai_name']     = $gf;
        $update['thai_lastname'] = $gl;
    } elseif (containsThai(($p['tf_name'] ?? '') . ($p['tl_name'] ?? ''))) {
        $update['thai_name']     = $p['tf_name'];
        $update['thai_lastname'] = $p['tl_name'];
    } else {
        // English Portal names — keep Thai columns if already set; else mirror English
        if (trim((string) ($row['thai_name'] ?? '')) === '' || strcasecmp((string) $row['thai_name'], 'User') === 0) {
            $update['thai_name'] = $gf;
        }
        if (trim((string) ($row['thai_lastname'] ?? '')) === '') {
            $update['thai_lastname'] = $gl;
        }
    }

    return $update;
}

function isPlaceholderUser(array $row): bool
{
    $gf = trim((string) ($row['gf_name'] ?? ''));
    $th = trim((string) ($row['thai_name'] ?? ''));
    $pc = trim((string) ($row['profile_customer'] ?? ''));

    if (strcasecmp($gf, 'User') === 0 || strcasecmp($th, 'User') === 0) {
        return true;
    }

    return $pc === 'newscience_sso' && ($gf === '' || $th === '');
}

// --- main ---

$opts   = parseArgs($argv);
$portal = parsePortalNamesFromLogs($opts['log_dir']);

echo 'Parsed Portal names from logs: ' . count($portal) . " unique email(s)\n";

$mysqli = new mysqli($opts['host'], $opts['user'], $opts['pass'], $opts['database']);
if ($mysqli->connect_error) {
    fwrite(STDERR, 'MySQL connect failed: ' . $mysqli->connect_error . "\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');

$res = $mysqli->query(
    "SELECT email, login_uid, gf_name, gl_name, thai_name, thai_lastname, profile_customer
     FROM user
     WHERE profile_customer = 'newscience_sso'
        OR gf_name = 'User'
        OR thai_name = 'User'"
);

if ($res === false) {
    fwrite(STDERR, 'Query failed: ' . $mysqli->error . "\n");
    exit(1);
}

$toUpdate = [];
while ($row = $res->fetch_assoc()) {
    if (! isPlaceholderUser($row)) {
        continue;
    }
    $update = buildUpdate($row, $portal);
    if ($update === null) {
        continue;
    }
    $toUpdate[] = ['row' => $row, 'update' => $update, 'portal' => $portal[strtolower($row['email'])]];
}
$res->free();

if ($toUpdate === []) {
    echo "No placeholder SSO users matched log data.\n";
    exit(0);
}

echo ($opts['dry_run'] ? "DRY-RUN — would update " : 'Applying updates to ') . count($toUpdate) . " user(s):\n\n";

$stmt = $mysqli->prepare(
    'UPDATE user SET gf_name=?, gl_name=?, thai_name=?, thai_lastname=?, login_uid=COALESCE(NULLIF(?, \'\'), login_uid)
     WHERE email=?'
);

if ($stmt === false) {
    fwrite(STDERR, 'Prepare failed: ' . $mysqli->error . "\n");
    exit(1);
}

$applied = 0;
foreach ($toUpdate as $item) {
    $row    = $item['row'];
    $u      = $item['update'];
    $src    = $item['portal'];
    $email  = $row['email'];
    $loginUid = $u['login_uid'] ?? ($row['login_uid'] ?? '');

    echo "  {$email}\n";
    echo "    log: {$src['source_file']}:{$src['source_line']}\n";
    echo "    before: gf_name=" . ($row['gf_name'] ?? '') . ' gl_name=' . ($row['gl_name'] ?? '') . ' login_uid=' . ($row['login_uid'] ?? '') . "\n";
    echo "    after:  gf_name={$u['gf_name']} gl_name={$u['gl_name']} thai_name=" . ($u['thai_name'] ?? '-') . ' thai_lastname=' . ($u['thai_lastname'] ?? '-') . " login_uid={$loginUid}\n\n";

    if (! $opts['dry_run']) {
        $thaiName = $u['thai_name'] ?? $row['thai_name'] ?? '';
        $thaiLast = $u['thai_lastname'] ?? $row['thai_lastname'] ?? '';
        $stmt->bind_param(
            'ssssss',
            $u['gf_name'],
            $u['gl_name'],
            $thaiName,
            $thaiLast,
            $loginUid,
            $email
        );
        if (! $stmt->execute()) {
            fwrite(STDERR, "UPDATE failed for {$email}: " . $stmt->error . "\n");
            continue;
        }
        $applied++;
    }
}

$stmt->close();
$mysqli->close();

if ($opts['dry_run']) {
    echo "Re-run with --apply to write changes.\n";
} else {
    echo "Done. Updated {$applied} user(s).\n";
}
