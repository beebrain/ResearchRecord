#!/usr/bin/env php
<?php
/**
 * ONE-TIME migration: backfill RR thai_name/thai_lastname from newScience OAuth logs.
 *
 * Ran on win-kc 2026-07-04 — do NOT re-run on deploy, cron, or login.
 * Ongoing: SSO sends tf_name/tl_name when Portal has them; users without Thai
 * are prompted via auth/complete-thai-name on first login.
 *
 * Scans oauth_login-*.log for [callback_user] JSON (user_summary, user_detail,
 * personInfo) and updates rac.user rows that still lack Thai script in both fields.
 *
 * Usage (win-kc, manual only):
 *   php scripts/backfill-thai-names-from-logs.php --dry-run --pass=...
 *   php scripts/backfill-thai-names-from-logs.php --apply --pass=...
 *
 * Options:
 *   --dry-run | --apply
 *   --log-dir=PATH     newScience writable/logs (default: C:/inetpub/newscience/writable/logs)
 *   --host=HOST        MySQL host (default: localhost)
 *   --user=USER        MySQL user (default: rac)
 *   --pass=PASS        MySQL password (or env RR_MYSQL_PASS)
 *   --database=DB      Database (default: rac)
 *   --email=EMAIL      Process one user only
 */

declare(strict_types=1);

function usage(): void
{
    fwrite(STDERR, <<<TXT
Usage: php backfill-thai-names-from-logs.php [--dry-run|--apply] [options]

  --dry-run              Preview only (default)
  --apply                Run UPDATE on rac.user
  --log-dir=PATH         newScience log directory
  --host=HOST            MySQL host (default: localhost)
  --user=USER            MySQL user (default: rac)
  --pass=PASS            MySQL password
  --database=DB          Database name (default: rac)
  --email=EMAIL          Single user email

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
        'email'    => '',
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
        } elseif (str_starts_with($arg, '--email=')) {
            $opts['email'] = strtolower(trim(substr($arg, 8)));
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

function hasThai(string $s): bool
{
    return (bool) preg_match('/[\x{0E00}-\x{0E7F}]/u', $s);
}

/** @param array<string,mixed> $row */
function userNeedsThaiName(array $row): bool
{
    $first = trim((string) ($row['thai_name'] ?? ''));
    $last  = trim((string) ($row['thai_lastname'] ?? ''));

    if ($first === '' || $last === '') {
        return true;
    }

    if (strcasecmp($first, 'User') === 0 || strcasecmp($last, 'User') === 0) {
        return true;
    }

    return ! hasThai($first) || ! hasThai($last);
}

function trimName(string $s): string
{
    return trim(preg_replace('/\s+/u', ' ', $s) ?? '');
}

/**
 * @param array<string,mixed> $data
 * @return array{0:string,1:string}|null [first, last]
 */
function thaiPairFromPayload(array $data): ?array
{
    $candidates = [];

    $summary = is_array($data['user_summary'] ?? null) ? $data['user_summary'] : [];
    $detail  = is_array($data['user_detail'] ?? null) ? $data['user_detail'] : $data;

    $pairs = [
        [trimName((string) ($summary['tf_name'] ?? '')), trimName((string) ($summary['tl_name'] ?? ''))],
        [trimName((string) ($data['tf_name'] ?? '')), trimName((string) ($data['tl_name'] ?? ''))],
        [trimName((string) ($detail['first_name_th'] ?? '')), trimName((string) ($detail['last_name_th'] ?? ''))],
        [trimName((string) ($detail['tf_name'] ?? '')), trimName((string) ($detail['tl_name'] ?? ''))],
        [trimName((string) ($summary['gf_name'] ?? '')), trimName((string) ($summary['gl_name'] ?? ''))],
        [trimName((string) ($data['gf_name'] ?? '')), trimName((string) ($data['gl_name'] ?? ''))],
        [trimName((string) ($detail['gf_name'] ?? '')), trimName((string) ($detail['gl_name'] ?? ''))],
    ];

    foreach (['personInfo', 'accountInfo'] as $section) {
        $block = $data[$section] ?? $detail[$section] ?? $summary[$section] ?? null;
        if (! is_array($block)) {
            continue;
        }
        $pairs[] = [trimName((string) ($block['tf_name'] ?? $block['first_name_th'] ?? $block['thaiFirstName'] ?? $block['th_name'] ?? $block['thai_name'] ?? '')),
            trimName((string) ($block['tl_name'] ?? $block['last_name_th'] ?? $block['thaiLastName'] ?? $block['thai_lastname'] ?? ''))];
        $pairs[] = [trimName((string) ($block['gf_name'] ?? '')), trimName((string) ($block['gl_name'] ?? ''))];
    }

    foreach ($pairs as [$first, $last]) {
        if ($first !== '' && $last !== '' && hasThai($first) && hasThai($last)) {
            $candidates[] = [$first, $last];
        }
    }

    if ($candidates === []) {
        return null;
    }

    return $candidates[count($candidates) - 1];
}

/**
 * @return array<string, array{email:string,thai_name:string,thai_lastname:string,source_file:string,source_line:int}>
 */
function parseThaiNamesFromLogs(string $logDir): array
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

            $summary = is_array($data['user_summary'] ?? null) ? $data['user_summary'] : [];
            $email   = strtolower(trim((string) ($data['email'] ?? $summary['email'] ?? $data['user_detail']['email'] ?? '')));
            if ($email === '' || ! str_contains($email, '@')) {
                continue;
            }

            $pair = thaiPairFromPayload($data);
            if ($pair === null) {
                continue;
            }

            [$tf, $tl] = $pair;
            $byEmail[$email] = [
                'email'         => $email,
                'thai_name'     => $tf,
                'thai_lastname' => $tl,
                'source_file'   => basename($file),
                'source_line'   => $i + 1,
            ];
        }
    }

    return $byEmail;
}

// --- main ---

$opts   = parseArgs($argv);
$portal = parseThaiNamesFromLogs($opts['log_dir']);

echo 'Parsed Thai names from logs: ' . count($portal) . " unique email(s)\n";

$mysqli = new mysqli($opts['host'], $opts['user'], $opts['pass'], $opts['database']);
if ($mysqli->connect_error) {
    fwrite(STDERR, 'MySQL connect failed: ' . $mysqli->connect_error . "\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');

$sql = 'SELECT email, thai_name, thai_lastname, gf_name, gl_name FROM user';
if ($opts['email'] !== '') {
    $esc = $mysqli->real_escape_string($opts['email']);
    $sql .= " WHERE LOWER(email) = '{$esc}'";
}

$res = $mysqli->query($sql);
if ($res === false) {
    fwrite(STDERR, 'Query failed: ' . $mysqli->error . "\n");
    exit(1);
}

$needsThai = [];
while ($row = $res->fetch_assoc()) {
    if (! userNeedsThaiName($row)) {
        continue;
    }
    $needsThai[] = $row;
}
$res->free();

echo 'RR users needing Thai name: ' . count($needsThai) . "\n";

$toUpdate = [];
$notInLog = [];

foreach ($needsThai as $row) {
    $email = strtolower(trim((string) $row['email']));
    if (! isset($portal[$email])) {
        $notInLog[] = $email;
        continue;
    }
    $p = $portal[$email];
    if ($p['thai_name'] === trim((string) ($row['thai_name'] ?? ''))
        && $p['thai_lastname'] === trim((string) ($row['thai_lastname'] ?? ''))) {
        continue;
    }
    $toUpdate[] = ['row' => $row, 'portal' => $p];
}

if ($toUpdate === []) {
    echo "No updates from log data.\n";
} else {
    echo ($opts['dry_run'] ? "DRY-RUN — would update " : 'Applying updates to ') . count($toUpdate) . " user(s):\n\n";

    $stmt = $mysqli->prepare('UPDATE user SET thai_name = ?, thai_lastname = ? WHERE email = ?');
    if ($stmt === false) {
        fwrite(STDERR, 'Prepare failed: ' . $mysqli->error . "\n");
        exit(1);
    }

    $applied = 0;
    foreach ($toUpdate as $item) {
        $row   = $item['row'];
        $p     = $item['portal'];
        $email = $row['email'];

        echo "  {$email}\n";
        echo "    log: {$p['source_file']}:{$p['source_line']}\n";
        echo '    before: thai_name=' . ($row['thai_name'] ?? '') . ' thai_lastname=' . ($row['thai_lastname'] ?? '') . "\n";
        echo "    after:  thai_name={$p['thai_name']} thai_lastname={$p['thai_lastname']}\n\n";

        if (! $opts['dry_run']) {
            $stmt->bind_param('sss', $p['thai_name'], $p['thai_lastname'], $email);
            if ($stmt->execute()) {
                $applied++;
            } else {
                fwrite(STDERR, "UPDATE failed for {$email}: {$stmt->error}\n");
            }
        }
    }

    $stmt->close();

    if ($opts['dry_run']) {
        echo "Re-run with --apply to write changes.\n";
    } else {
        echo "Done. Updated {$applied} user(s).\n";
    }
}

if ($notInLog !== []) {
    sort($notInLog);
    echo "\nNo Thai name found in logs for " . count($notInLog) . " user(s) still missing Thai:\n";
    foreach (array_slice($notInLog, 0, 30) as $email) {
        echo "  - {$email}\n";
    }
    if (count($notInLog) > 30) {
        echo '  ... and ' . (count($notInLog) - 30) . " more\n";
    }
}

$mysqli->close();
