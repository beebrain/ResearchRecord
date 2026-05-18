<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiKeyFilter implements FilterInterface
{
    /**
     * คีย์ที่ใช้ตรวจ X-API-KEY — อ่านจาก .env เท่านั้น (ไม่มีค่า default ในโค้ด):
     * 1) RESEARCH_API_KEY (แนะนำ ให้ตรงกับ newScience)
     * 2) API_KEY (ชื่อเดิมของ RR)
     */
    public static function getExpectedKey(): string
    {
        foreach (['RESEARCH_API_KEY', 'API_KEY'] as $name) {
            $v = env($name);
            if (is_string($v)) {
                $v = trim($v);
                if ($v !== '') {
                    return $v;
                }
            }
        }

        return '';
    }

    public static function isConfigured(): bool
    {
        return self::getExpectedKey() !== '';
    }

    /**
     * Check for API Key in headers
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $expectedKey = self::getExpectedKey();

        if ($expectedKey === '') {
            if (session()->get('logged_in')) {
                return;
            }

            return service('response')
                ->setStatusCode(503)
                ->setJSON([
                    'success' => false,
                    'error'   => 'API_NOT_CONFIGURED',
                    'message' => 'Set RESEARCH_API_KEY or API_KEY in .env on the Research Record server',
                ]);
        }

        $apiKey = trim($request->getHeaderLine('X-API-KEY'));

        if ($apiKey === '' || ! hash_equals($expectedKey, $apiKey)) {
            if (session()->get('logged_in')) {
                return;
            }

            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'error'   => 'UNAUTHORIZED',
                    'message' => 'Valid API Key is required in X-API-KEY header',
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
