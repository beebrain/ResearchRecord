<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\UserModel;
use App\Models\AuthorModel;
use App\Services\OAuthService;
use Config\NewscienceSso;
use Config\NewsciencePortal;

class AuthenController extends Controller
{
    protected $userModel;
    protected $authorModel;
    protected $oauthService;
    protected $session;

    private const SSO_LOG_PREFIX = 'Research Record SSO: ';

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->authorModel = new AuthorModel();
        $this->oauthService = new OAuthService();
        $this->session = session();
        helper(['form', 'url']);
    }

    /**
     * Show login page with OAuth integration
     */
    public function login()
    {
        if ($this->session->has('user_data') || $this->session->get('logged_in')) {
            return redirect()->to(site_url('dashboard'));
        }

        $oauthConfig  = config(\Config\UruPortalOAuth::class);
        $portalConfig = config(NewsciencePortal::class);

        if (! $oauthConfig->enabled) {
            // หลัง logout หรือมี error — แสดงหน้า login ไม่ redirect วนกลับไป SSO ทันที
            $isLogoutLanding = $this->request->getGet('logout') === '1';
            if ($isLogoutLanding || session()->getFlashdata('error') || session()->getFlashdata('success')) {
                return view('auth/login', [
                    'title'    => 'Login - Research Publication Management',
                    'auth_url' => $portalConfig->researchRecordLoginUrl(),
                ]);
            }

            return redirect()->to($portalConfig->researchRecordLoginUrl());
        }

        // Local/dev: OAuth ตรงที่ RR
        $state = bin2hex(random_bytes(16));
        $this->session->set('oauth_state', $state);

        $authUrl = null;
        try {
            $authUrl = $this->oauthService->getAuthUrl($state);
        } catch (\Throwable $e) {
            log_message('error', 'OAuth login URL failed: ' . $e->getMessage());
        }

        $data = [
            'title'    => 'Login - Research Publication Management',
            'auth_url' => $authUrl,
        ];

        return view('auth/login', $data);
    }

    /**
     * Handle OAuth callback and process user data from API
     */
    public function callback()
    {
        try {
            // Check for OAuth errors
            if ($this->request->getGet('error')) {
                throw new \Exception('OAuth Error: ' . $this->request->getGet('error_description', 'Unknown error'));
            }

            // Get authorization code
            $code = $this->request->getGet('code');
            if (!$code) {
                throw new \Exception('Authorization code not found');
            }

            // Verify state parameter for security
            $state = $this->request->getGet('state');
            if (!$state || $state !== $this->session->get('oauth_state')) {
                throw new \Exception('Invalid state parameter');
            }

            log_message('debug', "Processing OAuth callback with code: " . $code);

            // Exchange code for access token
            $tokenInfo = $this->oauthService->getAccessToken($code);

            if (!isset($tokenInfo['access_token'])) {
                throw new \Exception('Failed to obtain access token');
            }

            // Get user information from API
            $userInfo = $this->oauthService->getUserInfo($tokenInfo['access_token']);

            if (!$userInfo || !isset($userInfo['email'])) {
                throw new \Exception('Failed to retrieve user information from API');
            }

            // Process user login/registration with API data
            $userData = $this->processAPIUserLogin($userInfo, $tokenInfo['access_token']);

            // Set session data
            $this->setUserSession($userData, $tokenInfo['access_token']);

            // Remove oauth state
            $this->session->remove('oauth_state');

            log_message('info', 'Successful OAuth login for user: ' . $userData['email']);
            $this->session->set('last_oauth_callback_at', time());
            $redirectTarget = $this->getPostLoginRedirect($userData);
            log_message('debug', sprintf(
                'OAuth callback completed for Email:%s - redirecting to %s',
                $userData['email'] ?? 'unknown',
                $redirectTarget
            ));

            return redirect()->to($redirectTarget)->with('success', 'Login successful! Welcome to Research Publication Management System.');
        } catch (\Exception $e) {
            log_message('error', 'OAuth Login Error: ' . $e->getMessage());
            return redirect()->to(site_url('auth/login'))->with('error', 'Login failed: ' . $e->getMessage());
        }
    }

    /**
     * Process user login with API data and insert/update user records
     */
    private function processAPIUserLogin($userInfo, $accessToken): array
    {
        try {
            $this->userModel->db->transStart();

            $loginUid = $userInfo['code'] ?? uniqid();
            $email = $userInfo['email'];

            // Extract user data from API response
            $apiUserData = [
                'login_uid' => $loginUid,
                'email' => $email,
                'gf_name' => trim($userInfo['first_name_en'] ?? ''),
                'gl_name' => trim($userInfo['last_name_en'] ?? ''),
                'thai_name' => trim($userInfo['first_name_th'] ?? ''),
                'thai_lastname' => trim($userInfo['last_name_th'] ?? ''),
                'title' => trim($userInfo['prefix_en'] ?? ''),
                'titleThai' => trim($userInfo['prefix_th'] ?? ''),
                'major' => trim($userInfo['faculty_name_th'] ?? $userInfo['faculty_name_en'] ?? ''),
                'profile_picture' => $userInfo['picture'] ?? '',
                'profile_customer' => 'oauth_api',
                'active' => 1,
                'edoc' => 1,
                'admin' => 0,
                // Additional API fields
                'faculty_id' => $userInfo['faculty_id'] ?? null,
                'department_id' => $userInfo['department_id'] ?? null,
                'user_type' => $userInfo['type'] ?? null,
                'degree' => $userInfo['degree'] ?? null,
                'gender' => $userInfo['gender'] ?? null,
                'nickname' => trim($userInfo['nickname'] ?? ''),
                'birth_date' => $userInfo['birth_date'] ?? null,
                'nationality' => $userInfo['nationality'] ?? null,
                'citizen_id' => $userInfo['citizen_id'] ?? null,
                'passport_id' => $userInfo['passport_id'] ?? null
            ];

            // Check if user exists by login_uid or email
            $existingUser = $this->userModel->findByLoginUid($loginUid);
            if (!$existingUser) {
                $existingUser = $this->userModel->getUserByEmail($email);
            }

            if ($existingUser) {
                // Update existing user with fresh API data
                // Note: faculty_id, department_id, and major are NOT updated
                $updateData = [
                    'gf_name' => $apiUserData['gf_name'],
                    'gl_name' => $apiUserData['gl_name'],
                    'thai_name' => $apiUserData['thai_name'],
                    'thai_lastname' => $apiUserData['thai_lastname'],
                    'title' => $apiUserData['title'],
                    'titleThai' => $apiUserData['titleThai'],
                    'user_type' => $apiUserData['user_type'],
                    'degree' => $apiUserData['degree'],
                    'gender' => $apiUserData['gender'],
                    'nickname' => $apiUserData['nickname'],
                    'birth_date' => $apiUserData['birth_date'],
                    'nationality' => $apiUserData['nationality'],
                    'citizen_id' => $apiUserData['citizen_id'],
                    'passport_id' => $apiUserData['passport_id']
                ];


                // Profile picture logic:
                // 1. If user has NULL/empty profile_picture -> use API picture
                // 2. If user has a locally uploaded picture (contains 'dashboard/profile-image/' or 'uploads/profile') -> keep it
                // 3. If user has an external API picture -> update with fresh API picture
                $existingProfilePic = $existingUser['profile_picture'] ?? '';
                $isLocalUpload = !empty($existingProfilePic) && (
                    strpos($existingProfilePic, 'dashboard/profile-image/') !== false ||
                    strpos($existingProfilePic, 'uploads/profile') !== false
                );

                if (!empty($apiUserData['profile_picture'])) {
                    if (empty($existingProfilePic)) {
                        // User has no picture - use API picture
                        $updateData['profile_picture'] = $apiUserData['profile_picture'];
                        log_message('info', 'Setting profile picture from API for user with no picture: ' . $existingUser['email']);
                    } elseif (!$isLocalUpload) {
                        // User has external API picture - update with fresh API picture
                        $updateData['profile_picture'] = $apiUserData['profile_picture'];
                        log_message('info', 'Updating external API profile picture for user: ' . $existingUser['email']);
                    } else {
                        // User has locally uploaded picture - preserve it
                        log_message('info', 'Preserving locally uploaded profile picture for user: ' . $existingUser['email']);
                    }
                }

                // Update login_uid if it's null or empty
                if (empty($existingUser['login_uid']) && !empty($loginUid)) {
                    $updateData['login_uid'] = $loginUid;
                    log_message('info', 'Updated login_uid for existing user: ' . $existingUser['email'] . ' -> ' . $loginUid);
                }

                $this->userModel->update($existingUser['email'], $updateData);

                $userData = array_merge($existingUser, $updateData);

                log_message('info', 'Updated existing user from API: ' . $email);

                // Update corresponding author record if exists
                $this->updateAuthorFromAPI($userData);
            } else {
                // Create new user from API data
                $apiUserData['created_at'] = date('Y-m-d H:i:s');
                $apiUserData['last_login_at'] = date('Y-m-d H:i:s');

                $userId = $this->userModel->insertUserData($apiUserData);

                if (! $userId) {
                    throw new \Exception('Failed to create user record');
                }

                $userData = $this->userModel->find($userId);
                if (! $userData) {
                    $userData = $apiUserData;
                    $userData['email'] = $userId;
                    $userData['role'] = $userData['role'] ?? 'user';
                }

                log_message('info', 'Created new user from API: ' . $userId . ' (' . $email . ')');

                // Auto-create author record for new user
                $this->createAuthorFromAPI($userData);
            }

            // Fetch additional user data from API if needed
            $this->fetchAdditionalUserData($userData, $accessToken);

            $this->userModel->db->transComplete();

            if ($this->userModel->db->transStatus() === false) {
                throw new \Exception('Database transaction failed');
            }

            return $userData;
        } catch (\Exception $e) {
            $this->userModel->db->transRollback();
            log_message('error', 'Error processing API user login: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create author record from API user data
     */
    private function createAuthorFromAPI($userData)
    {
        try {
            $authorData = [
                'email'            => \App\Libraries\UserIdentity::normalizeEmail((string) $userData['email']),
                'user_email'       => \App\Libraries\UserIdentity::normalizeEmail((string) $userData['email']),
                'created_by_email' => \App\Libraries\UserIdentity::normalizeEmail((string) $userData['email']),
            ];

            $existingAuthor = $this->authorModel->getAuthorByEmail($userData['email']);

            if (! $existingAuthor) {
                $this->authorModel->insert($authorData);
                log_message('info', 'Created author record for user: ' . $authorData['email']);
            } else {
                $this->authorModel->linkAuthorToUser($existingAuthor['id'], $authorData['email']);
                log_message('info', 'Linked existing author to user: ' . $authorData['email']);
            }
        } catch (\Exception $e) {
            log_message('error', 'Failed to create/link author for user ' . ($userData['email'] ?? '') . ': ' . $e->getMessage());
        }
    }

    /**
     * Update author record from API user data
     */
    private function updateAuthorFromAPI($userData)
    {
        try {
            $author = $this->authorModel->getAuthorByEmail($userData['email']);

            if ($author) {
                $updateData = [
                    'name' => trim($userData['gf_name'] . ' ' . $userData['gl_name']),
                    'affiliation' => $userData['major'] ?? $author['affiliation']
                ];

                $this->authorModel->update($author['id'], $updateData);
                log_message('info', 'Updated author record for user: ' . $userData['email']);
            }
        } catch (\Exception $e) {
            log_message('error', 'Failed to update author for user ' . ($userData['email'] ?? '') . ': ' . $e->getMessage());
        }
    }

    /**
     * Fetch additional user data from API if needed
     */
    private function fetchAdditionalUserData($userData, $accessToken)
    {
        try {
            // Example: Fetch user's research interests, publications, etc.
            $additionalData = $this->oauthService->getAdditionalUserData($accessToken, $userData['login_uid']);

            if ($additionalData && !empty($additionalData)) {
                // Update user with additional data
                $updateFields = [];

                if (isset($additionalData['research_interests'])) {
                    $updateFields['research_interests'] = json_encode($additionalData['research_interests']);
                }

                if (isset($additionalData['position'])) {
                    $updateFields['position'] = $additionalData['position'];
                }

                if (!empty($updateFields)) {
                    $this->userModel->update(\App\Libraries\UserIdentity::normalizeEmail((string) ($userData['email'] ?? '')), $updateFields);
                    log_message('info', 'Updated additional data for user: ' . ($userData['email'] ?? ''));
                }
            }
        } catch (\Exception $e) {
            log_message('warning', 'Failed to fetch additional user data: ' . $e->getMessage());
            // Don't throw exception as this is not critical
        }
    }

    /**
     * Set user session data
     */
    private function setUserSession($userData, $accessToken = null)
    {
        $role = $userData['role'] ?? 'user';
        $managedFaculties = $userData['managed_faculties'] ?? null;
        $loginMethod = ($accessToken !== null) ? 'oauth_api' : 'newscience_sso';

        $sessionData = [
            'email' => \App\Libraries\UserIdentity::normalizeEmail((string) ($userData['email'] ?? '')),
            'name' => trim($userData['gf_name'] . ' ' . $userData['gl_name']),
            'thai_name' => trim(($userData['thai_name'] ?? '') . ' ' . ($userData['thai_lastname'] ?? '')),
            'title' => $userData['title'] ?? '',
            'major' => $userData['major'] ?? '',
            'is_admin' => $userData['admin'] ?? 0,
            'edoc' => $userData['edoc'] ?? 0,
            'profile_picture' => $userData['profile_picture'] ?? '',
            'logged_in' => true,
            'login_time' => time(),
            'login_method' => $loginMethod,
            'role' => $role,
            'managed_faculties' => $managedFaculties
        ];

        // Clear any stale admin flags before setting new session data
        $this->session->remove('backdoor_admin_auth');

        $normEmail = \App\Libraries\UserIdentity::normalizeEmail((string) ($userData['email'] ?? ''));

        $sessionToSet = [
            'user_data' => $sessionData,
            'logged_in' => true,
            'user_email' => $normEmail,
            'user_id' => $normEmail,
            'user_role' => $role,
        ];

        if ($accessToken) {
            $sessionToSet['access_token'] = $accessToken;
            $sessionToSet['token_expires'] = time() + 3600; // Assume 1 hour expiry
        }

        if (in_array($role, ['super_admin', 'faculty_admin'], true)) {
            $sessionToSet['backdoor_admin_auth'] = true;
        }

        $this->session->set($sessionToSet);
    }

    /**
     * Determine where to send the user after authentication
     * All users go to the general dashboard, admins can access admin dashboard from there
     */
    private function getPostLoginRedirect(array $userData = []): string
    {
        // All users (including admins) go to the general user dashboard
        // Admins will see a button to access the admin dashboard from there
        return site_url('dashboard');
    }

    /**
     * SSO entry จาก newScience — รับ token (ลงนามด้วย shared secret) แล้วสร้าง session โดยไม่ต้อง login ซ้ำ
     * ใช้ Email เป็นตัวระบุตัวตนร่วมกับ newScience และ Edoc
     * GET /auth/sso-entry?token=xxx
     */
    public function ssoEntry()
    {
        $config = config(NewscienceSso::class);
        if (!$config->enabled || $config->sharedSecret === '') {
            log_message('warning', self::SSO_LOG_PREFIX . 'ssoEntry disabled or sharedSecret empty');
            return redirect()->to(site_url('auth/login'))->with('error', 'SSO จาก newScience ยังไม่เปิดใช้');
        }

        $token = $this->extractSsoToken();
        if ($token === null) {
            log_message('warning', self::SSO_LOG_PREFIX . 'ssoEntry missing token');
            return redirect()->to(site_url('auth/login'))->with('error', 'ไม่พบ token จาก newScience');
        }

        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            log_message('warning', self::SSO_LOG_PREFIX . 'ssoEntry invalid token format');
            return redirect()->to(site_url('auth/login'))->with('error', 'Token ไม่ถูกต้อง');
        }

        $payloadB64 = $parts[0];
        $sigB64 = $parts[1];

        $expectedSig = hash_hmac('sha256', $payloadB64, $config->sharedSecret, true);
        $signature = $this->base64UrlDecode($sigB64);
        if ($signature === null || !hash_equals($expectedSig, $signature)) {
            log_message('warning', self::SSO_LOG_PREFIX . 'ssoEntry invalid signature secret_len=' . strlen($config->sharedSecret) . ' token_len=' . strlen($token));
            return redirect()->to(site_url('auth/login'))->with('error', 'Token ไม่ถูกต้อง (ตรวจ secret ระหว่าง newScience กับ Research Record)');
        }

        $payloadJson = $this->base64UrlDecode($payloadB64);
        if ($payloadJson === null) {
            log_message('warning', self::SSO_LOG_PREFIX . 'ssoEntry invalid payload encoding');
            return redirect()->to(site_url('auth/login'))->with('error', 'Token ไม่ถูกต้อง');
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload) || empty($payload['email'])) {
            log_message('warning', self::SSO_LOG_PREFIX . 'ssoEntry missing email in payload');
            return redirect()->to(site_url('auth/login'))->with('error', 'Token ไม่มีข้อมูลผู้ใช้');
        }

        $exp = $payload['exp'] ?? 0;
        if ($exp < time()) {
            log_message('warning', self::SSO_LOG_PREFIX . 'ssoEntry token expired');
            return redirect()->to(site_url('auth/login'))->with('error', 'Token หมดอายุ กรุณาเข้าใหม่จาก newScience');
        }

        $email = trim($payload['email']);
        $name = trim($payload['name'] ?? '');

        $user = $this->userModel->where('email', $email)->first();
        if (!$user) {
            $nameParts = preg_split('/\s+/', $name, 2);
            $gfName = $nameParts[0] ?? '';
            $glName = $nameParts[1] ?? '';
            $newUser = [
                'email' => $email,
                'login_uid' => $email,
                'gf_name' => $gfName,
                'gl_name' => $glName,
                'thai_name' => $name,
                'thai_lastname' => '',
                'active' => 1,
                'edoc' => 1,
                'profile_customer' => 'newscience_sso',
            ];
            $insertedEmail = $this->userModel->insertUserData($newUser);
            if (!$insertedEmail) {
                log_message('error', self::SSO_LOG_PREFIX . 'ssoEntry failed to create user email=' . $email);
                return redirect()->to(site_url('auth/login'))->with('error', 'ไม่สามารถสร้างผู้ใช้ได้');
            }
            $user = $this->userModel->find($insertedEmail);
            log_message('info', self::SSO_LOG_PREFIX . 'ssoEntry created user email=' . $email);
        }

        $userData = $user;
        $this->setUserSession($userData, null);
        log_message('info', self::SSO_LOG_PREFIX . 'ssoEntry success email=' . $email);

        $entryPath = \App\Libraries\PublicationReturnNavigation::applySsoPayload($payload);

        return redirect()->to(site_url(ltrim($entryPath, '/')))->with('success', 'เข้าสู่ระบบจาก newScience สำเร็จ');
    }

    /**
     * อ่าน token จาก query — รองรับ IIS/PHP ที่อาจทำให้ค่าใน URL เพี้ยน
     */
    private function extractSsoToken(): ?string
    {
        $token = $this->request->getGet('token');
        if (is_string($token) && $token !== '') {
            return $this->normalizeSsoToken($token);
        }

        $qs = $this->request->getServer('QUERY_STRING');
        if (is_string($qs) && preg_match('/(?:^|&)token=([^&]+)/', $qs, $m)) {
            return $this->normalizeSsoToken(rawurldecode($m[1]));
        }

        return null;
    }

    private function normalizeSsoToken(string $token): string
    {
        $token = trim($token);

        // PHP แปลง + ใน query string เป็น space (กรณี encoding แบบ form)
        return str_replace(' ', '+', $token);
    }

    /**
     * Base64 URL-safe decode
     */
    private function base64UrlDecode(string $data): ?string
    {
        $padding = 4 - (strlen($data) % 4);
        if ($padding !== 4) {
            $data .= str_repeat('=', $padding);
        }
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        return $decoded !== false ? $decoded : null;
    }

    /**
     * Logout user and clear session
     * IMPORTANT: When logging out from backdoor god mode, ALL session data including god mode flags will be cleared
     */
    public function logout()
    {
        try {
            $skipNs = $this->request->getGet('skip_ns') === '1';
            $returnUrl = $this->request->getGet('return_url');
            $safeReturnUrl = null;
            if (is_string($returnUrl) && $returnUrl !== '') {
                // same-origin only
                $base = rtrim(site_url(), '/');
                if (strpos($returnUrl, $base . '/') === 0) {
                    $safeReturnUrl = $returnUrl;
                }
            }

            $userData = $this->session->get('user_data');

            // Check if this is a backdoor/god mode session
            $isGodMode = $this->session->get('god_mode') || $this->session->get('backdoor_session');
            $backdoorAuth = $this->session->get('backdoor_admin_auth');

            if ($userData) {
                // Log the logout
                $logType = $isGodMode ? 'God mode user logged out (ALL session cleared)' : 'User logged out';
                log_message('info', $logType . ': ' . ($userData['email'] ?? 'unknown'));

                // Optional: Call API logout endpoint if available (not for backdoor sessions)
                if (!$isGodMode) {
                    $accessToken = $this->session->get('access_token');
                    if ($accessToken) {
                        try {
                            $this->oauthService->logout($accessToken);
                        } catch (\Exception $e) {
                            log_message('warning', 'API logout failed: ' . $e->getMessage());
                            // Continue with logout even if API logout fails
                        }
                    }
                }
            }

            // Log god mode session details before clearing
            if ($isGodMode || $backdoorAuth) {
                log_message('info', '========== BACKDOOR GOD MODE LOGOUT START ==========');
                log_message('info', 'Logout - God Mode Session Detected:');
                log_message('info', '  - god_mode: ' . ($this->session->get('god_mode') ? 'YES' : 'NO'));
                log_message('info', '  - backdoor_session: ' . ($this->session->get('backdoor_session') ? 'YES' : 'NO'));
                log_message('info', '  - backdoor_admin_auth: ' . ($backdoorAuth ? 'YES' : 'NO'));
                log_message('info', '  - backdoor_login: ' . ($this->session->get('backdoor_login') ? 'YES' : 'NO'));
                log_message('info', 'Logout - Clearing ALL session data including god mode flags');
            }

            // IMPORTANT: Always destroy ALL session data, including god mode flags
            // This ensures that when logging out from backdoor god mode, everything is cleared
            $this->session->destroy();

            // Explicitly clear all god mode related session variables (in case destroy didn't work)
            // Note: After destroy(), these should already be cleared, but we do this for safety
            try {
                $this->session->remove('god_mode');
                $this->session->remove('backdoor_session');
                $this->session->remove('backdoor_admin_auth');
                $this->session->remove('backdoor_login');
                $this->session->remove('user_data');
                $this->session->remove('logged_in');
                $this->session->remove('user_id');
                $this->session->remove('user_role');
                $this->session->remove('access_token');
                $this->session->remove('token_expires');
            } catch (\Exception $clearError) {
                log_message('warning', 'Error clearing individual session variables: ' . $clearError->getMessage());
            }

            if ($isGodMode || $backdoorAuth) {
                log_message('info', '========== BACKDOOR GOD MODE LOGOUT COMPLETE ==========');
                log_message('info', 'All god mode session data has been cleared. User must re-authenticate.');
            }

            log_message('info', 'Session destroyed successfully');

            // Redirect to login page (NOT back to backdoor portal)
            $message = ($isGodMode || $backdoorAuth)
                ? 'You have been logged out successfully. All god mode access has been cleared.'
                : 'You have been logged out successfully!';

            // Redirect to local login page of Research Record directly
            return redirect()->to($safeReturnUrl ?: site_url('auth/login?logout=1'))->with('success', $message);
        } catch (\Exception $e) {
            log_message('error', 'Logout error: ' . $e->getMessage());

            // Force session destruction even on error
            try {
                // Clear all god mode flags explicitly
                $this->session->remove('god_mode');
                $this->session->remove('backdoor_session');
                $this->session->remove('backdoor_admin_auth');
                $this->session->remove('backdoor_login');

                // Destroy entire session
                $this->session->destroy();
            } catch (\Exception $sessionError) {
                log_message('error', 'Session destruction failed: ' . $sessionError->getMessage());
            }

            // Redirect to login anyway
            return redirect()->to(site_url('auth/login?logout=1'))->with('error', 'Logged out with errors.');
        }
    }

    /**
     * Check if user is authenticated
     */
    public function checkAuth()
    {
        if (!$this->session->has('logged_in') || !$this->session->get('logged_in')) {
            return redirect()->to(site_url('auth/login'));
        }

        // Check token expiry
        $tokenExpires = $this->session->get('token_expires');
        if ($tokenExpires && time() > $tokenExpires) {
            $this->session->destroy();
            return redirect()->to(site_url('auth/login'))->with('error', 'Session expired. Please login again.');
        }

        return true;
    }

    /**
     * Refresh user data from API
     */
    public function refreshUserData()
    {
        if (!$this->session->get('logged_in')) {
            return redirect()->to(site_url('auth/login'));
        }

        try {
            $accessToken = $this->session->get('access_token');
            if (!$accessToken) {
                throw new \Exception('No access token available');
            }

            // Get fresh user data from API
            $userInfo = $this->oauthService->getUserInfo($accessToken);

            // Update user data
            $userData = $this->session->get('user_data');
            $updatedUserData = $this->processAPIUserLogin($userInfo, $accessToken);

            // Update session
            $this->setUserSession($updatedUserData, $accessToken);

            return redirect()->back()->with('success', 'User data refreshed successfully!');
        } catch (\Exception $e) {
            log_message('error', 'Failed to refresh user data: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to refresh user data');
        }
    }

    /**
     * Get current user data
     */
    public function getCurrentUser()
    {
        return $this->session->get('user_data');
    }

    /**
     * Check if current user is admin
     */
    public function isAdmin()
    {
        $userData = $this->session->get('user_data');
        return $userData && isset($userData['is_admin']) && $userData['is_admin'] == 1;
    }

    /**
     * Require authentication middleware
     */
    public function requireAuth()
    {
        $authCheck = $this->checkAuth();
        if ($authCheck !== true) {
            return $authCheck;
        }
    }

    /**
     * Require admin privileges
     */
    public function requireAdmin()
    {
        $this->requireAuth();

        if (!$this->isAdmin()) {
            return redirect()->to(site_url('dashboard'))->with('error', 'Insufficient permissions');
        }
    }

    /**
     * Sync user data with API (manual trigger)
     */
    public function syncWithAPI()
    {
        if (!$this->session->get('logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not authenticated']);
        }

        try {
            $accessToken = $this->session->get('access_token');
            $userData = $this->session->get('user_data');

            if (!$accessToken) {
                throw new \Exception('No access token available');
            }

            // Fetch latest data from API
            $apiUserInfo = $this->oauthService->getUserInfo($accessToken);

            // Update database
            $updatedUserData = $this->processAPIUserLogin($apiUserInfo, $accessToken);

            // Update session
            $this->setUserSession($updatedUserData, $accessToken);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Data synchronized successfully',
                'data' => $updatedUserData
            ]);
        } catch (\Exception $e) {
            log_message('error', 'API sync failed: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Synchronization failed: ' . $e->getMessage()
            ]);
        }
    }
}
