<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Settings') ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.min.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: #f8f9fa;
        }

        .section-title {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }
    </style>
</head>

<body class="min-h-screen py-8">
    <!-- Quick Access Bar -->
    <div class="max-w-5xl mx-auto px-4 mb-6">
        <div class="flex items-center justify-between bg-white rounded-xl shadow-sm px-4 py-3 border border-gray-100">
            <div class="flex items-center gap-4">
                <a href="<?= site_url('dashboard') ?>" class="text-gray-500 hover:text-gray-700 text-sm">← กลับ Dashboard</a>
                <span class="text-gray-300">|</span>
                <span class="font-semibold text-gray-700">⚙️ Settings</span>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?= site_url('dashboard/cv') ?>" class="px-3 py-1.5 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50">📄 ดู CV</a>
                <a href="<?= site_url('dashboard/cv-manage') ?>" class="px-3 py-1.5 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50">📝 จัดการ CV</a>
                <a href="<?= site_url('dashboard/orcid') ?>" class="px-3 py-1.5 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50">🔗 ORCID</a>
            </div>
        </div>
    </div>

    <!-- CV Style Layout -->
    <div class="max-w-5xl mx-auto px-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Header -->
            <div class="p-6 border-b border-gray-100">
                <h1 class="text-2xl font-light text-gray-800 tracking-wide">PROFILE SETTINGS</h1>
                <p class="text-sm text-gray-500">จัดการข้อมูลโปรไฟล์และการติดต่อ</p>
            </div>

            <!-- Two Column Layout -->
            <div class="flex">
                <!-- Left Column - Profile Picture & Quick Info -->
                <div class="w-1/3 p-6 bg-gray-50 border-r border-gray-100">
                    <!-- Profile Picture -->
                    <h3 class="section-title">Profile Picture</h3>
                    <div class="text-center mb-8">
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img src="<?= esc(str_replace('https:', 'http:', $user['profile_picture'])) ?>" class="w-28 h-28 rounded-full object-cover mx-auto mb-4 border-4 border-white shadow" id="profilePreview">
                        <?php else: ?>
                            <div class="w-28 h-28 rounded-full bg-gradient-to-br from-gray-300 to-gray-400 mx-auto mb-4 flex items-center justify-center text-white text-4xl font-light border-4 border-white shadow" id="profileInitial">
                                <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <label class="inline-block px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 cursor-pointer hover:bg-gray-100 transition">
                            📁 Change Photo
                            <input type="file" id="profilePicture" accept="image/*" class="hidden">
                        </label>
                        <p class="text-xs text-gray-400 mt-2">JPG, PNG max 5MB</p>
                    </div>

                    <!-- Quick Links -->
                    <h3 class="section-title">Quick Links</h3>
                    <div class="space-y-2">
                        <a href="<?= site_url('dashboard/cv') ?>" class="block text-sm text-gray-600 hover:text-gray-800">→ View CV</a>
                        <a href="<?= site_url('dashboard/cv-manage') ?>" class="block text-sm text-gray-600 hover:text-gray-800">→ Manage CV Sections</a>
                        <a href="<?= site_url('dashboard/orcid') ?>" class="block text-sm text-gray-600 hover:text-gray-800">→ ORCID Sync</a>
                    </div>
                </div>

                <!-- Right Column - Forms -->
                <div class="w-2/3 p-6">
                    <!-- Profile Form -->
                    <h3 class="section-title">Professional Profile</h3>
                    <form id="profileForm" action="<?= site_url('dashboard/settings/profile-summary') ?>" method="POST" class="mb-8">
                        <?= csrf_field() ?>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs text-gray-500 uppercase mb-1">Bio / Summary</label>
                                <textarea name="bio" rows="4" class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:border-gray-400 outline-none resize-none text-sm" placeholder="สรุปประวัติวิชาชีพสั้นๆ..."><?= esc($bio ?? '') ?></textarea>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 uppercase mb-1">Expertise / Skills</label>
                                <input type="text" name="expertise" value="<?= esc($expertise ?? '') ?>" class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:border-gray-400 outline-none text-sm" placeholder="AI, Data Science, ML (comma separated)">
                            </div>
                            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-6 py-2 rounded-lg text-sm font-medium">
                                Save Profile
                            </button>
                        </div>
                    </form>

                    <!-- Contact Form -->
                    <h3 class="section-title">Contact Information</h3>
                    <form id="contactForm" action="<?= site_url('dashboard/settings/contact') ?>" method="POST">
                        <?= csrf_field() ?>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs text-gray-500 uppercase mb-1">Phone</label>
                                <input type="text" name="phone" value="<?= esc($user_profile['phone'] ?? '') ?>" class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:border-gray-400 outline-none text-sm" placeholder="+66...">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 uppercase mb-1">Institution</label>
                                <input type="text" name="institution" value="<?= esc($user_profile['institution'] ?? '') ?>" class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:border-gray-400 outline-none text-sm" placeholder="University...">
                            </div>
                        </div>

                        <p class="text-xs text-gray-500 uppercase mb-3">Academic Profile Links</p>
                        <div class="space-y-3 mb-4">
                            <input type="url" name="google_scholar" value="<?= esc($social_links['google_scholar'] ?? '') ?>" class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:border-gray-400 outline-none text-sm" placeholder="Google Scholar URL">
                            <input type="url" name="orcid" value="<?= esc($social_links['orcid'] ?? '') ?>" class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:border-gray-400 outline-none text-sm" placeholder="ORCID URL">
                            <input type="url" name="scopus" value="<?= esc($social_links['scopus'] ?? '') ?>" class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:border-gray-400 outline-none text-sm" placeholder="Scopus URL">
                            <input type="url" name="researchgate" value="<?= esc($social_links['researchgate'] ?? '') ?>" class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:border-gray-400 outline-none text-sm" placeholder="ResearchGate URL">
                            <input type="url" name="linkedin" value="<?= esc($social_links['linkedin'] ?? '') ?>" class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:border-gray-400 outline-none text-sm" placeholder="LinkedIn URL">
                        </div>

                        <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-6 py-2 rounded-lg text-sm font-medium">
                            Save Contact
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('profilePicture')?.addEventListener('change', async (e) => {
            if (!e.target.files.length) return;
            const formData = new FormData();
            formData.append('profile_picture', e.target.files[0]);
            formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

            const res = await fetch('<?= site_url('dashboard/profile-picture') ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            const data = await res.json();
            if (data.success) location.reload();
            else Swal.fire('Error', data.message, 'error');
        });

        ['profileForm', 'contactForm'].forEach(id => {
            document.getElementById(id)?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const res = await fetch(e.target.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new FormData(e.target)
                });
                const data = await res.json();
                if (data.success) Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    timer: 1500,
                    showConfirmButton: false
                });
                else Swal.fire('Error', data.message, 'error');
            });
        });
    </script>
</body>

</html>