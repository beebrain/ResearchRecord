<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\NewsciencePortal;
use Config\UruPortalOAuth;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // Check if user is logged in
        if (!$session->get('logged_in')) {
            $oauth = config(UruPortalOAuth::class);
            if (! $oauth->enabled) {
                // ข้าม /auth/login (ลด redirect วน) — ไป NS โดยตรง
                return redirect()->to(config(NewsciencePortal::class)->researchRecordLoginUrl());
            }

            return redirect()->to(site_url('auth/login'));
        }

        $email = (string) $session->get('user_email');
        if ($email !== '') {
            $user = (new UserModel())->find($email);
            if (! is_array($user) || (int) ($user['active'] ?? 0) !== 1) {
                $session->destroy();

                return redirect()->to(site_url('auth/login'))->with(
                    'error',
                    'บัญชีถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ'
                );
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
