<?php

namespace App\Libraries;

use CodeIgniter\HTTP\RedirectResponse;
use Config\NewsciencePortal;

/**
 * กลับหน้าเดิมหลังบันทึก/ยกเลิกฟอร์มผลงาน — NS (URL เต็ม) หรือภายใน RR (path)
 */
class PublicationReturnNavigation
{
    public const SESSION_KEY = 'publication_return_after';

    public static function applySsoPayload(array $payload): string
    {
        $return = self::validateNsReturnUrl(trim((string) ($payload['return_after_save'] ?? '')));
        if ($return !== null) {
            session()->set(self::SESSION_KEY, $return);
        }

        return self::resolveEntryRedirect($payload['entry_redirect'] ?? null);
    }

    public static function captureInternalReturnFromRequest(): void
    {
        $ret = service('request')->getGet('return');
        if (! is_string($ret) || $ret === '') {
            return;
        }

        $path = self::validateRrInternalReturn($ret);
        if ($path !== null) {
            session()->set(self::SESSION_KEY, $path);
        }
    }

    public static function resolveEntryRedirect(mixed $path): string
    {
        $normalized = self::normalizeRrPath(is_string($path) ? $path : '');
        if ($normalized !== null && self::isAllowedEntryPath($normalized)) {
            return $normalized;
        }

        return '/publications/create';
    }

    public static function redirectAfterSave(string $successMessage = 'บันทึกผลงานสำเร็จ'): RedirectResponse
    {
        $target = session()->get(self::SESSION_KEY);
        session()->remove(self::SESSION_KEY);

        if (is_string($target) && $target !== '') {
            if (self::isAbsoluteUrl($target)) {
                return redirect()->to(self::appendRrSyncIfNs($target));
            }

            return redirect()->to($target)->with('success', $successMessage);
        }

        return redirect()->to(site_url('publications/manage'))->with('success', $successMessage);
    }

    public static function ajaxRedirectUrl(): string
    {
        $target = session()->get(self::SESSION_KEY);
        if (! is_string($target) || $target === '') {
            return site_url('publications/manage');
        }

        if (self::isAbsoluteUrl($target)) {
            return self::appendRrSyncIfNs($target);
        }

        return site_url(ltrim($target, '/'));
    }

    public static function cancelUrl(): string
    {
        $target = session()->get(self::SESSION_KEY);
        if (is_string($target) && $target !== '') {
            if (self::isAbsoluteUrl($target)) {
                return $target;
            }

            return site_url(ltrim($target, '/'));
        }

        return site_url('publications/manage');
    }

    public static function validateNsReturnUrl(string $url): ?string
    {
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $portal      = config(NewsciencePortal::class);
        $allowedHost = parse_url($portal->baseUrl, PHP_URL_HOST);
        if (! is_string($allowedHost) || $allowedHost === '' || strcasecmp((string) $parts['host'], $allowedHost) !== 0) {
            return null;
        }

        $path = (string) ($parts['path'] ?? '');
        if (! self::isAllowedNsPath($path)) {
            return null;
        }

        return $url;
    }

    private static function isAllowedNsPath(string $path): bool
    {
        $path = '/' . ltrim($path, '/');

        return str_contains($path, '/dashboard/profile/cv')
            || str_contains($path, '/admin/');
    }

    private static function validateRrInternalReturn(string $raw): ?string
    {
        $path = self::normalizeRrPath($raw);
        if ($path === null) {
            return null;
        }

        if (preg_match('#^/publications/manage$#', $path)) {
            return $path;
        }
        if (preg_match('#^/dashboard$#', $path)) {
            return $path;
        }
        if (preg_match('#^/publications/edit/(\d+)$#', $path, $m)) {
            return '/publications/edit/' . (int) $m[1];
        }

        return null;
    }

    private static function isAllowedEntryPath(string $path): bool
    {
        if (preg_match('#^/publications/create$#', $path)) {
            return true;
        }
        if (preg_match('#^/publications/manage$#', $path)) {
            return true;
        }

        return (bool) preg_match('#^/publications/edit/(\d+)$#', $path);
    }

    private static function normalizeRrPath(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $parts = parse_url($path);

            return isset($parts['path']) ? self::normalizeRrPath((string) $parts['path']) : null;
        }

        $path = '/' . ltrim($path, '/');
        if (str_starts_with($path, '/index.php/')) {
            $path = substr($path, strlen('/index.php'));
        }

        return $path !== '' ? $path : null;
    }

    private static function isAbsoluteUrl(string $url): bool
    {
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }

    private static function appendRrSyncIfNs(string $url): string
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        if (! self::isAllowedNsPath($path)) {
            return $url;
        }

        if (str_contains($url, 'rr_sync=')) {
            return $url;
        }

        $sep = str_contains($url, '?') ? '&' : '?';

        return $url . $sep . 'rr_sync=1';
    }
}
