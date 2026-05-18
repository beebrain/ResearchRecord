<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * SSO จาก newScience — รับ token จาก newScience ที่ /auth/sso-entry แล้วสร้าง session โดยไม่ต้อง login ซ้ำ
 * ใช้ Email เป็นตัวระบุตัวตนร่วมกับ newScience และ Edoc
 *
 * ตั้งค่าใน .env (ถ้ามี):
 *   newscience_sso.enabled = "true"
 *   newscience_sso.sharedSecret = "same_as_newScience"
 */
class NewscienceSso extends BaseConfig
{
    /** เปิดใช้ SSO entry จาก newScience หรือไม่ */
    public bool $enabled = true;

    /** Shared secret (ต้องตรงกับ newScience และ Edoc — ใช้ pisit_secret) */
    public string $sharedSecret = 'pisit_secret';

    public function __construct()
    {
        parent::__construct();
        $this->sharedSecret = env('newscience_sso.sharedSecret', $this->sharedSecret) ?: $this->sharedSecret;
        $enabled = env('newscience_sso.enabled', 'true');
        $this->enabled = ($enabled === 'true' || $enabled === '1' || $enabled === true);
    }
}
