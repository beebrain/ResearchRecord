<?php

/**
 * Static asset URL (CSS/JS under public/assets/).
 * baseURL already points at .../public/ — do not prefix with "public/".
 */
function asset_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    if (str_starts_with($path, 'public/')) {
        $path = substr($path, 7);
    }
    if ($path !== '' && ! str_starts_with($path, 'assets/')) {
        $path = 'assets/' . $path;
    }

    return base_url($path);
}

/**
 * pdfmake lives at project root (sibling of public/), not under public/assets.
 */
function pdfmake_url(string $path = ''): string
{
    $publicBase = rtrim(base_url(), '/');
    $appRoot    = preg_replace('#/public$#', '', $publicBase) ?: $publicBase;
    $path       = ltrim($path, '/');

    return $appRoot . '/pdfmake/' . $path;
}

/**
 * CI4 query-string route URL for JS fetch (nginx without mod_rewrite).
 */
function app_api_url(string $route): string
{
    return site_url(ltrim($route, '/'));
}
