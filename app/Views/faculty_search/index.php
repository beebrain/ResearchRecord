<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>

    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <!-- Google Font (Sarabun) -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Sarabun', sans-serif;
        }

        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .teacher-card {
            transition: all 0.3s ease;
        }

        .teacher-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .gradient-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
        }
    </style>
</head>

<body class="min-h-full bg-gray-50">
    <?php if (! \App\Filters\ApiKeyFilter::isConfigured()): ?>
        <div class="bg-red-600 text-white text-center text-sm py-2 px-4">
            ยังไม่ได้ตั้งค่า <code class="bg-red-800 px-1 rounded">RESEARCH_API_KEY</code> หรือ <code class="bg-red-800 px-1 rounded">API_KEY</code> ใน <code class="bg-red-800 px-1 rounded">.env</code> — การโหลดข้อมูลผ่าน API จะไม่ทำงาน
        </div>
    <?php endif; ?>
    <!-- Header -->
    <header class="gradient-header text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center">
                <h1 class="text-3xl md:text-4xl font-bold mb-2">🎓 ค้นหาผลงานอาจารย์</h1>
                <p class="text-lg text-purple-100">Faculty Publication Search</p>
                <p class="mt-2 text-purple-200">ค้นหาผลงานวิจัยและบทความของอาจารย์ในมหาวิทยาลัย</p>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Search & Filter Section -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search Input -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">ค้นหาชื่ออาจารย์</label>
                    <div class="relative">
                        <input type="text" id="search-input" 
                            placeholder="พิมพ์ชื่อ-นามสกุล หรือ Email..."
                            class="search-input w-full px-4 py-3 pl-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <svg class="absolute left-4 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Faculty Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">คณะ</label>
                    <select id="faculty-filter" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">ทุกคณะ</option>
                    </select>
                </div>

                <!-- Curriculum Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">หลักสูตร</label>
                    <select id="curriculum-filter" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">ทุกหลักสูตร</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="flex items-center justify-between mb-6">
            <div class="text-gray-600">
                พบ <span id="total-count" class="font-bold text-indigo-600">0</span> อาจารย์
            </div>
        </div>

        <!-- Teachers Grid -->
        <div id="teachers-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Loading State -->
            <div id="loading-state" class="col-span-full flex justify-center items-center py-20">
                <div class="text-center">
                    <div class="loading-spinner mx-auto mb-4"></div>
                    <p class="text-gray-600">กำลังโหลดข้อมูล...</p>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div id="empty-state" class="hidden text-center py-20">
            <svg class="w-24 h-24 mx-auto text-gray-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">ไม่พบอาจารย์</h3>
            <p class="text-gray-500">ลองค้นหาใหม่หรือเปลี่ยนตัวกรอง</p>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-gray-400">© <?= date('Y') ?> Research Publication System</p>
            <p class="text-sm text-gray-500 mt-2">มหาวิทยาลัยราชภัฏอุตรดิตถ์</p>
        </div>
    </footer>

    <script>
        const BASE_URL = '<?= rtrim(base_url(), '/') ?>';
        const API_KEY = '<?= esc(\App\Filters\ApiKeyFilter::getExpectedKey(), 'js') ?>';
        let allTeachers = [];
        let filteredTeachers = [];

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadFaculties();
            loadTeachers();

            // Event listeners
            document.getElementById('search-input').addEventListener('input', debounce(filterTeachers, 300));
            document.getElementById('faculty-filter').addEventListener('change', function() {
                loadCurricula(this.value);
                filterTeachers();
            });
            document.getElementById('curriculum-filter').addEventListener('change', filterTeachers);
        });

        // Debounce function
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        // Load faculties for filter
        async function loadFaculties() {
            try {
                const response = await fetch(`${BASE_URL}/index.php/faculty-search/faculties`, {
                    headers: { 'X-API-KEY': API_KEY }
                });
                const result = await response.json();
                
                if (result.success) {
                    const select = document.getElementById('faculty-filter');
                    result.data.forEach(faculty => {
                        const option = document.createElement('option');
                        option.value = faculty.id;
                        option.textContent = faculty.name;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading faculties:', error);
            }
        }

        // Load curricula based on faculty
        async function loadCurricula(facultyId) {
            try {
                const select = document.getElementById('curriculum-filter');
                select.innerHTML = '<option value="">ทุกหลักสูตร</option>';

                if (!facultyId) return;

                const response = await fetch(`${BASE_URL}/index.php/faculty-search/curricula?faculty_id=${facultyId}`, {
                    headers: { 'X-API-KEY': API_KEY }
                });
                const result = await response.json();
                
                if (result.success) {
                    result.data.forEach(curriculum => {
                        const option = document.createElement('option');
                        option.value = curriculum.id;
                        option.textContent = curriculum.name;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading curricula:', error);
            }
        }

        // Load teachers
        async function loadTeachers() {
            try {
                showLoading(true);
                
                const response = await fetch(`${BASE_URL}/index.php/faculty-search/teachers`, {
                    headers: { 'X-API-KEY': API_KEY }
                });
                const result = await response.json();
                
                if (result.success) {
                    allTeachers = result.data;
                    filteredTeachers = [...allTeachers];
                    renderTeachers();
                }
            } catch (error) {
                console.error('Error loading teachers:', error);
                showError('เกิดข้อผิดพลาดในการโหลดข้อมูล');
            } finally {
                showLoading(false);
            }
        }

        // Filter teachers
        function filterTeachers() {
            const search = document.getElementById('search-input').value.toLowerCase();
            const facultyId = document.getElementById('faculty-filter').value;
            const curriculumId = document.getElementById('curriculum-filter').value;

            filteredTeachers = allTeachers.filter(teacher => {
                const matchesSearch = !search ||
                    teacher.name_thai.toLowerCase().includes(search) ||
                    teacher.name_english.toLowerCase().includes(search) ||
                    teacher.email.toLowerCase().includes(search);

                const matchesFaculty = !facultyId || teacher.faculty_id == facultyId;
                const matchesCurriculum = !curriculumId || teacher.curriculum_id == curriculumId;

                return matchesSearch && matchesFaculty && matchesCurriculum;
            });

            renderTeachers();
        }

        // Render teachers grid
        function renderTeachers() {
            const grid = document.getElementById('teachers-grid');
            const emptyState = document.getElementById('empty-state');
            
            document.getElementById('total-count').textContent = filteredTeachers.length;

            if (filteredTeachers.length === 0) {
                grid.innerHTML = '';
                emptyState.classList.remove('hidden');
                return;
            }

            emptyState.classList.add('hidden');
            grid.innerHTML = filteredTeachers.map(teacher => `
                <a href="${BASE_URL}/index.php/faculty-search/view/${teacher.uid}" 
                   class="teacher-card bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl">
                    <div class="p-6">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                ${teacher.profile_picture ? 
                                    `<img src="${teacher.profile_picture.replace(/^https:/i, 'http:')}" alt="Profile" class="w-16 h-16 rounded-full object-cover border-2 border-indigo-100">` :
                                    `<div class="w-16 h-16 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xl font-bold">
                                        ${teacher.name_thai.charAt(0) || teacher.name_english.charAt(0) || '?'}
                                    </div>`
                                }
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-semibold text-gray-900 truncate">${escapeHtml(teacher.name_thai)}</h3>
                                <p class="text-sm text-gray-500 truncate">${escapeHtml(teacher.name_english)}</p>
                            </div>
                        </div>
                        
                        <div class="mt-4 space-y-2">
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <span class="truncate">${escapeHtml(teacher.faculty)}</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <span class="truncate text-xs">${escapeHtml(teacher.email)}</span>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                                📚 ${teacher.publication_count} ผลงาน
                            </span>
                            <span class="text-indigo-600 text-sm font-medium flex items-center">
                                ดูผลงาน
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </span>
                        </div>
                    </div>
                </a>
            `).join('');
        }

        // Show/hide loading
        function showLoading(show) {
            const loading = document.getElementById('loading-state');
            if (show) {
                loading.classList.remove('hidden');
            } else {
                loading.classList.add('hidden');
            }
        }

        // Show error
        function showError(message) {
            const grid = document.getElementById('teachers-grid');
            grid.innerHTML = `
                <div class="col-span-full text-center py-20">
                    <div class="text-red-500 text-xl mb-4">⚠️</div>
                    <p class="text-red-600">${escapeHtml(message)}</p>
                </div>
            `;
        }

        // Escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
