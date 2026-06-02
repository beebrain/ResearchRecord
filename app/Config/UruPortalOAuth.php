<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * URU Portal OAuth 2.0 — ชุดเดียวกับ newScience (sci.uru.ac.th)
 *
 * ลงทะเบียน redirect URI เพิ่มที่ Portal:
 *   https://sci.uru.ac.th/ResearchRecord/index.php/oauth
 *
 * .env:
 *   uruoauth.clientId = sci
 *   uruoauth.clientSecret = ...
 *   uruoauth.callbackUrl = https://sci.uru.ac.th/ResearchRecord/index.php/oauth
 *   uruoauth.enabled = true
 */
class UruPortalOAuth extends BaseConfig
{
    public string $provider = 'uruportal';

    public string $clientId = 'sci';

    public string $clientSecret = '';

    public string $callbackUrl = 'https://sci.uru.ac.th/ResearchRecord/index.php/oauth';

    public string $loginUrl = 'https://uruportal.uru.ac.th/oauth_login';

    public string $tokenUrl = 'https://uruportal.uru.ac.th/oauth/token';

    public string $userInfoUrl = 'https://uruportal.uru.ac.th/me';

    public ?string $checkUrl = null;

    public bool $enabled = true;

    public int $httpTimeout = 15;

    public bool $httpVerifySsl = true;

    public function __construct()
    {
        parent::__construct();

        $raw = strtolower((string) env('uruoauth.provider', 'uruportal'));
        $this->provider = in_array($raw, ['uruportal', 'idportal'], true) ? $raw : 'uruportal';

        $endpoints = self::defaultEndpoints($this->provider);

        $this->clientId     = $this->readGlobalCredential('clientId', $this->clientId);
        $this->clientSecret = $this->readGlobalCredential('clientSecret', $this->clientSecret);
        $this->callbackUrl  = $this->readString('callbackUrl', 'uruoauth.callbackUrl', $this->callbackUrl);
        $this->loginUrl     = $this->readString('loginUrl', 'uruoauth.loginUrl', $endpoints['loginUrl']);
        $this->tokenUrl     = $this->readString('tokenUrl', 'uruoauth.tokenUrl', $endpoints['tokenUrl']);
        $this->userInfoUrl  = $this->readString('userInfoUrl', 'uruoauth.userInfoUrl', $endpoints['userInfoUrl']);
        $this->checkUrl     = $this->readOptionalUrl('checkUrl', 'uruoauth.checkUrl', $endpoints['checkUrl']);

        $enabled       = env('uruoauth.enabled', 'true');
        $this->enabled = ($enabled === 'true' || $enabled === '1' || $enabled === true);

        $verifySsl = env('uruoauth.httpVerifySsl', '');
        $this->httpVerifySsl = ($verifySsl === '' || $verifySsl === null)
            ? true
            : ($verifySsl === 'true' || $verifySsl === '1' || $verifySsl === true);

        if (ENVIRONMENT === 'development') {
            $local = $this->readString('callbackUrlLocal', 'uruoauth.callbackUrlLocal', '');
            if ($local !== '') {
                $this->callbackUrl = $local;
            }
        }
    }

    /** @return array{loginUrl: string, tokenUrl: string, userInfoUrl: string, checkUrl: ?string} */
    private static function defaultEndpoints(string $provider): array
    {
        if ($provider === 'idportal') {
            return [
                'loginUrl'    => 'https://idportal.uru.ac.th/oauth2/authenticate',
                'tokenUrl'    => 'https://idportal.uru.ac.th/oauth2/access_token',
                'userInfoUrl' => 'https://idportal.uru.ac.th/info',
                'checkUrl'    => 'https://idportal.uru.ac.th/check',
            ];
        }

        return [
            'loginUrl'    => 'https://uruportal.uru.ac.th/oauth_login',
            'tokenUrl'    => 'https://uruportal.uru.ac.th/oauth/token',
            'userInfoUrl' => 'https://uruportal.uru.ac.th/me',
            'checkUrl'    => null,
        ];
    }

    private function readString(string $key, ?string $legacyKey, string $default): string
    {
        $specific = env('uruoauth.' . $this->provider . '.' . $key);
        if ($specific !== null && $specific !== false && (string) $specific !== '') {
            return (string) $specific;
        }
        if ($legacyKey !== null) {
            $leg = env($legacyKey);
            if ($leg !== null && $leg !== false && (string) $leg !== '') {
                return (string) $leg;
            }
        }

        return $default;
    }

    private function readGlobalCredential(string $key, string $default): string
    {
        $v = env('uruoauth.' . $key);
        if ($v !== null && $v !== false && (string) $v !== '') {
            return (string) $v;
        }

        return $default;
    }

    private function readOptionalUrl(string $key, ?string $legacyKey, ?string $default): ?string
    {
        $specific = env('uruoauth.' . $this->provider . '.' . $key);
        if ($specific !== null && $specific !== false && (string) $specific !== '') {
            return (string) $specific;
        }
        if ($legacyKey !== null) {
            $leg = env($legacyKey);
            if ($leg !== null && $leg !== false && (string) $leg !== '') {
                return (string) $leg;
            }
        }

        return $default;
    }

    public function buildAuthUrl(string $state = ''): string
    {
        $params = [
            'response_type' => 'code',
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->callbackUrl,
        ];
        if ($state !== '') {
            $params['state'] = $state;
        }

        return $this->loginUrl . '?' . http_build_query($params);
    }
}
