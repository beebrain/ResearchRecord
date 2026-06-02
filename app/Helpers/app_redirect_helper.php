<?php

/**
 * Redirect for Docker/nginx without mod_rewrite (index.php?/path).
 */
function app_redirect_to(string $path): \CodeIgniter\HTTP\RedirectResponse
{
    $request = service('request');
    $script  = (string) ($request->getServer('SCRIPT_NAME') ?? '/index.php');
    $host    = (string) ($request->getServer('HTTP_HOST') ?? 'localhost');
    $scheme  = $request->isSecure() ? 'https' : 'http';
    $segment = ltrim($path, '/');

    return redirect()->to($scheme . '://' . $host . $script . '?/' . $segment);
}
