<?php

namespace App\Filters;

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

            return redirect()->to('/auth/login');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
