#!/usr/bin/env php
<?php
/**
 * Copy Thai names from newScience user.tf_name/tl_name → rac.user.thai_name/thai_lastname
 * when NS has Thai script and RR does not (or RR mirrors English only).
 *
 * Usage (win-kc):
 *   php scripts/sync-thai-names-ns-to-rr.php --dry-run
 *   php scripts/sync-thai-names-ns-to-rr.php --apply
 *
 * Options:
 *   --dry-run | --apply
 *   --ns-host=HOST       newScience DB host (default: localhost)
 *   --ns-user=USER       (default: root)
 *   --ns-pass=PASS       (default: env NS_MYSQL_PASS)
 *   --ns-database=DB     (default: newscience)
 *   --rr-host=HOST       RR DB host (default: localhost)
 *   --rr-user=USER       (default: rac)
 *   --rr-pass=PASS       (default: env RR_MYSQL_PASS)
 *   --rr-database=DB     (default: rac)
 *   --email=EMAIL        Sync one user only
 */

declare(strict_types=1);

function usage(): void
{
    fwrite(STDERR, <<<TXT
Usage: php sync-thai-names-ns-to-rr.php [--dry-run|--apply] [--email=...]

TXT);
    exit(2);
}

function hasThai(string $s): bool
{
    return (bool) preg_match('/[\x{0E00}-\x{0E7F}]/u', $s);
}

function parseArgs(array $argv): array
{
    $opts = [
        'dry_run'    => true,
        'ns_host'    => 'localhost',
        'ns_user'    => 'root',
        'ns_pass'    => getenv('NS_MYSQL_PASS') ?: '',
        'ns_database'=> 'newscience',
        'rr_host'    => 'localhost',
        'rr_user'    => 'rac',
        'rr_pass'    => getenv('RR_MYSQL_PASS') ?: '',
        'rr_database'=> 'rac',
        'email'      => '',
    ];

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--dry-run') {
            $opts['dry_run'] = true;
        } elseif ($arg === '--apply') {
            $opts['dry_run'] = false;
        } elseif (str_starts_with($arg, '--ns-host=')) {
            $opts['ns_host'] = substr($arg, 10);
        } elseif (str_starts_with($arg, '--ns-user=')) {
            $opts['ns_user'] = substr($arg, 10);
        } elseif (str_starts_with($arg, '--ns-pass=')) {
            $opts['ns_pass'] = substr($arg, 10);
        } elseif (str_starts_with($arg, '--ns-database=')) {
            $opts['ns_database'] = substr($arg, 14);
        } elseif (str_starts_with($arg, '--rr-host=')) {
            $opts['rr_host'] = substr($arg, 10);
        } elseif (str_starts_with($arg, '--rr-user=')) {
            $opts['rr_user'] = substr($arg, 10);
        } elseif (str_starts_with($arg, '--rr-pass=')) {
            $opts['rr_pass'] = substr($arg, 10);
        } elseif (str_starts_with($arg, '--rr-database=')) {
            $opts['rr_database'] = substr($arg, 14);
        } elseif (str_starts_with($arg, '--email=')) {
            $opts['email'] = strtolower(trim(substr($arg, 8)));
        } elseif ($arg === '--help' || $arg === '-h') {
            usage();
        } else {
            fwrite(STDERR, "Unknown option: {$arg}\n");
            usage();
        }
    }

    if ($opts['ns_pass'] === '' || $opts['rr_pass'] === '') {
        fwrite(STDERR, "Set --ns-pass / --rr-pass or NS_MYSQL_PASS / RR_MYSQL_PASS\n");
        exit(2);
    }

    return $opts;
}

function connect(array $opts, string $which): mysqli
{
    $host = $opts["{$which}_host"];
    $user = $opts["{$which}_user"];
    $pass = $opts["{$which}_pass"];
    $db   = $opts["{$which}_database"];

    $mysqli = new mysqli($host, $user, $pass, $db);
    if ($mysqli->connect_error) {
        fwrite(STDERR, "MySQL ({$which}) connect failed: {$mysqli->connect_error}\n");
        exit(1);
    }
    $mysqli->set_charset('utf8mb4');

    return $mysqli;
}

$opts = parseArgs($argv);
$ns   = connect($opts, 'ns');
$rr   = connect($opts, 'rr');

$sql = 'SELECT email, tf_name, tl_name, gf_name, gl_name FROM user WHERE tf_name IS NOT NULL AND TRIM(tf_name) != \'\'';
if ($opts['email'] !== '') {
    $emailEsc = $ns->real_escape_string($opts['email']);
    $sql .= " AND LOWER(email) = '{$emailEsc}'";
}

$nsRes = $ns->query($sql);
if ($nsRes === false) {
    fwrite(STDERR, 'NS query failed: ' . $ns->error . "\n");
    exit(1);
}

$stmt = $rr->prepare(
    'SELECT thai_name, thai_lastname, gf_name, gl_name FROM user WHERE email = ? LIMIT 1'
);
$upd = $rr->prepare(
    'UPDATE user SET thai_name = ?, thai_lastname = ? WHERE email = ?'
);
if ($stmt === false || $upd === false) {
    fwrite(STDERR, 'RR prepare failed: ' . $rr->error . "\n");
    exit(1);
}

$checked = 0;
$toUpdate = 0;
$applied = 0;

while ($row = $nsRes->fetch_assoc()) {
    $email = strtolower(trim((string) ($row['email'] ?? '')));
    $tf    = trim((string) ($row['tf_name'] ?? ''));
    $tl    = trim((string) ($row['tl_name'] ?? ''));
    if ($email === '' || ! hasThai($tf . $tl)) {
        continue;
    }

    $checked++;
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $rrRes = $stmt->get_result();
    $rrRow = $rrRes ? $rrRes->fetch_assoc() : null;
    if ($rrRes) {
        $rrRes->free();
    }
    if ($rrRow === null) {
        continue;
    }

    $curThai = trim((string) ($rrRow['thai_name'] ?? '') . ' ' . ($rrRow['thai_lastname'] ?? ''));
    if (hasThai($curThai) && $curThai === trim($tf . ' ' . $tl)) {
        continue;
    }
    if (hasThai($curThai) && ! hasThai($tf . $tl)) {
        continue;
    }

    $toUpdate++;
    echo "  {$email}\n";
    echo "    NS:  tf={$tf} tl={$tl}\n";
    echo '    RR:  thai_name=' . ($rrRow['thai_name'] ?? '') . ' thai_lastname=' . ($rrRow['thai_lastname'] ?? '') . "\n";
    echo "    ->   thai_name={$tf} thai_lastname={$tl}\n\n";

    if (! $opts['dry_run']) {
        $upd->bind_param('sss', $tf, $tl, $email);
        if ($upd->execute()) {
            $applied++;
        } else {
            fwrite(STDERR, "UPDATE failed for {$email}: {$upd->error}\n");
        }
    }
}

$nsRes->free();
$stmt->close();
$upd->close();
$ns->close();
$rr->close();

echo 'NS rows with Thai tf_name: ' . $checked . "\n";
echo ($opts['dry_run'] ? 'Would update: ' : 'Updated: ') . ($opts['dry_run'] ? $toUpdate : $applied) . " user(s)\n";
if ($opts['dry_run'] && $toUpdate > 0) {
    echo "Re-run with --apply to write.\n";
}
