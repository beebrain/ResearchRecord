<?php

namespace App\Libraries;

/**
 * Extract Thai / English names from URU Portal OAuth user payload.
 * Deploy to: C:/inetpub/newscience/app/Libraries/PortalPersonNames.php
 */
final class PortalPersonNames
{
    /** @return array{0:string,1:string} [tf_name, tl_name] */
    public static function thaiFirstLast(array $portalUser): array
    {
        $tf = self::scalar($portalUser, ['tf_name', 'first_name_th', 'firstname_th', 'thai_name', 'th_name']);
        $tl = self::scalar($portalUser, ['tl_name', 'last_name_th', 'lastname_th', 'thai_lastname']);

        foreach (['personInfo', 'accountInfo'] as $section) {
            $nested = $portalUser[$section] ?? null;
            if (! is_array($nested)) {
                continue;
            }
            if ($tf === '') {
                $tf = self::scalar($nested, [
                    'thaiFirstName', 'firstname_th', 'first_name_th', 'tf_name', 'th_name', 'thai_name',
                ]);
            }
            if ($tl === '') {
                $tl = self::scalar($nested, [
                    'thaiLastName', 'lastname_th', 'last_name_th', 'tl_name', 'thai_lastname',
                ]);
            }
        }

        return [$tf, $tl];
    }

    public static function hasThai(string $text): bool
    {
        return (bool) preg_match('/[\x{0E00}-\x{0E7F}]/u', $text);
    }

    /**
     * @param  list<string> $keys
     */
    private static function scalar(array $src, array $keys): string
    {
        foreach ($keys as $key) {
            $v = trim((string) ($src[$key] ?? ''));
            if ($v !== '') {
                return $v;
            }
        }

        return '';
    }
}
