<?php

namespace App\Libraries;

/**
 * Email is the canonical user identity (PK on `user.email`).
 * Do not use numeric uid — removed from schema.
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

        return $db->table('user')
            ->where('email', $email)
            ->get()
            ->getRowArray() ?: null;
    }

    public static function sessionEmail(): string
    {
        $session = session();
        $email   = $session->get('user_email');

        if (is_string($email) && $email !== '') {
            return self::normalizeEmail($email);
        }

        $legacy = $session->get('user_id');
        if (is_string($legacy) && str_contains($legacy, '@')) {
            return self::normalizeEmail($legacy);
        }

        $userData = $session->get('user_data');
        if (is_array($userData) && ! empty($userData['email'])) {
            return self::normalizeEmail((string) $userData['email']);
        }

        return '';
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function sessionUser(): ?array
    {
        $email = self::sessionEmail();

        return $email !== '' ? self::resolveUserByEmail($email) : null;
    }

    /**
     * @param array<string,mixed> $userRow
     */
    public static function setSessionUser(array $userRow): void
    {
        $email = self::normalizeEmail((string) ($userRow['email'] ?? ''));
        if ($email === '') {
            return;
        }

        $userRow['email'] = $email;

        session()->set([
            'user_email' => $email,
            'user_id'    => $email,
            'user_data'  => array_merge(session()->get('user_data') ?? [], $userRow, ['email' => $email]),
        ]);
    }

    /**
     * Full login session (OAuth, backdoor impersonation, dev login).
     *
     * @param array<string,mixed> $userRow Row from `user` table
     * @param array{login_method?: string, god_mode?: bool, impersonate?: bool} $options
     */
    public static function establishLoginSession(array $userRow, array $options = []): void
    {
        $email = self::normalizeEmail((string) ($userRow['email'] ?? ''));
        if ($email === '') {
            return;
        }

        $role             = (string) ($userRow['role'] ?? 'user');
        $managedFaculties = $userRow['managed_faculties'] ?? null;
        $loginMethod      = (string) ($options['login_method'] ?? 'dev');

        $sessionData = [
            'email'            => $email,
            'name'             => trim(($userRow['gf_name'] ?? '') . ' ' . ($userRow['gl_name'] ?? '')),
            'thai_name'        => trim(($userRow['thai_name'] ?? '') . ' ' . ($userRow['thai_lastname'] ?? '')),
            'title'            => $userRow['title'] ?? '',
            'major'            => $userRow['major'] ?? '',
            'is_admin'         => $userRow['admin'] ?? 0,
            'edoc'             => $userRow['edoc'] ?? 0,
            'profile_picture'  => $userRow['profile_picture'] ?? '',
            'logged_in'        => true,
            'login_time'       => time(),
            'login_method'     => $loginMethod,
            'role'             => $role,
            'managed_faculties'=> $managedFaculties,
            'faculty_id'       => $userRow['faculty_id'] ?? null,
        ];

        $session = session();
        $session->remove(['god_mode', 'backdoor_session', 'backdoor_admin_auth', 'backdoor_login']);

        $sessionToSet = [
            'user_data'  => $sessionData,
            'logged_in'  => true,
            'user_email' => $email,
            'user_id'    => $email,
            'user_role'  => $role,
        ];

        if (! empty($options['god_mode'])) {
            $sessionToSet['god_mode']             = true;
            $sessionToSet['backdoor_session']     = true;
            $sessionToSet['backdoor_admin_auth']  = true;
        } elseif (in_array($role, ['super_admin', 'faculty_admin'], true)) {
            $sessionToSet['backdoor_admin_auth'] = true;
        }

        if (! empty($options['impersonate'])) {
            $sessionToSet['backdoor_login'] = true;
        }

        $session->set($sessionToSet);
    }
}
