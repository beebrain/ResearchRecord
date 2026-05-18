<!-- Top Navigation Bar -->
<nav class="bg-white shadow-sm border-b border-gray-200 px-6 py-4 relative" style="z-index: 0;">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= $pageTitle ?? 'แดชบอร์ดผู้ดูแลระบบ' ?></h1>
            <p class="text-sm text-gray-600"><?= $pageSubtitle ?? '' ?></p>
        </div>
        <div class="flex items-center space-x-4">
            <?php if (session()->get('god_mode') && !session()->get('user_data')): ?>
                <span class="bg-gradient-to-r from-purple-600 to-pink-600 text-white text-xs font-bold px-3 py-1 rounded-full animate-pulse">
                    👁️ GOD MODE
                </span>
            <?php endif; ?>

            <!-- Optional Actions (passed from parent view) -->
            <?php if (isset($headerActions)): ?>
                <?= $headerActions ?>
            <?php endif; ?>

            <!-- User Info Dropdown -->
            <div class="relative group">
                <div class="flex items-center space-x-3 cursor-pointer hover:bg-gray-50 px-3 py-2 rounded-lg transition-colors">
                    <?php
                    $userData = session()->get('user_data') ?? [];
                    $thaiName = !empty($userData['thai_name']) ? $userData['thai_name'] . ' ' . ($userData['thai_lastname'] ?? '') : '';
                    $englishName = !empty($userData['gf_name']) ? $userData['gf_name'] . ' ' . ($userData['gl_name'] ?? '') : '';
                    $displayName = !empty($thaiName) ? $thaiName : (!empty($englishName) ? $englishName : (session()->get('user_name') ?? 'Admin'));
                    $email = $userData['email'] ?? '';
                    $major = $userData['major'] ?? '';
                    $title = $userData['titleThai'] ?? $userData['title'] ?? '';
                    $userRole = $userData['role'] ?? 'user';
                    $roleDisplay = ucfirst(str_replace('_', ' ', $userRole));
                    ?>
                    <div class="text-right">
                        <div class="text-sm font-semibold text-gray-900">
                            <?= $title ?> <?= $displayName ?>
                        </div>
                        <div class="text-xs text-gray-500">
                            <?= $roleDisplay ?>
                        </div>
                    </div>
                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                        <?= strtoupper(substr($displayName, 0, 1)) ?>
                    </div>
                </div>

                <!-- Dropdown Menu -->
                <div class="hidden group-hover:block absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-lg border border-gray-200 py-2" style="z-index: 20;">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <div class="font-semibold text-gray-900"><?= $title ?> <?= $displayName ?></div>
                        <?php if (!empty($email)): ?>
                            <div class="text-sm text-gray-600 mt-1">📧 <?= $email ?></div>
                        <?php endif; ?>
                        <?php if (!empty($major)): ?>
                            <div class="text-sm text-gray-600 mt-1">🎓 <?= $major ?></div>
                        <?php endif; ?>
                        <div class="text-xs text-gray-500 mt-2 px-2 py-1 bg-blue-50 rounded inline-block">
                            <?= $roleDisplay ?>
                        </div>
                    </div>
                    <a href="<?= base_url('index.php/profile') ?>" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        👤 โปรไฟล์ของฉัน
                    </a>
                    <a href="<?= base_url('index.php/dashboard') ?>" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        ← กลับไปยังแดชบอร์ดผู้ใช้
                    </a>
                    <div class="border-t border-gray-100 my-1"></div>
                    <a href="<?= base_url('index.php/auth/logout') ?>" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        🚪 ออกจากระบบ
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>