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

        .publication-card {
            transition: all 0.2s ease;
        }

        .publication-card:hover {
            background-color: #f8fafc;
        }

        .gradient-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            <div class="flex items-center justify-between">
                <a href="<?= base_url('index.php/faculty-search') ?>" 
                   class="text-white hover:text-purple-200 flex items-center transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    กลับไปหน้าค้นหา
                </a>
            </div>
        </div>
    </header>

    <!-- Teacher Profile Section -->
    <div class="bg-white shadow-lg -mt-4 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6">
                <!-- Profile Image -->
                <div class="flex-shrink-0">
                    <?php if (!empty($teacher['profile_picture'])): ?>
                        <img src="<?= str_replace('https:', 'http:', $teacher['profile_picture']) ?>" alt="Profile" 
                             class="w-32 h-32 rounded-full object-cover border-4 border-indigo-100 shadow-lg">
                    <?php else: ?>
                        <div class="w-32 h-32 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-4xl font-bold shadow-lg">
                            <?= mb_substr($teacher['name_thai'], 0, 1) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Teacher Info -->
                <div class="flex-1 text-center md:text-left">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?= esc($teacher['name_thai']) ?></h1>
                    <p class="text-lg text-gray-600"><?= esc($teacher['name_english']) ?></p>
                    
                    <div class="mt-4 flex flex-wrap justify-center md:justify-start gap-4">
                        <div class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <span><?= esc($teacher['faculty']) ?></span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            <span><?= esc($teacher['curriculum']) ?></span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span class="text-sm"><?= esc($teacher['email']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="flex-shrink-0 bg-indigo-50 rounded-xl p-6 text-center">
                    <div id="publication-count" class="text-4xl font-bold text-indigo-600">-</div>
                    <div class="text-gray-600 text-sm">ผลงานวิจัย</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Search and Filter -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" id="search-input" 
                        placeholder="ค้นหาชื่อผลงาน..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <select id="type-filter" class="w-full md:w-auto px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">ทุกประเภท</option>
                        <option value="journal">วารสาร (Journal)</option>
                        <option value="proceedings">ประชุมวิชาการ (Conference)</option>
                        <option value="book">หนังสือ (Book)</option>
                        <option value="thesis">วิทยานิพนธ์ (Thesis)</option>
                        <option value="report">รายงาน (Report)</option>
                        <option value="other">อื่นๆ (Other)</option>
                    </select>
                </div>
                <div>
                    <select id="year-filter" class="w-full md:w-auto px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">ทุกปี</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Publications List -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">📚 รายการผลงานวิจัย</h2>
            </div>
            
            <div id="publications-list" class="divide-y divide-gray-200">
                <!-- Loading State -->
                <div id="loading-state" class="flex justify-center items-center py-20">
                    <div class="text-center">
                        <div class="loading-spinner mx-auto mb-4"></div>
                        <p class="text-gray-600">กำลังโหลดข้อมูล...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div id="empty-state" class="hidden text-center py-20">
            <svg class="w-24 h-24 mx-auto text-gray-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">ยังไม่มีผลงานวิจัย</h3>
            <p class="text-gray-500">ยังไม่พบผลงานวิจัยของอาจารย์ท่านนี้ในระบบ</p>
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
        const TEACHER_UID = <?= $teacher['uid'] ?>;
        const API_KEY = '<?= esc(\App\Filters\ApiKeyFilter::getExpectedKey(), 'js') ?>';
        let allPublications = [];
        let filteredPublications = [];

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadPublications();

            // Event listeners
            document.getElementById('search-input').addEventListener('input', debounce(filterPublications, 300));
            document.getElementById('type-filter').addEventListener('change', filterPublications);
            document.getElementById('year-filter').addEventListener('change', filterPublications);
        });

        // Debounce function
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        // Load publications
        async function loadPublications() {
            try {
                const response = await fetch(`${BASE_URL}/index.php/faculty-search/publications/${TEACHER_UID}`, {
                    headers: { 'X-API-KEY': API_KEY }
                });
                const result = await response.json();
                
                if (result.success) {
                    allPublications = result.publications;
                    filteredPublications = [...allPublications];
                    
                    document.getElementById('publication-count').textContent = result.total;
                    
                    // Populate year filter
                    populateYearFilter();
                    
                    renderPublications();
                }
            } catch (error) {
                console.error('Error loading publications:', error);
                showError('เกิดข้อผิดพลาดในการโหลดข้อมูล');
            } finally {
                document.getElementById('loading-state').classList.add('hidden');
            }
        }

        // Populate year filter
        function populateYearFilter() {
            const years = [...new Set(allPublications.map(p => p.publication_year).filter(y => y))].sort((a, b) => b - a);
            const select = document.getElementById('year-filter');
            
            years.forEach(year => {
                const beYear = parseInt(year) + 543;
                const option = document.createElement('option');
                option.value = year;
                option.textContent = `พ.ศ. ${beYear} (${year})`;
                select.appendChild(option);
            });
        }

        // Filter publications
        function filterPublications() {
            const search = document.getElementById('search-input').value.toLowerCase();
            const type = document.getElementById('type-filter').value;
            const year = document.getElementById('year-filter').value;

            filteredPublications = allPublications.filter(pub => {
                const matchesSearch = !search ||
                    (pub.title && pub.title.toLowerCase().includes(search)) ||
                    (pub.authors && pub.authors.toLowerCase().includes(search));

                const matchesType = !type || pub.publication_type === type;
                const matchesYear = !year || pub.publication_year == year;

                return matchesSearch && matchesType && matchesYear;
            });

            renderPublications();
        }

        // Render publications list
        function renderPublications() {
            const list = document.getElementById('publications-list');
            const emptyState = document.getElementById('empty-state');

            if (filteredPublications.length === 0) {
                list.innerHTML = '';
                emptyState.classList.remove('hidden');
                return;
            }

            emptyState.classList.add('hidden');
            list.innerHTML = filteredPublications.map((pub, index) => `
                <div class="publication-card px-6 py-5">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-medium mr-4">
                            ${index + 1}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">${escapeHtml(pub.title)}</h3>
                            
                            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600 mb-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getTypeColorClass(pub.publication_type)}">
                                    ${getPublicationTypeThai(pub.publication_type)}
                                </span>
                                ${pub.publication_year ? `
                                    <span class="inline-flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        พ.ศ. ${parseInt(pub.publication_year) + 543}
                                    </span>
                                ` : ''}
                            </div>

                            ${pub.source ? `
                                <p class="text-gray-600 mb-2">
                                    <span class="font-medium">แหล่งตีพิมพ์:</span> ${escapeHtml(pub.source)}
                                </p>
                            ` : ''}

                            ${pub.authors || pub.authors_names_thai ? `
                                <p class="text-gray-500 text-sm">
                                    <span class="font-medium">ผู้แต่ง:</span> ${escapeHtml(pub.authors || pub.authors_names_thai)}
                                </p>
                            ` : ''}

                            ${pub.doi ? `
                                <p class="text-sm mt-2">
                                    <span class="font-medium text-gray-600">DOI:</span> 
                                    <a href="https://doi.org/${pub.doi}" target="_blank" class="text-indigo-600 hover:underline">${escapeHtml(pub.doi)}</a>
                                </p>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Get publication type in Thai
        function getPublicationTypeThai(type) {
            const typeMap = {
                'journal': 'วารสาร',
                'proceedings': 'ประชุมวิชาการ',
                'book': 'หนังสือ',
                'thesis': 'วิทยานิพนธ์',
                'report': 'รายงาน',
                'other': 'อื่นๆ'
            };
            return typeMap[type] || type;
        }

        // Get color class for type badge
        function getTypeColorClass(type) {
            const colorMap = {
                'journal': 'bg-blue-100 text-blue-800',
                'proceedings': 'bg-purple-100 text-purple-800',
                'book': 'bg-green-100 text-green-800',
                'thesis': 'bg-yellow-100 text-yellow-800',
                'report': 'bg-orange-100 text-orange-800',
                'other': 'bg-gray-100 text-gray-800'
            };
            return colorMap[type] || 'bg-gray-100 text-gray-800';
        }

        // Show error
        function showError(message) {
            const list = document.getElementById('publications-list');
            list.innerHTML = `
                <div class="text-center py-20">
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
