<?php

namespace App\Controllers;

use App\Libraries\UserIdentity;
use App\Models\UserModel;
use CodeIgniter\Controller;

/**
 * Development-only login shortcuts (blocked when CI_ENVIRONMENT !== development).
 */
class DevController extends Controller
{
    public function login()
    {
        helper(['url', 'app_redirect']);

        if (ENVIRONMENT !== 'development') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $userModel = new UserModel();
        $email     = UserIdentity::normalizeEmail((string) ($this->request->getGet('email') ?? ''));

        if ($email === '') {
            $email = UserIdentity::normalizeEmail((string) (env('dev.defaultLoginEmail') ?? ''));
        }

        $user = null;
        if ($email !== '') {
            $user = $userModel->where('email', $email)->where('active', 1)->first();
        }

        if (! $user) {
            $user = $userModel->where('active', 1)->where('admin', 1)->first()
                ?? $userModel->where('active', 1)->first();
        }

        if (! $user) {
            return redirect()->to('/auth/login')->with('error', 'Dev login: no active user in database.');
        }

        $asGod = $this->request->getGet('god') === '1';
        UserIdentity::establishLoginSession($user, [
            'login_method' => 'dev',
            'god_mode'     => $asGod,
        ]);

        $target = $this->request->getGet('redirect');
        if (! is_string($target) || $target === '' || ! str_starts_with($target, '/')) {
            $target = $asGod ? 'admin/dashboard' : 'dashboard';
        } else {
            $target = ltrim($target, '/');
        }

        log_message('info', 'Dev login as ' . $user['email'] . ' god=' . ($asGod ? '1' : '0'));

        return redirect()->to(site_url($target))->with('success', 'Dev login: ' . $user['email']);
    }
}
