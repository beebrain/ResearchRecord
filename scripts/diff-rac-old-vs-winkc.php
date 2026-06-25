<?php
/**
 * Diff row counts + key identity columns: OLD server vs local win-kc sync.
 *
 * Run inside Docker (shared_php):
 *   docker exec shared_php php scripts/diff-rac-old-vs-winkc.php
 *
 * Env (optional):
 *   OLD_HOST, OLD_PORT, OLD_USER, OLD_PASS, OLD_DB
 *   LOCAL_HOST, LOCAL_USER, LOCAL_PASS, LOCAL_DB
 */
$old = [
    'host' => getenv('OLD_HOST') ?: '202.29.52.124',
    'port' => (int) (getenv('OLD_PORT') ?: 3306),
    'user' => getenv('OLD_USER') ?: 'rac',
    'pass' => getenv('OLD_PASS') ?: 'rac@URU@2025',
    'db'   => getenv('OLD_DB') ?: 'rac',
    'label'=> 'OLD (research.academic)',
];
$winkc = [
    'host' => getenv('LOCAL_HOST') ?: 'shared_mysql',
    'port' => (int) (getenv('LOCAL_PORT') ?: 3306),
    'user' => getenv('LOCAL_USER') ?: 'root',
    'pass' => getenv('LOCAL_PASS') ?: 'rootpass',
    'db'   => getenv('LOCAL_DB') ?: 'rac_winkc',
    'label'=> 'win-kc (local sync)',
];

function connect(array $cfg): mysqli
{
    $m = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['db'], $cfg['port']);
    if ($m->connect_error) {
        fwrite(STDERR, "connect {$cfg['label']} {$cfg['host']}:{$cfg['port']}: {$m->connect_error}\n");
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

function columnSet(mysqli $m, string $table, string $column): array
{
    if (!in_array($column, array_column($m->query("SHOW COLUMNS FROM `{$m->real_escape_string($table)}`")->fetch_all(MYSQLI_ASSOC), 'Field'), true)) {
        return [];
    }
    $t = $m->real_escape_string($table);
    $c = $m->real_escape_string($column);
    $r = $m->query("SELECT DISTINCT `{$c}` AS v FROM `{$t}` WHERE `{$c}` IS NOT NULL AND `{$c}` <> '' ORDER BY 1");
    $out = [];
    while ($row = $r->fetch_assoc()) {
        $out[(string) $row['v']] = true;
    }
    return $out;
}

$oldDb   = connect($old);
$winkcDb = connect($winkc);

$oldTables   = tables($oldDb);
$winkcTables = tables($winkcDb);
$allTables   = array_unique(array_merge($oldTables, $winkcTables));
sort($allTables);

$rows = [];
$mismatch = 0;
$onlyOldTotal = 0;
$onlyWinkcTotal = 0;

foreach ($allTables as $table) {
    $inOld   = in_array($table, $oldTables, true);
    $inWinkc = in_array($table, $winkcTables, true);
    $oc = $inOld ? countRows($oldDb, $table) : null;
    $wc = $inWinkc ? countRows($winkcDb, $table) : null;
    $ok = ($oc === $wc);
    if (!$ok) {
        $mismatch++;
    }
    $rows[] = [
        'table'  => $table,
        'old'    => $oc,
        'winkc'  => $wc,
        'diff'   => ($oc !== null && $wc !== null) ? ($wc - $oc) : null,
        'ok'     => $ok,
    ];
}

$identityChecks = [];
foreach ([
    ['table' => 'user', 'column' => 'email'],
    ['table' => 'publications', 'column' => 'id'],
] as $spec) {
    $table  = $spec['table'];
    $column = $spec['column'];
    if (!in_array($table, $oldTables, true) || !in_array($table, $winkcTables, true)) {
        continue;
    }
    $oldSet   = columnSet($oldDb, $table, $column);
    $winkcSet = columnSet($winkcDb, $table, $column);
    $onlyOld   = array_values(array_diff(array_keys($oldSet), array_keys($winkcSet)));
    $onlyWinkc = array_values(array_diff(array_keys($winkcSet), array_keys($oldSet)));
    $onlyOldTotal += count($onlyOld);
    $onlyWinkcTotal += count($onlyWinkc);
    $identityChecks[] = [
        'table'      => $table,
        'column'     => $column,
        'only_old'   => $onlyOld,
        'only_winkc' => $onlyWinkc,
        'only_old_count'   => count($onlyOld),
        'only_winkc_count' => count($onlyWinkc),
    ];
}

echo json_encode([
    'generated_at' => date('c'),
    'old'   => ['host' => $old['host'], 'db' => $old['db'], 'label' => $old['label']],
    'winkc' => ['host' => $winkc['host'], 'db' => $winkc['db'], 'label' => $winkc['label']],
    'tables_compared' => count($rows),
    'mismatches' => $mismatch,
    'match' => ($mismatch === 0),
    'identity_only_old_total'   => $onlyOldTotal,
    'identity_only_winkc_total' => $onlyWinkcTotal,
    'tables' => $rows,
    'identity_checks' => $identityChecks,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
