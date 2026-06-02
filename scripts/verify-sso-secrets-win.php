<?php
/**
 * รันบน win-kc: php scripts/verify-sso-secrets-win.php
 * เปรียบเทียบ secret ที่ NS ใช้สร้าง token กับ RR ใช้ตรวจ
 */
declare(strict_types=1);

function loadEnvFile(string $path): array
{
    $out = [];
    if (! is_readable($path)) {
        return $out;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (! str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        if ((str_starts_with($v, '"') && str_ends_with($v, '"')) || (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
            $v = substr($v, 1, -1);
        }
        $out[$k] = $v;
    }

    return $out;
}

function b64urlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64urlDecode(string $data): ?string
{
    $padding = 4 - (strlen($data) % 4);
    if ($padding !== 4) {
        $data .= str_repeat('=', $padding);
    }
    $decoded = base64_decode(strtr($data, '-_', '+/'), true);

    return $decoded !== false ? $decoded : null;
}

$rrEnv = loadEnvFile('C:/inetpub/ResearchRecord/.env');
$nsEnv = loadEnvFile('C:/inetpub/newscience/.env');

$rrSecret = $rrEnv['newscience_sso.sharedSecret'] ?? '(missing)';
$nsSecret = $nsEnv['researchrecordsso.sharedSecret']
    ?? $nsEnv['edocsso.sharedSecret']
    ?? '(missing)';

echo "RR newscience_sso.sharedSecret len=" . strlen($rrSecret) . " value=" . $rrSecret . PHP_EOL;
echo "NS secret len=" . strlen($nsSecret) . " value=" . $nsSecret . PHP_EOL;
echo "match=" . ($rrSecret === $nsSecret ? 'yes' : 'NO') . PHP_EOL;

$payloadB64 = b64urlEncode(json_encode(['email' => 't@t.th', 'name' => 'T', 'exp' => time() + 120]));
$sigNs     = b64urlEncode(hash_hmac('sha256', $payloadB64, $nsSecret, true));
$token     = $payloadB64 . '.' . $sigNs;

$expected = hash_hmac('sha256', $payloadB64, $rrSecret, true);
$got      = b64urlDecode(explode('.', $token, 2)[1]);

echo "cross_verify=" . ($got !== null && hash_equals($expected, $got) ? 'ok' : 'FAIL') . PHP_EOL;
