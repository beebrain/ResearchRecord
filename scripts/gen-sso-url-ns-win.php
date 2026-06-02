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
        $_ENV[trim($k)] = $v;
    }
}

function b64urlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

loadEnv('C:/inetpub/newscience/.env');

$secret = getenv('researchrecordsso.sharedSecret') ?: getenv('edocsso.sharedSecret') ?: '';
$base   = getenv('researchrecordsso.baseUrl') ?: 'https://sci.uru.ac.th/recordresearch';
$path   = getenv('researchrecordsso.ssoEntryPath') ?: '/index.php/auth/sso-entry';

echo 'secret=[' . $secret . ']' . PHP_EOL;

$payload = ['email' => 'verify@test.uru.ac.th', 'name' => 'Verify', 'exp' => time() + 120];
$payloadB64 = b64urlEncode(json_encode($payload));
$sigB64     = b64urlEncode(hash_hmac('sha256', $payloadB64, $secret, true));
$token      = $payloadB64 . '.' . $sigB64;
$entry      = rtrim($base, '/') . $path;

echo $entry . '?token=' . rawurlencode($token) . PHP_EOL;
