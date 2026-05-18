<?php

use App\Helpers\RoleHelper;
use App\Models\UserModel;

$session = session();
$userData = $session->get('user_data') ?? [];
$userRole = $userData['role'] ?? 'user';
$isAdmin = $userData['is_admin'] ?? 0;
$adminFlag = $userData['admin'] ?? 0;

// Get user data for Dean/Chair checks
$userModel = new UserModel();
$user = $userModel->find($userData['uid'] ?? 0);

// ========== NAVIGATION ROLE CHECK LOG START ==========
log_message('info', '========== NAVIGATION ROLE CHECK START ==========');
log_message('info', 'Navigation - User Data: ' . json_encode([
    'uid' => $userData['uid'] ?? 'N/A',
    'role' => $userRole,
    'is_admin' => $isAdmin,
    'admin' => $adminFlag,
    'email' => $userData['email'] ?? 'N/A'
]));

// Check if user is in god mode (backdoor access)
// NOTE: Even in god mode, faculty_admin should NOT see User Roles menu
$isGodMode = $session->get('god_mode') === true || $session->get('backdoor_session') === true || $session->get('backdoor_admin_auth') === true;
log_message('info', 'Navigation - God Mode: ' . ($isGodMode ? 'YES' : 'NO'));
log_message('info', 'Navigation - God Mode Sources: ' . json_encode([
    'god_mode' => $session->get('god_mode'),
    'backdoor_session' => $session->get('backdoor_session'),
    'backdoor_admin_auth' => $session->get('backdoor_admin_auth')
]));

// CRITICAL: Check if user is faculty admin FIRST (before checking super admin)
// Faculty admin if role is EXACTLY 'faculty_admin' - regardless of god mode
// This check must come FIRST to prevent faculty_admin from being treated as super_admin
// IMPORTANT: Faculty admin should NEVER see User Roles menu, even in god mode
$isFacultyAdmin = false;
if ($userRole === 'faculty_admin') {
    $isFacultyAdmin = true;
}
log_message('info', 'Navigation - Faculty Admin Check:');
log_message('info', '  - userRole: ' . $userRole);
log_message('info', '  - userRole === "faculty_admin": ' . ($userRole === 'faculty_admin' ? 'YES' : 'NO'));
log_message('info', '  - isFacultyAdmin: ' . ($isFacultyAdmin ? 'YES' : 'NO'));
log_message('info', '  - IMPORTANT: Faculty Admin will NEVER see User Roles menu, even in God Mode');

// Check if user is super admin (by role or admin flag)
// IMPORTANT: Faculty admin should NEVER be treated as super admin, even in god mode or with admin flags
$isSuperAdmin = false;
log_message('info', 'Navigation - Super Admin Check:');
if ($isFacultyAdmin) {
    // Faculty admin is NEVER super admin, even in god mode or with admin flags
    $isSuperAdmin = false;
    log_message('info', '  - Reason: Faculty Admin is NEVER Super Admin (even in God Mode or with admin flags)');
} elseif ($isGodMode) {
    // God mode = super admin (but only if NOT faculty_admin)
    $isSuperAdmin = true;
    log_message('info', '  - Reason: God Mode = Super Admin (and NOT faculty_admin)');
} elseif ($userRole === 'super_admin') {
    // Explicit super_admin role
    $isSuperAdmin = true;
    log_message('info', '  - Reason: Explicit super_admin role');
} elseif (($isAdmin == 1 || $adminFlag == 1) && $userRole !== 'faculty_admin') {
    // Has admin flags AND is NOT faculty_admin
    $isSuperAdmin = true;
    log_message('info', '  - Reason: Has admin flags AND is NOT faculty_admin');
} else {
    log_message('info', '  - Reason: No conditions met - NOT Super Admin');
}
log_message('info', '  - Final isSuperAdmin: ' . ($isSuperAdmin ? 'YES' : 'NO'));

// Check if user is Dean or Chair
$isDean = RoleHelper::isDean($user);
$isChair = RoleHelper::isChair($user);

log_message('info', 'Navigation - Final Role Status:');
log_message('info', '  - isGodMode: ' . ($isGodMode ? 'YES' : 'NO'));
log_message('info', '  - isFacultyAdmin: ' . ($isFacultyAdmin ? 'YES' : 'NO'));
log_message('info', '  - isSuperAdmin: ' . ($isSuperAdmin ? 'YES' : 'NO'));
log_message('info', '  - isDean: ' . ($isDean ? 'YES' : 'NO'));
log_message('info', '  - isChair: ' . ($isChair ? 'YES' : 'NO'));
log_message('info', '========== NAVIGATION ROLE CHECK END ==========');
// ========== NAVIGATION ROLE CHECK LOG END ==========

// Get current URI for active menu highlighting
$currentUri = service('uri')->getPath();
?>

<!-- Sidebar Navigation -->
<aside class="w-64 bg-white shadow-sm h-screen sticky top-0">
    <div class="p-6">
        <nav class="space-y-2">
            <!-- Dashboard - Available to all admins -->
            <a href="<?= base_url('index.php/admin/dashboard') ?>"
                class="flex items-center px-3 py-2 text-sm font-medium <?= strpos($currentUri, 'admin/dashboard') !== false ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?> rounded-lg">
                📊 แดชบอร์ด
            </a>

            <?php
            // Log menu rendering decision
            log_message('info', 'Navigation - Menu Rendering Decision:');
            log_message('info', '  - Will show Super Admin menu: ' . ($isSuperAdmin ? 'YES' : 'NO'));
            log_message('info', '  - Will show Faculty Admin menu: ' . ($isFacultyAdmin ? 'YES' : 'NO'));
            log_message('info', '  - Will show Dean menu: ' . ($isDean ? 'YES' : 'NO'));
            log_message('info', '  - Will show Chair menu: ' . ($isChair ? 'YES' : 'NO'));

            if ($isSuperAdmin):
                log_message('info', 'Navigation - Rendering SUPER ADMIN menu');
            ?>
                <!-- Super Admin sees all menus -->
                <a href="<?= base_url('index.php/admin/publications/manage') ?>"
                    class="flex items-center px-3 py-2 text-sm font-medium <?= strpos($currentUri, 'publications/manage') !== false ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?> rounded-lg">
                    📚 จัดการผลงานวิจัย
                </a>

                <a href="<?= base_url('index.php/admin/publications/summary') ?>"
                    class="flex items-center px-3 py-2 text-sm font-medium <?= strpos($currentUri, 'publications/summary') !== false ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?> rounded-lg">
                    📊 สรุปผลงานวิจัย
                </a>

                <a href="<?= base_url('index.php/admin/manageEmails') ?>"
                    class="flex items-center px-3 py-2 text-sm font-medium <?= strpos($currentUri, 'manageEmails') !== false ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?> rounded-lg">
                    👥 ผู้แต่ง
                </a>

                <a href="<?= base_url('index.php/admin/manage-user-curriculum') ?>"
                    class="flex items-center px-3 py-2 text-sm font-medium <?= strpos($currentUri, 'manage-user-curriculum') !== false ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?> rounded-lg">
                    🎓 หลักสูตรของผู้ใช้
                </a>

                <a href="<?= base_url('index.php/admin/faculty-curriculum') ?>"
                    class="flex items-center px-3 py-2 text-sm font-medium <?= strpos($currentUri, 'faculty-curriculum') !== false ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?> rounded-lg">
                    🏛️ คณะและหลักสูตร
                </a>

                <?php
                log_message('info', 'Navigation - Rendering User Roles menu (Super Admin only)');
                ?>
                <a href="<?= base_url('index.php/admin/admission') ?>"
                    class="flex items-center px-3 py-2 text-sm font-medium <?= strpos($currentUri, 'admission') !== false ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?> rounded-lg">
                    📋 แบบฟอร์มเปิดรับ นศ.
                </a>

                <a href="<?= base_url('index.php/admin/education') ?>"
                    class="flex items-center px-3 py-2 text-sm font-medium <?= strpos($currentUri, 'admin/education') !== false ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?> rounded-lg">
                    🎓 ประวัติการศึกษา
                </a>

                <a href="<?= base_url('index.php/admin/user-roles') ?>"
                    class="flex items-center px-3 py-2 text-sm font-medium <?= strpos($currentUri, 'user-roles') !== false ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?> rounded-lg">
                    🔐 บทบาทผู้ใช้
                </a>

            <?php elseif ($isFacultyAdmin):
                log_message('info', 'Navigation - Rendering FACULTY ADMIN menu (User Roles should NOT appear)');
            ?>
                <!-- Faculty Admin sees limited menus -->
                <a href="<?= base_url('index.php/admin/publications/manage') ?>"
                    class="flex items-center px-3 py-2 text-sm font-medium <?= strpos($currentUri, 'publications/manage') !== false ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?> rounded-lg">
                    📚 จัดการผลงานวิจัย
                </a>

                <a href="<?= base_url('index.php/admin/publications/summary') ?>"
                    class="flex items-center px-3 py-2 text-sm font-medium <?= strpos($currentUri, 'publications/summary') !== false ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?> rounded-lg">
                    📊 สรุปผลงานวิจัย
                </a>

                <a href="<?= base_url('index.php/admin/manage-user-curriculum') ?>"
                    class="flex items-center px-3 py-2 text-sm font-medium <?= strpos($currentUri, 'manage-user-curriculum') !== false ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?> rounded-lg">
                    🎓 หลักสูตรของผู้ใช้
                </a>

                <a href="<?= base_url('index.php/admin/admission') ?>"
                    class="flex items-center px-3 py-2 text-sm font-medium <?= strpos($currentUri, 'admission') !== false ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?> rounded-lg">
                    📋 แบบฟอร์มเปิดรับ นศ.
                </a>

                <a href="<?= base_url('index.php/admin/education') ?>"
                    class="flex items-center px-3 py-2 text-sm font-medium <?= strpos($currentUri, 'admin/education') !== false ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?> rounded-lg">
                    🎓 ประวัติการศึกษา
                </a>

            <?php elseif ($isDean):
                log_message('info', 'Navigation - Rendering DEAN menu');
            ?>
                <!-- Dean sees: Dashboard, Publications, Summary, Admission -->
                <a href="<?= base_url('index.php/admin/publications/manage') ?>"
                    class="flex items-center px-3 py-2 text-sm font-medium <?= strpos($currentUri, 'publications/manage') !== false ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?> rounded-lg">
                    📚 จัดการผลงานวิจัย
                </a>

                <a href="<?= base_url('index.php/admin/publications/summary') ?>"
                    class="flex items-center px-3 py-2 text-sm font-medium <?= strpos($currentUri, 'publications/summary') !== false ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?> rounded-lg">
                    📊 สรุปผลงานวิจัย
                </a>

                <a href="<?= base_url('index.php/admin/admission') ?>"
                    class="flex items-center px-3 py-2 text-sm font-medium <?= strpos($currentUri, 'admission') !== false ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?> rounded-lg">
                    📋 แบบฟอร์มเปิดรับ นศ.
                </a>

            <?php elseif ($isChair):
                log_message('info', 'Navigation - Rendering CHAIR menu');
            ?>
                <!-- Chair sees: Dashboard, Publications, Admission -->
                <a href="<?= base_url('index.php/admin/publications/manage') ?>"
                    class="flex items-center px-3 py-2 text-sm font-medium <?= strpos($currentUri, 'publications/manage') !== false ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?> rounded-lg">
                    📚 จัดการผลงานวิจัย
                </a>

                <a href="<?= base_url('index.php/admin/admission') ?>"
                    class="flex items-center px-3 py-2 text-sm font-medium <?= strpos($currentUri, 'admission') !== false ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?> rounded-lg">
                    📋 แบบฟอร์มเปิดรับ นศ.
                </a>

            <?php endif; ?>

            <!-- Divider -->
            <div class="border-t border-gray-200 my-4"></div>

            <!-- Back to User Dashboard - Available to all admins -->
            <a href="<?= base_url('index.php/dashboard') ?>"
                class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg">
                ← กลับไปยังแดชบอร์ดผู้ใช้
            </a>

            <!-- Logout - Available to all -->
            <a href="<?= base_url('index.php/auth/logout') ?>"
                class="flex items-center px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg">
                🚪 ออกจากระบบ
            </a>
        </nav>
    </div>
</aside>