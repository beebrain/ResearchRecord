<?php
/**
 * ทดสอบว่า token ที่ NS สร้าง ตรวจผ่าน logic เดียวกับ AuthenController::ssoEntry
 * Usage: php scripts/verify-sso-token.php [secret]
 */
$secret = $argv[1] ?? 'pisit_secret';

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

$payload = [
    'email' => 'test@uru.ac.th',
    'name'  => 'Test',
    'exp'   => time() + 120,
];
$payloadB64 = b64urlEncode(json_encode($payload));
$sigB64     = b64urlEncode(hash_hmac('sha256', $payloadB64, $secret, true));
$token      = $payloadB64 . '.' . $sigB64;

$parts = explode('.', $token, 2);
$expectedSig = hash_hmac('sha256', $parts[0], $secret, true);
$signature   = b64urlDecode($parts[1]);

echo 'secret_len=' . strlen($secret) . PHP_EOL;
echo 'token_len=' . strlen($token) . PHP_EOL;
echo 'sig_ok=' . ($signature !== null && hash_equals($expectedSig, $signature) ? 'yes' : 'no') . PHP_EOL;

// Test with quoted secret (misconfig)
$quoted = '"pisit_secret"';
$sig2 = b64urlEncode(hash_hmac('sha256', $payloadB64, $quoted, true));
$token2 = $payloadB64 . '.' . $sig2;
$parts2 = explode('.', $token2, 2);
$expected2 = hash_hmac('sha256', $parts2[0], $secret, true);
$sigDecoded2 = b64urlDecode($parts2[1]);
echo 'quoted_secret_mismatch=' . (hash_equals($expected2, $sigDecoded2) ? 'no' : 'yes') . PHP_EOL;
