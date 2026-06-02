<?php
declare(strict_types=1);

function loadEnv(string $path): void
{
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (! str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $v = trim($v);
        if ((str_starts_with($v, '"') && str_ends_with($v, '"')) || (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
            $v = substr($v, 1, -1);
        }
        putenv(trim($k) . '=' . $v);
    }
}

function b64urlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

loadEnv('C:/inetpub/newscience/.env');
loadEnv('C:/inetpub/ResearchRecord/.env');

$nsSecret = getenv('researchrecordsso.sharedSecret') ?: getenv('edocsso.sharedSecret') ?: '';
$rrSecret = getenv('newscience_sso.sharedSecret') ?: '';
$base     = getenv('researchrecordsso.baseUrl') ?: 'https://sci.uru.ac.th/recordresearch';
$path     = getenv('researchrecordsso.ssoEntryPath') ?: '/index.php/auth/sso-entry';

echo "ns_secret={$nsSecret} rr_secret={$rrSecret} match=" . ($nsSecret === $rrSecret ? 'yes' : 'NO') . PHP_EOL;

$payload = ['email' => 'verify@test.uru.ac.th', 'name' => 'Verify', 'exp' => time() + 120];
$payloadB64 = b64urlEncode(json_encode($payload));
$sigB64     = b64urlEncode(hash_hmac('sha256', $payloadB64, $nsSecret, true));
$token      = $payloadB64 . '.' . $sigB64;
$url        = rtrim($base, '/') . $path . '?token=' . rawurlencode($token);

echo "url_len=" . strlen($url) . PHP_EOL;

$ctx = stream_context_create(['http' => ['follow_location' => 0, 'ignore_errors' => true]]);
file_get_contents($url, false, $ctx);
foreach ($http_response_header ?? [] as $h) {
    if (stripos($h, 'HTTP/') === 0 || stripos($h, 'location') !== false || stripos($h, 'refresh') !== false) {
        echo $h . PHP_EOL;
    }
}

$logs = glob('C:/inetpub/ResearchRecord/writable/logs/log-*.log');
if ($logs) {
    usort($logs, static fn ($a, $b) => filemtime($b) <=> filemtime($a));
    $lines = file($logs[0]);
    echo '---log---' . PHP_EOL . implode('', array_slice($lines, -3));
}
