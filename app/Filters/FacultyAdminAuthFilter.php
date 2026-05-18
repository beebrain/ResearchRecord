<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Helpers\RoleHelper;

class FacultyAdminAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // Check if user is logged in
        if (!$session->get('logged_in')) {
            return redirect()->to('/login');
        }

        // Check god mode first (backdoor god mode grants all permissions)
        if ($session->get('god_mode') === true) {
            return; // God mode bypasses all checks
        }

        // Check if user passed through backdoor for admin access
        if (!$session->get('backdoor_admin_auth')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Get user data
        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($session->get('user_id'));

        if (!$user) {
            return redirect()->to('/login')->with('error', 'User not found');
        }

        // Check if user is at least faculty admin
        if (!RoleHelper::isFacultyAdmin($user) && !RoleHelper::isSuperAdmin($user)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Store user data in session for quick access
        $session->set('user_role', $user['role']);
        $session->set('user_managed_faculties', $user['managed_faculties'] ?? null);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
