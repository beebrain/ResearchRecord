<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Dashboard' ?></title>
    <link rel="stylesheet" href="<?= base_url('/public/assets/css/tailwind.min.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        /* Skeleton Loading Animation */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: 4px;
        }

        @keyframes skeleton-loading {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        .data-content {
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        .data-content.loaded {
            opacity: 1;
        }

        .hide-on-load {
            display: block;
        }

        .loaded .hide-on-load {
            display: none;
        }
    </style>
</head>

<body class="min-h-screen py-8">
    <!-- Quick Access Bar -->
    <div class="max-w-5xl mx-auto px-4 mb-6">
        <div class="flex items-center justify-between bg-white rounded-xl shadow-sm px-4 py-3 border border-gray-100">
            <div class="flex items-center gap-2">
                <span class="text-xl">📚</span>
                <span class="font-semibold text-gray-700">Research Portal</span>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?= base_url('index.php/publications/create') ?>" class="px-3 py-1.5 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-900">➕ เพิ่มผลงาน</a>
                <a href="<?= base_url('index.php/dashboard/cv') ?>" class="px-3 py-1.5 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50">📄 ดู CV</a>
                <a href="<?= base_url('index.php/dashboard/cv-manage') ?>" class="px-3 py-1.5 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50">📝 จัดการ CV</a>
                <a href="<?= base_url('index.php/dashboard/orcid') ?>" class="px-3 py-1.5 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50">🔗 ORCID</a>
                
                <div id="admin-button-container"></div>

                <a href="<?= base_url('index.php/dashboard/settings') ?>" class="px-2 py-1.5 text-gray-500 hover:text-gray-700">⚙️</a>
                <a href="<?= base_url('index.php/auth/logout') ?>" class="px-2 py-1.5 text-gray-500 hover:text-gray-700">🚪</a>
            </div>
        </div>
    </div>

    <!-- Flash Messages (PHP based as they happen on redirect) -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="max-w-5xl mx-auto px-4 mb-4">
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">
                ✅ <?= session()->getFlashdata('success') ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- CV Style Layout -->
    <div class="max-w-5xl mx-auto px-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" id="dashboard-container">
            <!-- Header Section -->
            <div class="p-8 border-b border-gray-100">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div id="user-name-placeholder" class="skeleton h-10 w-64 mb-2 hide-on-load"></div>
                        <h1 id="user-name" class="text-4xl font-light text-gray-800 tracking-wide mb-1 data-content"></h1>
                        
                        <div id="user-major-placeholder" class="skeleton h-4 w-48 hide-on-load"></div>
                        <p id="user-major" class="text-sm text-gray-500 uppercase tracking-widest data-content"></p>
                    </div>
                    <div id="user-avatar-container">
                        <div class="skeleton w-24 h-24 rounded-full hide-on-load"></div>
                        <div id="user-avatar" class="data-content"></div>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="flex">
                <!-- Left Column -->
                <div class="w-1/3 p-8 bg-gray-50 border-r border-gray-100">
                    <!-- Contact -->
                    <div class="mb-8">
                        <h3 class="section-title">Contact</h3>
                        <div id="contact-skeleton" class="space-y-3 hide-on-load">
                            <div class="skeleton h-4 w-full"></div>
                            <div class="skeleton h-4 w-3/4"></div>
                            <div class="skeleton h-4 w-5/6"></div>
                        </div>
                        <div id="contact-info" class="space-y-3 text-sm data-content"></div>
                    </div>

                    <!-- Key Metrics -->
                    <div class="mb-8">
                        <h3 class="section-title">Statistics</h3>
                        <div id="stats-skeleton" class="space-y-3 hide-on-load">
                            <div class="flex justify-between"><div class="skeleton h-4 w-20"></div><div class="skeleton h-4 w-8"></div></div>
                            <div class="flex justify-between"><div class="skeleton h-4 w-20"></div><div class="skeleton h-4 w-8"></div></div>
                            <div class="flex justify-between"><div class="skeleton h-4 w-20"></div><div class="skeleton h-4 w-8"></div></div>
                        </div>
                        <div id="stats-info" class="space-y-3 data-content"></div>
                    </div>

                    <!-- Education -->
                    <div class="mb-8" id="education-section">
                        <h3 class="section-title">Education</h3>
                        <div id="education-skeleton" class="space-y-4 hide-on-load">
                            <div><div class="skeleton h-4 w-3/4 mb-1"></div><div class="skeleton h-3 w-1/2"></div></div>
                            <div><div class="skeleton h-4 w-2/3 mb-1"></div><div class="skeleton h-3 w-1/3"></div></div>
                        </div>
                        <div id="education-list" class="space-y-3 data-content"></div>
                    </div>

                    <!-- Skills/Expertise -->
                    <div class="mb-8" id="expertise-section">
                        <h3 class="section-title">Expertise</h3>
                        <div id="expertise-skeleton" class="space-y-2 hide-on-load">
                            <div class="skeleton h-4 w-1/2"></div>
                            <div class="skeleton h-4 w-2/3"></div>
                            <div class="skeleton h-4 w-1/3"></div>
                        </div>
                        <div id="expertise-list" class="space-y-2 data-content"></div>
                    </div>

                    <!-- Publication Types -->
                    <div id="types-section">
                        <h3 class="section-title">By Type</h3>
                        <div id="types-skeleton" class="space-y-2 hide-on-load">
                            <div class="flex justify-between"><div class="skeleton h-4 w-24"></div><div class="skeleton h-4 w-6"></div></div>
                            <div class="flex justify-between"><div class="skeleton h-4 w-24"></div><div class="skeleton h-4 w-6"></div></div>
                        </div>
                        <div id="types-list" class="space-y-2 data-content"></div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="w-2/3 p-8">
                    <!-- Professional Profile -->
                    <div class="mb-8">
                        <h3 class="section-title">Professional Profile</h3>
                        <div id="bio-skeleton" class="space-y-2 hide-on-load">
                            <div class="skeleton h-4 w-full"></div>
                            <div class="skeleton h-4 w-full"></div>
                            <div class="skeleton h-4 w-2/3"></div>
                        </div>
                        <p id="user-bio" class="text-gray-600 leading-relaxed data-content"></p>
                    </div>

                    <!-- Recent Publications -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="section-title mb-0 border-0 pb-0">Recent Publications</h3>
                            <a href="<?= base_url('index.php/publications') ?>" class="text-xs text-gray-500 hover:text-gray-700">View all →</a>
                        </div>

                        <div id="publications-skeleton" class="space-y-6 hide-on-load">
                            <?php for($i=0; $i<3; $i++): ?>
                            <div class="border-l-2 border-gray-100 pl-4">
                                <div class="skeleton h-3 w-32 mb-2"></div>
                                <div class="skeleton h-5 w-full mb-1"></div>
                                <div class="skeleton h-4 w-3/4"></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <div id="publications-list" class="space-y-6 data-content"></div>
                    </div>

                    <!-- Publication Timeline -->
                    <div id="timeline-section">
                        <h3 class="section-title">Publication Timeline</h3>
                        <div id="timeline-skeleton" class="space-y-3 hide-on-load">
                            <div class="skeleton h-4 w-full"></div>
                            <div class="skeleton h-4 w-full"></div>
                            <div class="skeleton h-4 w-full"></div>
                        </div>
                        <div id="timeline-list" class="space-y-3 data-content"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetchDashboardData();
        });

        async function fetchDashboardData() {
            try {
                const response = await fetch(window.location.href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) throw new Error('Network response was not ok');
                
                const result = await response.json();
                if (result.success) {
                    renderDashboard(result.data);
                }
            } catch (error) {
                console.error('Error fetching dashboard data:', error);
                // Handle error state
            }
        }

        function renderDashboard(data) {
            const { user, user_profile, user_stats, publication_stats, recent_publications, cv_sections, extra } = data;

            // Header info
            document.getElementById('user-name').textContent = (user.name || 'RESEARCHER').toUpperCase();
            document.getElementById('user-major').textContent = user.major || 'Faculty Member';
            
            // Avatar
            const avatarContainer = document.getElementById('user-avatar');
            if (user.profile_picture) {
                const profilePic = user.profile_picture.replace(/^https:/i, 'http:');
                avatarContainer.innerHTML = `<img src="${profilePic}" class="w-24 h-24 rounded-full object-cover border-4 border-gray-100">`;
            } else {
                const initial = (user.name || 'U').substring(0, 1).toUpperCase();
                avatarContainer.innerHTML = `
                    <div class="w-24 h-24 rounded-full bg-gradient-to-br from-gray-300 to-gray-400 flex items-center justify-center text-white text-3xl font-light">
                        ${initial}
                    </div>`;
            }

            // Contact
            const contactInfo = document.getElementById('contact-info');
            let contactHtml = `
                <div class="flex items-center gap-3">
                    <span class="text-gray-400">📧</span>
                    <span class="text-gray-700">${user.email || '-'}</span>
                </div>
            `;
            if (user_profile.phone) {
                contactHtml += `
                    <div class="flex items-center gap-3">
                        <span class="text-gray-400">📞</span>
                        <span class="text-gray-700">${user_profile.phone}</span>
                    </div>
                `;
            }
            contactHtml += `
                <div class="flex items-center gap-3">
                    <span class="text-gray-400">📍</span>
                    <span class="text-gray-700">${user_profile.institution || 'Uttaradit Rajabhat University'}</span>
                </div>
            `;
            if (user_profile.orcid_id) {
                contactHtml += `
                    <div class="flex items-center gap-3">
                        <span class="text-gray-400">🔗</span>
                        <span class="text-gray-700">ORCID: ${user_profile.orcid_id}</span>
                    </div>
                `;
            }
            contactInfo.innerHTML = contactHtml;

            // Stats
            const statsInfo = document.getElementById('stats-info');
            const thisYearCount = (publication_stats.by_year && publication_stats.by_year[0]) ? publication_stats.by_year[0].count : 0;
            statsInfo.innerHTML = `
                <div class="flex items-center justify-between">
                    <span class="text-gray-600 text-sm">Publications</span>
                    <span class="font-semibold text-gray-800">${user_stats.publications || 0}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600 text-sm">Co-Authors</span>
                    <span class="font-semibold text-gray-800">${user_stats.authors || 0}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600 text-sm">This Year</span>
                    <span class="font-semibold text-gray-800">${thisYearCount}</span>
                </div>
            `;

            // Education
            const eduSection = cv_sections.find(s => s.type === 'education');
            if (eduSection && eduSection.entries && eduSection.entries.length > 0) {
                const eduList = document.getElementById('education-list');
                eduList.innerHTML = eduSection.entries.map(edu => `
                    <div>
                        <p class="font-medium text-gray-800 text-sm">${edu.title}</p>
                        ${edu.organization ? `<p class="text-xs text-gray-500">${edu.organization}</p>` : ''}
                        ${(edu.start_date || edu.end_date) ? `
                            <p class="text-xs text-gray-400">
                                ${edu.start_date ? new Date(edu.start_date).getFullYear() : ''}
                                ${edu.start_date && (edu.end_date || edu.is_current) ? ' - ' : ''}
                                ${edu.end_date ? new Date(edu.end_date).getFullYear() : (edu.is_current ? 'Present' : '')}
                            </p>
                        ` : ''}
                    </div>
                `).join('');
            } else {
                document.getElementById('education-section').style.display = 'none';
            }

            // Expertise
            if (user_profile.expertise) {
                const skills = user_profile.expertise.split(',').map(s => s.trim()).filter(s => s);
                if (skills.length > 0) {
                    document.getElementById('expertise-list').innerHTML = skills.map(skill => `
                        <div class="text-sm text-gray-700">${skill}</div>
                    `).join('');
                } else {
                    document.getElementById('expertise-section').style.display = 'none';
                }
            } else {
                document.getElementById('expertise-section').style.display = 'none';
            }

            // Publication Types
            if (publication_stats.by_type && publication_stats.by_type.length > 0) {
                document.getElementById('types-list').innerHTML = publication_stats.by_type.map(type => `
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">${capitalizeFirstLetter(type.publication_type)}</span>
                        <span class="text-gray-800 font-medium">${type.count}</span>
                    </div>
                `).join('');
            } else {
                document.getElementById('types-section').style.display = 'none';
            }

            // Bio
            document.getElementById('user-bio').textContent = user_profile.bio || 'เพิ่มประวัติย่อในหน้าตั้งค่าเพื่อแสดงในหน้า Dashboard และ CV';

            // Recent Publications
            if (recent_publications && recent_publications.length > 0) {
                document.getElementById('publications-list').innerHTML = recent_publications.slice(0, 4).map(pub => `
                    <div class="border-l-2 border-gray-200 pl-4">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-medium text-gray-500 uppercase">${pub.publication_type || 'Publication'}</span>
                            <span class="text-gray-300">|</span>
                            <span class="text-xs text-gray-500">${pub.publication_year || ''}</span>
                        </div>
                        <h4 class="font-medium text-gray-800 mb-1">${pub.title || 'Untitled'}</h4>
                        ${pub.source ? `<p class="text-sm text-gray-500">${pub.source}</p>` : ''}
                    </div>
                `).join('');
            } else {
                document.getElementById('publications-list').innerHTML = '<p class="text-gray-400 text-sm">ยังไม่มีผลงาน</p>';
            }

            // Timeline
            if (publication_stats.by_year && publication_stats.by_year.length > 0) {
                document.getElementById('timeline-list').innerHTML = publication_stats.by_year.slice(0, 5).map(item => {
                    const barWidth = Math.min(100, Math.max(5, parseInt(item.count || 0) * 20));
                    return `
                        <div class="flex items-center gap-4">
                            <span class="w-12 text-sm font-medium text-gray-700">${item.publication_year}</span>
                            <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-gray-400 rounded-full" style="width: ${barWidth}%;"></div>
                            </div>
                            <span class="w-8 text-sm text-gray-600 text-right">${item.count}</span>
                        </div>
                    `;
                }).join('');
            } else {
                document.getElementById('timeline-section').style.display = 'none';
            }

            // Admin button
            if (extra && extra.show_admin_button) {
                const adminBtn = document.createElement('a');
                adminBtn.href = "<?= base_url('index.php/admin/dashboard') ?>";
                adminBtn.className = "px-3 py-1.5 bg-emerald-600 text-sm rounded-lg hover:bg-emerald-700 font-medium";
                adminBtn.style = "color: #ffffff !important; text-decoration: none !important; display: inline-block; white-space: nowrap; background-color: #059669 !important;";
                adminBtn.textContent = "🛡️ เข้าสู่ระบบจัดการ";
                document.getElementById('admin-button-container').appendChild(adminBtn);
            }

            // Set loaded state
            document.querySelectorAll('.data-content').forEach(el => el.classList.add('loaded'));
            document.getElementById('dashboard-container').classList.add('loaded');
        }

        function capitalizeFirstLetter(string) {
            if (!string) return '';
            return string.charAt(0).toUpperCase() + string.slice(1);
        }
    </script>
</body>

</html>