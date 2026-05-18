<?php

namespace App\Libraries;

/**
 * ตรวจ HMAC สำหรับ sync API (email|exp) — secret ต้องตรงกับ newScience RESEARCH_SYNC_HMAC_SECRET
 * ถ้าไม่ตั้ง secret ใน .env จะยอมรับเฉพาะ email (พึ่งพา X-API-KEY จาก filter apikey)
 */
class ResearchSyncHmac
{
    public static function normEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * @return array{ok: bool, email?: string, message?: string}
     */
    public static function verify(?string $email, ?string $exp, ?string $sig): array
    {
        $secret = (string) env('RESEARCH_SYNC_HMAC_SECRET', '');
        $norm   = self::normEmail((string) $email);

        if ($norm === '') {
            return ['ok' => false, 'message' => 'EMAIL_REQUIRED'];
        }

        if ($secret === '') {
            return ['ok' => true, 'email' => $norm];
        }

        if ($exp === null || $exp === '' || $sig === null || $sig === '') {
            return ['ok' => false, 'message' => 'SYNC_AUTH_REQUIRED'];
        }

        $expInt = (int) $exp;
        if ($expInt < time()) {
            return ['ok' => false, 'message' => 'SYNC_AUTH_EXPIRED'];
        }

        $expected = hash_hmac('sha256', $norm . '|' . $expInt, $secret);
        if (! hash_equals($expected, (string) $sig)) {
            return ['ok' => false, 'message' => 'SYNC_AUTH_INVALID'];
        }

        return ['ok' => true, 'email' => $norm];
    }
}
