<?php

declare(strict_types=1);

if (! function_exists('publication_resolve_ref_url')) {
    /**
     * Canonical link for sync/display: hidden ref_url (upload) or visible url field.
     */
    function publication_resolve_ref_url(?string $refUrl, ?string $url): ?string
    {
        $refUrl = trim((string) $refUrl);
        if ($refUrl !== '') {
            return $refUrl;
        }

        $url = trim((string) $url);

        return $url !== '' ? $url : null;
    }
}

if (! function_exists('publication_sync_ref_url')) {
    /**
     * @param array<string,mixed> $pub publications row or sync payload
     */
    function publication_sync_ref_url(array $pub): ?string
    {
        return publication_resolve_ref_url(
            isset($pub['ref_url']) ? (string) $pub['ref_url'] : null,
            isset($pub['url']) ? (string) $pub['url'] : null
        );
    }
}
