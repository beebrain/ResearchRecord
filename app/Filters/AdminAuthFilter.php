<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Helpers\RoleHelper;
use App\Libraries\UserIdentity;

class AdminAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // Check god mode first (backdoor god mode grants all permissions)
        // This check must come BEFORE logged_in check to allow backdoor access
        if ($session->get('god_mode') === true || $session->get('backdoor_session') === true) {
            // Ensure logged_in is set for god mode users
            if (!$session->get('logged_in')) {
                $session->set('logged_in', true);
            }
            return; // God mode bypasses all checks
        }

        // Check if user passed through backdoor for admin access
        if ($session->get('backdoor_admin_auth')) {
            // Ensure logged_in is set
            if (!$session->get('logged_in')) {
                $session->set('logged_in', true);
            }
            return; // Backdoor access granted
        }

        // Check if user is logged in
        if (!$session->get('logged_in')) {
            // For AJAX requests, return JSON error
            if ($request->isAJAX()) {
                $response = service('response');
                return $response->setJSON([
                    'success' => false,
                    'message' => 'Unauthorized: Please login first'
                ])->setStatusCode(401);
            }
            $oauth = config(\Config\UruPortalOAuth::class);
            if (! $oauth->enabled) {
                return redirect()->to(config(\Config\NewsciencePortal::class)->researchRecordLoginUrl());
            }

            return redirect()->to('/auth/login');
        }

        // Check if user is admin through normal login
        $userData = $session->get('user_data') ?? [];
        $userRole = $userData['role'] ?? null;
        $isAdmin = $userData['is_admin'] ?? false;
        $adminFlag = $userData['admin'] ?? 0;

        // Allow super_admin, faculty_admin, or users with is_admin flag or admin flag
        if ($userRole === 'super_admin' || $userRole === 'faculty_admin' || $isAdmin || $adminFlag == 1) {
            return; // Admin access granted
        }

        // Check if user is Dean or Chair
        $user = UserIdentity::sessionUser();

        if ($user && (RoleHelper::isDean($user) || RoleHelper::isChair($user))) {
            return; // Dean or Chair access granted
        }

        // No admin access - deny request
        if ($request->isAJAX()) {
            $response = service('response');
            return $response->setJSON([
                'success' => false,
                'message' => 'Forbidden: Admin access required'
            ])->setStatusCode(403);
        }

        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
