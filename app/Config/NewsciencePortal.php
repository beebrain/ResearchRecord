<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * URL ของ newScience บน sci.uru.ac.th — ใช้สำหรับ login redirect / SSO hop
 *
 * .env:
 *   newscience.baseUrl = https://sci.uru.ac.th
 */
class NewsciencePortal extends BaseConfig
{
    /** Base URL ไม่มี slash ท้าย */
    public string $baseUrl = 'https://sci.uru.ac.th';

    public function __construct()
    {
        parent::__construct();
        $raw = env('newscience.baseUrl', $this->baseUrl) ?: $this->baseUrl;
        $this->baseUrl = rtrim((string) $raw, '/');
    }

    public function goResearchRecordUrl(): string
    {
        return $this->baseUrl . '/go-research-record';
    }

    /** OAuth ที่ NS — หลัง login ส่งตรงไป RR sso-entry (ไม่วนที่ go-research-record) */
    public function researchRecordLoginUrl(): string
    {
        $secret = env('newscience_sso.sharedSecret', '') ?: '';
        $rrInit = null;
        if ($secret !== '') {
            $payload = json_encode([
                'exp'   => time() + 120,
                'nonce' => bin2hex(random_bytes(8)),
            ]);
            $payloadB64 = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
            $sig = hash_hmac('sha256', $payloadB64, $secret, true);
            $sigB64 = rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');
            $rrInit = $payloadB64 . '.' . $sigB64;
        }

        return $this->baseUrl . '/oauth/login?' . http_build_query([
            'intent' => 'researchrecord',
            'rr_init' => $rrInit,
        ]);
    }
}
