<?php

namespace App\Libraries;

/**
 * Email-first identity helpers.
 *
 * RR still keeps user.uid as the internal surrogate key, but cross-system
 * identity and publication/CV matching should start from normalized email.
 */
final class UserIdentity
{
    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function resolveUserByEmail(string $email): ?array
    {
        $email = self::normalizeEmail($email);
        if ($email === '') {
            return null;
        }

        $db = \Config\Database::connect();
        $row = $db->table('user')
            ->where('LOWER(TRIM(email)) = ' . $db->escape($email), null, false)
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : null;
    }

    public static function resolveUidFromEmail(string $email): ?int
    {
        $user = self::resolveUserByEmail($email);
        if ($user === null || ! isset($user['uid'])) {
            return null;
        }

        return (int) $user['uid'];
    }
}
