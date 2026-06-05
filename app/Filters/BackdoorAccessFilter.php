<?php

namespace App\Filters;

use App\Helpers\RoleHelper;
use App\Libraries\UserIdentity;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\NewsciencePortal;
use Config\UruPortalOAuth;

/**
 * Gate for SecretController backdoor routes:
 *  1) caller must be logged in via the normal auth flow
 *  2) caller must hold role=super_admin
 * Anything else returns 404 so the route is indistinguishable from
 * a non-existent path.
 */
class BackdoorAccessFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (! $session->get('logged_in')) {
            $oauth = config(UruPortalOAuth::class);
            if (! $oauth->enabled) {
                return redirect()->to(config(NewsciencePortal::class)->researchRecordLoginUrl());
            }
            return redirect()->to(site_url('auth/login'));
        }

        $user = UserIdentity::sessionUser();
        if (! $user || ! RoleHelper::isSuperAdmin($user)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
