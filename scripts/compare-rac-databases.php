<?php
/**
 * Compare row counts: remote rac (202.29.52.124) vs local rac (localhost) on win-kc.
 * Run: php scripts/compare-rac-databases.php
 */
$remote = [
    'host' => '202.29.52.124',
    'user' => 'rac',
    'pass' => 'rac@URU@2025',
    'db'   => 'rac',
];
$local = [
    'host' => 'localhost',
    'user' => 'rac',
    'pass' => 'rac@URU@2026',
    'db'   => 'rac',
];

function connect(array $cfg): mysqli
{
    $m = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['db']);
    if ($m->connect_error) {
        fwrite(STDERR, "connect {$cfg['host']}: {$m->connect_error}\n");
        exit(1);
    }
    $m->set_charset('utf8mb4');
    return $m;
}

function tables(mysqli $m): array
{
    $out = [];
    $r = $m->query('SHOW FULL TABLES');
    while ($row = $r->fetch_array()) {
        if (($row[1] ?? '') === 'BASE TABLE') {
            $out[] = $row[0];
        }
    }
    sort($out);
    return $out;
}

function countRows(mysqli $m, string $table): int
{
    $t = $m->real_escape_string($table);
    $r = $m->query("SELECT COUNT(*) c FROM `{$t}`");
    if (!$r) {
        return -1;
    }
    return (int) $r->fetch_assoc()['c'];
}

$remoteDb = connect($remote);
$localDb  = connect($local);

$remoteTables = tables($remoteDb);
$localTables  = tables($localDb);
$allTables    = array_unique(array_merge($remoteTables, $localTables));
sort($allTables);

$rows = [];
$totalRemote = 0;
$totalLocal  = 0;
$mismatch    = 0;
$missingLocal = 0;

foreach ($allTables as $table) {
    $inRemote = in_array($table, $remoteTables, true);
    $inLocal  = in_array($table, $localTables, true);
    $rc = $inRemote ? countRows($remoteDb, $table) : null;
    $lc = $inLocal ? countRows($localDb, $table) : null;
    $ok = ($rc === $lc);
    if (!$ok) {
        $mismatch++;
    }
    if ($inRemote && (!$inLocal || ($lc === 0 && $rc > 0))) {
        $missingLocal++;
    }
    if ($rc !== null) {
        $totalRemote += max(0, $rc);
    }
    if ($lc !== null) {
        $totalLocal += max(0, $lc);
    }
    $rows[] = [
        'table'  => $table,
        'remote' => $rc,
        'local'  => $lc,
        'ok'     => $ok,
    ];
}

echo json_encode([
    'remote_host' => $remote['host'],
    'local_host'  => $local['host'],
    'tables_compared' => count($rows),
    'mismatches' => $mismatch,
    'tables_with_missing_local_data' => $missingLocal,
    'total_rows_remote' => $totalRemote,
    'total_rows_local'  => $totalLocal,
    'match' => ($mismatch === 0),
    'rows'  => $rows,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
