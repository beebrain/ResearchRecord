<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Token auth for GET /api/curriculum-detail-by-name only.
 *
 * Set CURRICULUM_API_TOKEN in .env (no default in code).
 * Send: X-Curriculum-Api-Token: <token>  or  Authorization: Bearer <token>
 */
class CurriculumApiTokenFilter implements FilterInterface
{
    public static function getExpectedToken(): string
    {
        $v = env('CURRICULUM_API_TOKEN');
        if (is_string($v)) {
            $v = trim($v);
            if ($v !== '') {
                return $v;
            }
        }

        return '';
    }

    public static function isConfigured(): bool
    {
        return self::getExpectedToken() !== '';
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        if (strtolower($request->getMethod()) === 'options') {
            return;
        }

        $expected = self::getExpectedToken();

        if ($expected === '') {
            return service('response')
                ->setStatusCode(503)
                ->setJSON([
                    'success' => false,
                    'error'   => 'API_NOT_CONFIGURED',
                    'message' => 'Set CURRICULUM_API_TOKEN in .env on the Research Record server',
                ]);
        }

        $token = trim($request->getHeaderLine('X-Curriculum-Api-Token'));

        if ($token === '') {
            $auth = trim($request->getHeaderLine('Authorization'));
            if (str_starts_with(strtolower($auth), 'bearer ')) {
                $token = trim(substr($auth, 7));
            }
        }

        if ($token === '' || ! hash_equals($expected, $token)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'error'   => 'UNAUTHORIZED',
                    'message' => 'Valid curriculum API token required (X-Curriculum-Api-Token or Authorization: Bearer)',
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
