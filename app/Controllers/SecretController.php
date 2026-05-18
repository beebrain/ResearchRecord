<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\UserModel;

class SecretController extends Controller
{
    protected $userModel;
    protected $session;
    private $secretKey = 'admin_backdoor_2024'; // Change this to your secret

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->session = session();
    }

    public function searchUsers($key = null)
    {
        if ($key !== $this->secretKey) {
            return $this->response->setJSON(['success' => false]);
        }

        $search = $this->request->getGet('search') ?? '';
        $role = $this->request->getGet('role') ?? '';
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

        if ($role === 'admin') $builder->where('admin', 1);
        if ($role === 'user') $builder->where('admin', 0);
        if ($status === 'oauth') $builder->where('edoc', 1);
        if ($status === 'local') $builder->where('edoc', 0);

        $users = $builder->limit(50)->get()->getResultArray();

        return $this->response->setJSON(['success' => true, 'users' => $users]);
    }

    /**
     * Update the backdoor method to load minimal data initially
     */
    public function backdoor($key = null)
    {
        // Check secret key
        if ($key !== $this->secretKey) {
            log_message('warning', 'Unauthorized access attempt to admin backdoor from IP: ' . $this->request->getIPAddress());
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Set backdoor session flag with god mode
        // IMPORTANT: Set logged_in to allow access to admin pages
        $this->session->set([
            'backdoor_admin_auth' => true,
            'backdoor_auth_time' => time(),
            'god_mode' => true,  // God mode grants all permissions
            'backdoor_session' => true,  // Mark as backdoor session
            'logged_in' => true  // Required for AdminAuthFilter
        ]);

        // Get all users for the existing view
        $users = $this->userModel->getActiveUsers();

        // Get stats for the new sections
        $stats = [
            'total' => count($users),
            'admin' => count(array_filter($users, fn($u) => $u['admin'] == 1)),
            'oauth' => count(array_filter($users, fn($u) => $u['edoc'] == 1)),
            'active' => count(array_filter($users, fn($u) => $u['active'] == 1))
        ];

        $data = [
            'title' => 'Admin User Management Portal',
            'users' => $users,
            'stats' => $stats,
            'secret_key' => $key
        ];

        return view('secret/backdoor', $data);
    }


    public function dashboardAdmin()
    {

        // // Check secret key
        // if ($key !== $this->secretKey) {
        //     log_message('warning', 'Unauthorized access attempt to admin backdoor from IP: ' . $this->request->getIPAddress());
        //     throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        // }

        // // Get all users for the existing view
        // $users = $this->userModel->getActiveUsers();

        // // Get stats for the new sections
        // $stats = [
        //     'total' => count($users),
        //     'admin' => count(array_filter($users, fn($u) => $u['admin'] == 1)),
        //     'oauth' => count(array_filter($users, fn($u) => $u['edoc'] == 1)),
        //     'active' => count(array_filter($users, fn($u) => $u['active'] == 1))
        // ];

        // $data = [
        //     'title' => 'Admin User Management Portal',
        //     'users' => $users,  // Keep this for your existing view
        //     'stats' => $stats,
        //     'secret_key' => $key
        // ];

        return view('dashboard/adminDashboard');
    }
    /**
     * Direct login as any user without authentication
     */
    public function directLogin($key = null, $userId = null)
    {
        // Check secret key
        if ($key !== $this->secretKey) {
            log_message('warning', 'Unauthorized direct login attempt from IP: ' . $this->request->getIPAddress());
            return redirect()->to('/');
        }

        if (!$userId) {
            return redirect()->to("/secret-admin-portal/{$key}")->with('error', 'User ID required');
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            return redirect()->to("/secret-admin-portal/{$key}")->with('error', 'User not found');
        }

        // Determine user role based on database role field or admin status
        $role = 'user';
        if (!empty($user['role'])) {
            $role = $user['role'];
        } elseif (!empty($user['admin']) && $user['admin'] == 1) {
            $role = 'super_admin'; // Default admin users to super_admin
        }

        // Create session for the user
        $userData = [
            'uid' => $user['uid'],
            'email' => $user['email'],
            'name' => trim($user['gf_name'] . ' ' . $user['gl_name']),
            'gf_name' => $user['gf_name'] ?? '',
            'gl_name' => $user['gl_name'] ?? '',
            'thai_name' => $user['thai_name'] ?? '',
            'thai_lastname' => $user['thai_lastname'] ?? '',
            'title' => $user['title'] ?? '',
            'titleThai' => $user['titleThai'] ?? '',
            'major' => $user['major'] ?? '',
            'is_admin' => $user['admin'] ?? 0,
            'role' => $role,  // Add role field for proper authorization checks
            'edoc' => $user['edoc'] ?? 0,
            'logged_in' => true,
            'backdoor_access' => true,
            'faculty_id' => $user['faculty_id'] ?? null
        ];

        // IMPORTANT: Remove god_mode and backdoor_session from the session
        // These may have been set by the backdoor() method when accessing the portal
        // We must explicitly remove them to ensure Login as User doesn't have God Mode
        $this->session->remove(['god_mode', 'backdoor_session', 'backdoor_admin_auth']);

        // Set session with user data - NO God Mode for Login as User
        // This ensures the user gets their actual role permissions
        $sessionData = [
            'user_data' => $userData,
            'user_id' => $userData['uid'],  // Add user_id directly for compatibility
            'logged_in' => true,
            'backdoor_login' => true  // Flag for audit logging only, doesn't grant extra permissions
        ];

        $this->session->set($sessionData);

        // Log the backdoor access
        log_message('info', 'Backdoor access: Logged in as user ' . $userId . ' (' . $user['email'] . ') from IP: ' . $this->request->getIPAddress());

        // Redirect to user dashboard - admin users will have Admin button to access admin section
        return redirect()->to('/dashboard')->with('success', 'Backdoor access granted as: ' . $user['email']);
    }

    /**
     * Quick admin access
     */
    public function quickAdmin($key = null)
    {
        // Check secret key
        if ($key !== $this->secretKey) {
            log_message('warning', 'Unauthorized admin access attempt from IP: ' . $this->request->getIPAddress());
            return redirect()->to('/');
        }

        // Find first admin user or create temporary admin session
        $adminUser = $this->userModel->where('admin', 1)->where('active', 1)->first();

        if (!$adminUser) {
            // Create temporary admin session if no admin exists
            $userData = [
                'uid' => 999999,
                'email' => 'backdoor@admin.local',
                'name' => 'Backdoor Admin',
                'is_admin' => 1,
                'role' => 'super_admin',  // Temporary admin gets super_admin role
                'logged_in' => true,
                'backdoor_access' => true,
                'temporary_admin' => true
            ];
        } else {
            // Determine role for existing admin user
            $role = !empty($adminUser['role']) ? $adminUser['role'] : 'super_admin';

            $userData = [
                'uid' => $adminUser['uid'],
                'email' => $adminUser['email'],
                'name' => trim($adminUser['gf_name'] . ' ' . $adminUser['gl_name']),
                'is_admin' => 1,
                'role' => $role,  // Add role field
                'logged_in' => true,
                'backdoor_access' => true
            ];
        }

        $this->session->set([
            'user_data' => $userData,
            'user_id' => $userData['uid'],  // Add user_id directly for compatibility
            'logged_in' => true,
            'backdoor_session' => true,
            'god_mode' => true  // God mode grants all permissions
        ]);

        log_message('info', 'Backdoor admin access from IP: ' . $this->request->getIPAddress());

        return redirect()->to('/admin/dashboard')->with('success', 'Backdoor admin access granted');
    }

    /**
     * Exit god mode completely - destroy all session and return to login
     */
    public function exitGodMode($key = null)
    {
        // Check secret key
        if ($key !== $this->secretKey) {
            log_message('warning', 'Unauthorized exit attempt from IP: ' . $this->request->getIPAddress());
            return redirect()->to('/');
        }

        // Log the exit
        $userData = $this->session->get('user_data');
        if ($userData) {
            log_message('info', 'God mode exited by user: ' . ($userData['email'] ?? 'unknown') . ' from IP: ' . $this->request->getIPAddress());
        } else {
            log_message('info', 'God mode portal exited from IP: ' . $this->request->getIPAddress());
        }

        // Completely destroy the session
        $this->session->destroy();

        return redirect()->to('/auth/login')->with('success', 'God mode exited. Session destroyed.');
    }

    /**
     * Debug session data
     */
    public function debugSession()
    {
        echo "<h1>CodeIgniter Session Debug</h1>";
        echo "<pre>";
        echo "All Session Data:\n";
        print_r($this->session->get());
        echo "\n\nUser Data:\n";
        print_r($this->session->get('user_data') ?? 'No user_data found');
        echo "\n\nLogged In: " . ($this->session->get('logged_in') ? 'Yes' : 'No');
        echo "\n\nGod Mode: " . ($this->session->get('god_mode') ? 'Yes' : 'No');
        echo "</pre>";
    }
}
