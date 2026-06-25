<?php
/**
 * Load key=value pairs from scripts/ftp_rr.env (never committed).
 * Used by deploy/diff PHP scripts — no passwords in repo.
 */
function loadFtpRrEnvFile(?string $path = null): array
{
    $path ??= dirname(__DIR__) . '/scripts/ftp_rr.env';
    if (! is_readable($path)) {
        return [];
    }

    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (! str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\"'");
        if ($key !== '') {
            $out[$key] = $value;
        }
    }

    return $out;
}

function deployEnv(string $key, ?string $default = null): ?string
{
    $v = getenv($key);
    if ($v !== false && $v !== '') {
        return $v;
    }

    static $fileEnv = null;
    if ($fileEnv === null) {
        $fileEnv = loadFtpRrEnvFile();
    }

    $fromFile = $fileEnv[$key] ?? '';
    if ($fromFile !== '') {
        return $fromFile;
    }

    return $default;
}

function requireDeployEnv(string $key, string $hint = ''): string
{
    $v = deployEnv($key);
    if ($v === null || $v === '') {
        $msg = "Missing {$key}. Set env var or add to scripts/ftp_rr.env (copy from ftp_rr.example.env).";
        if ($hint !== '') {
            $msg .= ' ' . $hint;
        }
        fwrite(STDERR, $msg . PHP_EOL);
        exit(2);
    }

    return $v;
}

/** @deprecated alias — use deployEnv() */
function ftpRrEnv(string $key, ?string $default = null): ?string
{
    return deployEnv($key, $default);
}

/** @deprecated alias — use requireDeployEnv() */
function requireFtpRrEnv(string $key, string $hint = ''): string
{
    return requireDeployEnv($key, $hint);
}
