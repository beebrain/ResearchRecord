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

    public static function isLiveUruEmail(string $email): bool
    {
        return (bool) preg_match('/@live\.uru\.ac\.th$/', self::normalizeEmail($email));
    }

    public static function hasThaiScript(string $text): bool
    {
        return (bool) preg_match('/[\x{0E00}-\x{0E7F}]/u', $text);
    }

    /** Latin legal name (foreign nationals may use English). */
    public static function isValidLatinLegalName(string $text): bool
    {
        $text = trim($text);
        if ($text === '' || mb_strlen($text) > 100 || mb_strlen($text) < 2) {
            return false;
        }

        return (bool) preg_match("/^[A-Za-z][A-Za-z\\s'\\-.]*[A-Za-z\\.]$|^[A-Za-z]{2,}$/u", $text);
    }

    public static function isAcceptableLegalNamePart(string $text): bool
    {
        $text = trim($text);

        return $text !== '' && (self::hasThaiScript($text) || self::isValidLatinLegalName($text));
    }

    public const PROFILE_SSO_NAME_OK = 'newscience_sso_name_ok';

    /**
     * @param array<string,mixed> $userRow
     */
    public static function userHasCompleteThaiName(array $userRow): bool
    {
        $first = trim((string) ($userRow['thai_name'] ?? ''));
        $last  = trim((string) ($userRow['thai_lastname'] ?? ''));

        if ($first === '' || $last === '') {
            return false;
        }

        if (strcasecmp($first, 'User') === 0 || strcasecmp($last, 'User') === 0) {
            return false;
        }

        if (self::hasThaiScript($first) && self::hasThaiScript($last)) {
            return true;
        }

        if (self::isValidLatinLegalName($first) && self::isValidLatinLegalName($last)) {
            $profile = (string) ($userRow['profile_customer'] ?? '');
            if ($profile === self::PROFILE_SSO_NAME_OK) {
                return true;
            }
            // English mirrored from SSO before user confirmed on the form
            if ($profile === 'newscience_sso') {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Redirect logged-in users who lack a confirmed legal name to the completion form.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|null
     */
    public static function redirectIfThaiNameRequired(\CodeIgniter\HTTP\RequestInterface $request)
    {
        $session = session();
        if (! $session->get('logged_in')) {
            return null;
        }

        $path = strtolower($request->getUri()->getPath());
        if (str_contains($path, 'complete-thai-name')
            || str_contains($path, 'auth/logout')
            || str_contains($path, '/logout')) {
            return null;
        }

        $user = self::sessionUser();
        if ($user === null || self::userHasCompleteThaiName($user)) {
            return null;
        }

        if ($session->get('thai_name_return_url') === null) {
            $session->set('thai_name_return_url', (string) current_url());
        }

        return redirect()->to(site_url('auth/complete-thai-name'));
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
