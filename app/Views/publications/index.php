<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผลงานวิจัยของฉัน - My Publications</title>

    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <!-- Google Font (Sarabun) -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Modal Handler -->
    <script src="<?= base_url('public/assets/js/modal-handler.js') ?>"></script>

    <!-- Author Search CSS -->
    <link rel="stylesheet" href="<?= base_url('public/assets/css/author-search.css') ?>">

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
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body class="min-h-full">
    <div class="min-h-full bg-gray-50">
        <!-- Top Navigation Bar (User Style) -->
        <nav class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">จัดการผลงานวิจัยของฉัน</h1>
                    <p class="text-sm text-gray-600">จัดการและแก้ไขผลงานวิจัยของคุณ</p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="<?= base_url('index.php/dashboard') ?>" class="text-sm text-gray-600 hover:text-gray-900">
                        ← กลับสู่แดชบอร์ด
                    </a>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-600 mb-2">ผลงานทั้งหมด</div>
                    <div id="total-count" class="text-3xl font-bold text-gray-900">-</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-600 mb-2">ผลงานวารสาร</div>
                    <div id="journal-count" class="text-3xl font-bold text-blue-600">-</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-600 mb-2">ผลงานประชุมวิชาการ</div>
                    <div id="conference-count" class="text-3xl font-bold text-purple-600">-</div>
                </div>
            </div>

            <!-- Filters and Actions -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex flex-col sm:flex-row gap-4 justify-between">
                    <div class="flex gap-4 flex-1">
                        <input
                            type="text"
                            id="search-input"
                            placeholder="ค้นหาชื่อผลงาน หรือชื่อผู้แต่ง..."
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">

                        <select id="type-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">ประเภทผลงานทั้งหมด</option>
                            <option value="journal">วารสาร (Journal)</option>
                            <option value="proceedings">ประชุมวิชาการ (Conference)</option>
                            <option value="book">หนังสือ (Book)</option>
                            <option value="thesis">วิทยานิพนธ์ (Thesis)</option>
                            <option value="report">รายงาน (Report)</option>
                            <option value="other">อื่นๆ (Other)</option>
                        </select>
                    </div>

                    <a href="<?= base_url('index.php/publications/create') ?>" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors inline-flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        เพิ่มผลงานใหม่
                    </a>
                </div>
            </div>

            <!-- Publications Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">ชื่อผลงาน</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">ผู้แต่ง</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">ประเภท</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">แหล่งตีพิมพ์</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">เดือน</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">ปี พ.ศ.</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">การดำเนินการ</th>
                            </tr>
                        </thead>
                        <tbody id="publications-table">
                            <!-- Loading state -->
                            <tr id="loading-row">
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex justify-center items-center gap-3">
                                        <div class="loading-spinner"></div>
                                        <span class="text-gray-600">กำลังโหลดข้อมูล...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <!-- Edit Publication Modal (Same as admin) -->
        <div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-70 overflow-y-auto py-10 px-4" style="max-height: 100vh;">
            <div class="modal-content relative w-full max-w-5xl bg-white rounded-2xl shadow-2xl" style="max-height: 90vh; display: flex; flex-direction: column; margin: auto;">
                <!-- Modal Header (Fixed) -->
                <div class="flex justify-between items-center px-8 py-6 border-b border-gray-100 flex-shrink-0">
                    <h2 class="text-2xl font-bold text-gray-900">แก้ไขผลงานวิจัย</h2>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 rounded-lg hover:bg-gray-100" title="ปิด (Esc)">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Edit Form (Scrollable) -->
                <form id="editForm" class="px-8 py-6 space-y-6 overflow-y-auto flex-1" style="max-height: calc(90vh - 180px); min-height: 0;">
                    <input type="hidden" id="edit_publication_id" name="publication_id">

                    <!-- Publication Type -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ประเภทผลงาน *</label>
                        <select id="edit_publication_type" name="publication_type" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">เลือกประเภทผลงาน</option>
                            <option value="journal">วารสาร (Journal)</option>
                            <option value="proceedings">ประชุมวิชาการ (Conference Proceedings)</option>
                            <option value="book">หนังสือ (Book)</option>
                            <option value="thesis">วิทยานิพนธ์ (Thesis)</option>
                            <option value="report">รายงาน (Report)</option>
                            <option value="other">อื่นๆ (Other)</option>
                        </select>
                    </div>

                    <!-- Title (Hidden - not editable) -->
                    <div class="mb-6 hidden">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อผลงาน (Title) *</label>
                        <input type="text" id="edit_title" name="title" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Authors Section -->
                    <div class="authors-section mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <label class="block text-sm font-medium text-gray-700">ผู้แต่ง/ผู้วิจัย *</label>
                            <button type="button" onclick="addEditAuthor()"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                เพิ่มผู้แต่ง
                            </button>
                        </div>
                        <div id="edit_authors_container" class="authors-container space-y-3"></div>
                    </div>

                    <!-- Abstract -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">บทคัดย่อ (Abstract)</label>
                        <textarea id="edit_abstract" name="abstract" rows="4"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>

                    <!-- Source / Journal Name -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">แหล่งตีพิมพ์/ชื่อวารสาร (Source) *</label>
                        <input type="text" id="edit_source" name="source" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Publication Year and Month -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ปีที่ตีพิมพ์ (Year) - พ.ศ.</label>
                            <input type="number" id="edit_publication_year" name="publication_year" min="2443" max="2643"
                                placeholder="พ.ศ. (เช่น 2567)"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">เดือน (Month)</label>
                            <select id="edit_publication_month" name="publication_month"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">เลือกเดือน</option>
                                <option value="1">มกราคม</option>
                                <option value="2">กุมภาพันธ์</option>
                                <option value="3">มีนาคม</option>
                                <option value="4">เมษายน</option>
                                <option value="5">พฤษภาคม</option>
                                <option value="6">มิถุนายน</option>
                                <option value="7">กรกฎาคม</option>
                                <option value="8">สิงหาคม</option>
                                <option value="9">กันยายน</option>
                                <option value="10">ตุลาคม</option>
                                <option value="11">พฤศจิกายน</option>
                                <option value="12">ธันวาคม</option>
                            </select>
                        </div>
                    </div>

                    <!-- Volume, Issue, Pages -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ปีที่/ฉบับที่ (Volume)</label>
                            <input type="text" id="edit_volume" name="volume"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ครั้งที่ (Issue)</label>
                            <input type="text" id="edit_issue" name="issue"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">หน้า (Pages)</label>
                            <input type="text" id="edit_pages" name="pages" placeholder="เช่น 123-145"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <!-- DOI and ISBN -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">DOI</label>
                            <input type="text" id="edit_doi" name="doi"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ISBN</label>
                            <input type="text" id="edit_isbn" name="isbn"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <!-- Publisher (for books) -->
                    <div class="mb-6 field-publisher hidden">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">สำนักพิมพ์ (Publisher)</label>
                        <input type="text" id="edit_publisher" name="publisher"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Conference fields -->
                    <div class="field-conference hidden">
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อการประชุม (Conference Name)</label>
                            <input type="text" id="edit_conference_name" name="conference_name"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">สถานที่ (Location)</label>
                                <input type="text" id="edit_conference_location" name="conference_location"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">วันที่จัด (Date)</label>
                                <input type="date" id="edit_conference_date" name="conference_date"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Book fields -->
                    <div class="field-book hidden">
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อหนังสือ (Book Title)</label>
                                <input type="text" id="edit_book_title" name="book_title"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">บทที่ (Chapter)</label>
                                <input type="text" id="edit_chapter" name="chapter"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">บรรณาธิการ (Editor)</label>
                            <input type="text" id="edit_editor" name="editor"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <!-- Keywords -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">คำสำคัญ (Keywords)</label>
                        <input type="text" id="edit_keywords" name="keywords" placeholder="คั่นด้วยเครื่องหมาย ,"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- URLs -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">URL อ้างอิง (Reference URL)</label>
                            <input type="url" id="edit_ref_url" name="ref_url"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">URL เพิ่มเติม (Additional URL)</label>
                            <input type="url" id="edit_url" name="url"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">หมายเหตุ (Notes)</label>
                        <textarea id="edit_notes" name="notes" rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>

                </form>
                <!-- Modal Footer (Fixed) -->
                <div class="flex gap-4 justify-end px-8 py-6 border-t border-gray-100 rounded-b-2xl flex-shrink-0 bg-white">
                    <button type="button" onclick="closeEditModal()"
                        class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors font-medium">
                        ยกเลิก
                    </button>
                    <button type="submit" form="editForm"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
                        บันทึกการแก้ไข
                    </button>
                </div>
            </div>
        </div>

        <script>
            const BASE_URL = '<?= rtrim(base_url(), '/') ?>';
            let allPublications = [];
            let filteredPublications = [];

            // Convert Christian year to Buddhist year with both formats: พ.ศ.(ค.ศ.)
            function toBuddhistYear(christianYear) {
                if (!christianYear) return '-';
                const beYear = parseInt(christianYear) + 543;
                return `${beYear}(${christianYear})`;
            }

            // Get Thai month name
            function getMonthNameThai(month) {
                if (!month) return '-';
                const monthNames = {
                    1: 'มกราคม',
                    2: 'กุมภาพันธ์',
                    3: 'มีนาคม',
                    4: 'เมษายน',
                    5: 'พฤษภาคม',
                    6: 'มิถุนายน',
                    7: 'กรกฎาคม',
                    8: 'สิงหาคม',
                    9: 'กันยายน',
                    10: 'ตุลาคม',
                    11: 'พฤศจิกายน',
                    12: 'ธันวาคม'
                };
                return monthNames[parseInt(month)] || '-';
            }

            // Map publication type to Thai name
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

            // Load publications data
            async function loadPublications() {
                try {
                    // Always filter by current user's ID, even in God mode
                    const currentUserId = <?= isset($user['uid']) ? (int)$user['uid'] : 'null' ?>;
                    const apiUrl = '<?= site_url('api/dashboard/publications') ?>?limit=1000&filter_user_id=' + currentUserId;
                    console.log('=== LOAD PUBLICATIONS START ===');
                    console.log('API URL:', apiUrl);
                    console.log('Filter User ID:', currentUserId);

                    const response = await fetch(apiUrl);
                    const result = await response.json();

                    if (result.success) {
                        allPublications = result.data;
                        filteredPublications = [...allPublications];
                        console.log('Total publications loaded:', allPublications.length);

                        updateStats();
                        renderTable();
                        console.log('=== LOAD PUBLICATIONS COMPLETE ===');
                    } else {
                        console.error('API returned success=false:', result.message);
                        showError('ไม่สามารถโหลดข้อมูลได้: ' + result.message);
                    }
                } catch (error) {
                    console.error('=== LOAD PUBLICATIONS ERROR ===');
                    console.error('Error:', error);
                    showError('เกิดข้อผิดพลาดในการโหลดข้อมูล');
                }
            }

            // Update statistics
            function updateStats() {
                const totalCount = allPublications.length;
                const journalCount = allPublications.filter(p => p.publication_type === 'journal').length;
                const conferenceCount = allPublications.filter(p => p.publication_type === 'proceedings').length;

                document.getElementById('total-count').textContent = totalCount;
                document.getElementById('journal-count').textContent = journalCount;
                document.getElementById('conference-count').textContent = conferenceCount;
            }

            // Filter publications
            function filterPublications() {
                const searchTerm = document.getElementById('search-input').value.toLowerCase();
                const typeFilter = document.getElementById('type-filter').value;

                filteredPublications = allPublications.filter(pub => {
                    const matchesSearch = !searchTerm ||
                        pub.title.toLowerCase().includes(searchTerm) ||
                        (pub.authors && pub.authors.toLowerCase().includes(searchTerm)) ||
                        (pub.authors_names_thai && pub.authors_names_thai.toLowerCase().includes(searchTerm)) ||
                        (pub.authors_names_en && pub.authors_names_en.toLowerCase().includes(searchTerm));

                    const matchesType = !typeFilter || pub.publication_type === typeFilter;

                    return matchesSearch && matchesType;
                });

                renderTable();
            }

            // Render publications table
            function renderTable() {
                const tbody = document.getElementById('publications-table');

                if (filteredPublications.length === 0) {
                    tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            ไม่พบผลงานวิจัย
                        </td>
                    </tr>
                `;
                    return;
                }

                tbody.innerHTML = filteredPublications.map((pub) => {
                    return `
                    <tr class="border-t border-gray-200 hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">${escapeHtml(pub.title || 'Untitled')}</div>
                        </td>
                        <td class="px-6 py-4 text-gray-700">
                            ${escapeHtml(pub.authors || pub.authors_names_thai || pub.authors_names_en || '-')}
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-medium rounded-full ${getTypeColorClass(pub.publication_type)}">
                                ${getPublicationTypeThai(pub.publication_type)}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-700">${escapeHtml(pub.source || '-')}</td>
                        <td class="px-6 py-4 text-gray-700">${getMonthNameThai(pub.publication_month)}</td>
                        <td class="px-6 py-4 text-gray-700">${toBuddhistYear(pub.publication_year)}</td>
                        <td class="px-6 py-4">
                            <div class="flex gap-2">
                                <button
                                    onclick="editPublication(${pub.id})"
                                    class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded transition-colors">
                                    แก้ไข
                                </button>
                                <button
                                    onclick="deletePublication(${pub.id}, '${escapeHtml(pub.title || 'Untitled')}')"
                                    class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm rounded transition-colors">
                                    ลบ
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                }).join('');
            }

            // Get color class for publication type badge
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

            // Escape HTML to prevent XSS
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Edit publication
            async function editPublication(id) {
                try {
                    // Show loading
                    Swal.fire({
                        title: 'กำลังโหลดข้อมูล...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Fetch publication data - use user route
                    const apiUrl = '<?= site_url('publications/get') ?>/' + id;
                    const response = await fetch(apiUrl);
                    const result = await response.json();

                    if (!result.success) {
                        throw new Error(result.message || 'ไม่สามารถโหลดข้อมูลได้');
                    }

                    // Close loading and open edit modal
                    Swal.close();
                    openEditModal(result.data);
                } catch (error) {
                    console.error('Edit publication error:', error);
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด!',
                        text: error.message || 'ไม่สามารถโหลดข้อมูลผลงานได้',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                }
            }

            // Delete publication with confirmation
            async function deletePublication(id, title) {
                const result = await Swal.fire({
                    title: 'ยืนยันการลบ?',
                    html: `คุณต้องการลบผลงานนี้หรือไม่?<br><br><strong>${escapeHtml(title)}</strong>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'ลบ',
                    cancelButtonText: 'ยกเลิก',
                    reverseButtons: true
                });

                if (result.isConfirmed) {
                    try {
                        // Show loading
                        Swal.fire({
                            title: 'กำลังลบ...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Use user route for delete
                        // Use site_url() to ensure correct URL without /public/ prefix
                        const apiUrl = '<?= site_url('publications/delete') ?>/' + id;
                        console.log('=== DELETE PUBLICATION START ===');
                        console.log('Delete Publication - ID:', id);
                        console.log('Delete Publication - API URL:', apiUrl);
                        console.log('Delete Publication - Method: DELETE (will fallback to POST if needed)');

                        // Try DELETE first, fallback to POST if server doesn't support DELETE
                        let response;
                        try {
                            response = await fetch(apiUrl, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json'
                                }
                            });

                            // If DELETE returns 404 or 405, try POST as fallback
                            if (response.status === 404 || response.status === 405) {
                                console.log('Delete Publication - DELETE method not supported, trying POST fallback');
                                response = await fetch(apiUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-HTTP-Method-Override': 'DELETE'
                                    }
                                });
                            }
                        } catch (error) {
                            console.log('Delete Publication - DELETE method failed, trying POST fallback:', error);
                            // Fallback to POST if DELETE fails
                            response = await fetch(apiUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-HTTP-Method-Override': 'DELETE'
                                }
                            });
                        }

                        console.log('Delete Publication - Response Status:', response.status);
                        console.log('Delete Publication - Response OK:', response.ok);
                        console.log('Delete Publication - Response Headers:', response.headers);

                        const deleteResult = await response.json();
                        console.log('Delete Publication - Response JSON:', deleteResult);
                        console.log('Delete Publication - Success:', deleteResult.success);
                        console.log('Delete Publication - Message:', deleteResult.message);

                        if (deleteResult.success) {
                            console.log('Delete Publication - SUCCESS');
                            await Swal.fire({
                                title: 'ลบสำเร็จ!',
                                text: 'ผลงานถูกลบเรียบร้อยแล้ว',
                                icon: 'success',
                                confirmButtonText: 'ตกลง'
                            });

                            // Reload publications
                            await loadPublications();
                            console.log('=== DELETE PUBLICATION COMPLETE ===');
                        } else {
                            console.error('Delete Publication - FAILED:', deleteResult.message);
                            throw new Error(deleteResult.message || 'ไม่สามารถลบได้');
                        }
                    } catch (error) {
                        console.error('=== DELETE PUBLICATION ERROR ===');
                        console.error('Delete Publication - Error:', error);
                        console.error('Delete Publication - Error Name:', error.name);
                        console.error('Delete Publication - Error Message:', error.message);
                        console.error('Delete Publication - Error Stack:', error.stack);
                        Swal.fire({
                            title: 'เกิดข้อผิดพลาด!',
                            text: error.message || 'ไม่สามารถลบผลงานได้',
                            icon: 'error',
                            confirmButtonText: 'ตกลง'
                        });
                    }
                }
            }

            // Show error message
            function showError(message) {
                const tbody = document.getElementById('publications-table');
                tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <div class="text-red-600">${escapeHtml(message)}</div>
                    </td>
                </tr>
            `;
            }

            // ============================================================================
            // EDIT MODAL FUNCTIONS (Same as admin)
            // ============================================================================

            let editAuthorsCount = 0;

            // Open edit modal
            function openEditModal(publication) {
                console.log('=== openEditModal START ===');
                console.log('Publication:', publication);

                try {
                    // Set publication ID
                    document.getElementById('edit_publication_id').value = publication.id;

                    // Set basic fields
                    document.getElementById('edit_publication_type').value = publication.publication_type || '';
                    document.getElementById('edit_title').value = publication.title || '';
                    document.getElementById('edit_abstract').value = publication.abstract || '';
                    document.getElementById('edit_source').value = publication.source || '';
                    // Convert CE year to BE year for display
                    const ceYear = publication.publication_year;
                    document.getElementById('edit_publication_year').value = ceYear ? parseInt(ceYear) + 543 : '';
                    document.getElementById('edit_publication_month').value = publication.publication_month || '';
                    document.getElementById('edit_volume').value = publication.volume || '';
                    document.getElementById('edit_issue').value = publication.issue || '';
                    document.getElementById('edit_pages').value = publication.pages || '';
                    document.getElementById('edit_doi').value = publication.doi || '';
                    document.getElementById('edit_isbn').value = publication.isbn || '';
                    document.getElementById('edit_publisher').value = publication.publisher || '';
                    document.getElementById('edit_conference_name').value = publication.conference_name || '';
                    document.getElementById('edit_conference_location').value = publication.conference_location || '';
                    document.getElementById('edit_conference_date').value = publication.conference_date || '';
                    document.getElementById('edit_book_title').value = publication.book_title || '';
                    document.getElementById('edit_chapter').value = publication.chapter || '';
                    document.getElementById('edit_editor').value = publication.editor || '';
                    document.getElementById('edit_keywords').value = publication.keywords || '';
                    document.getElementById('edit_ref_url').value = publication.ref_url || '';
                    document.getElementById('edit_url').value = publication.url || '';
                    document.getElementById('edit_notes').value = publication.notes || '';

                    // Clear and add authors
                    document.getElementById('edit_authors_container').innerHTML = '';
                    editAuthorsCount = 0;

                    if (publication.authors && publication.authors.length > 0) {
                        publication.authors.forEach(author => {
                            addEditAuthor(author);
                        });
                    } else {
                        addEditAuthor(); // Add one empty author field
                    }

                    // Update conditional fields based on publication type
                    updateEditConditionalFields();

                    // Show modal
                    console.log('Showing edit modal...');
                    const editModal = document.getElementById('editModal');
                    if (editModal) {
                        editModal.style.display = 'flex';
                        editModal.classList.remove('hidden');
                        document.body.style.overflow = 'hidden';
                    }
                    console.log('Edit modal shown successfully');
                    console.log('=== openEditModal COMPLETE ===');
                } catch (error) {
                    console.error('=== openEditModal ERROR ===');
                    console.error('Error:', error);
                    throw error;
                }
            }

            // Close edit modal
            function closeEditModal() {
                const editModal = document.getElementById('editModal');
                if (editModal) {
                    editModal.classList.add('hidden');
                    editModal.style.display = 'none';
                }
                document.body.style.overflow = '';
                // Reset form
                const form = document.getElementById('editForm');
                if (form) {
                    form.reset();
                }
            }

            // Close modal on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const editModal = document.getElementById('editModal');
                    if (editModal && !editModal.classList.contains('hidden')) {
                        closeEditModal();
                    }
                }
            });

            // Close modal when clicking outside
            document.getElementById('editModal')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeEditModal();
                }
            });

            // Update conditional fields based on publication type
            function updateEditConditionalFields() {
                const pubType = document.getElementById('edit_publication_type').value;

                // Hide all conditional fields first
                document.querySelectorAll('.field-publisher, .field-conference, .field-book').forEach(el => {
                    el.classList.add('hidden');
                });

                // Show relevant fields based on type
                if (pubType === 'book') {
                    document.querySelector('.field-publisher').classList.remove('hidden');
                    document.querySelector('.field-book').classList.remove('hidden');
                } else if (pubType === 'proceedings') {
                    document.querySelector('.field-conference').classList.remove('hidden');
                }
            }

            // Add author field
            function addEditAuthor(authorData = null) {
                editAuthorsCount++;
                const container = document.getElementById('edit_authors_container');
                const authorId = 'edit_author_' + editAuthorsCount;

                const authorDiv = document.createElement('div');
                authorDiv.className = 'author-row';
                authorDiv.id = authorId;

                authorDiv.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                    <div class="input-group">
                        <input type="text"
                               name="authors[${editAuthorsCount}][name]"
                               value="${authorData ? escapeHtml(authorData.author_name || '') : ''}"
                               placeholder="ชื่อผู้แต่ง"
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="input-group">
                        <input type="email"
                               name="authors[${editAuthorsCount}][email]"
                               value="${authorData ? escapeHtml(authorData.author_email || '') : ''}"
                               placeholder="อีเมล (ไม่บังคับ)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="input-group flex">
                        <input type="text"
                               name="authors[${editAuthorsCount}][affiliation]"
                               value="${authorData ? escapeHtml(authorData.author_affiliation || '') : 'มหาวิทยาลัยราชภัฏอุตรดิตถ์'}"
                               placeholder="หน่วยงาน (ไม่บังคับ)"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <button type="button"
                                onclick="removeEditAuthor('${authorId}')"
                                class="ml-2 px-2 py-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <!-- Corresponding Author Checkbox -->
                <div class="flex items-center ml-1 mb-3">
                    <input type="checkbox"
                           name="authors[${editAuthorsCount}][corresponding]"
                           value="1"
                           ${authorData && (authorData.corresponding == 1 || authorData.corresponding === '1' || authorData.corresponding === true) ? 'checked' : ''}
                           class="corresponding-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                    <label class="ml-2 text-sm font-medium text-gray-700">
                        ผู้แต่งที่ติดต่อได้ (Corresponding Author)
                    </label>
                </div>
            `;

                container.appendChild(authorDiv);

                // Reinitialize author search for the new input after a small delay
                setTimeout(() => {
                    if (window.AuthorNameSearch) {
                        const $newInput = $(authorDiv).find('input[name*="[name]"]');
                        window.AuthorNameSearch.setupSingleNameInput($newInput);
                    }
                }, 100);
            }

            // Remove author field
            function removeEditAuthor(authorId) {
                const element = document.getElementById(authorId);
                if (element) {
                    element.remove();
                }
            }

            // Handle form submission
            document.getElementById('editForm').addEventListener('submit', async (e) => {
                e.preventDefault();

                const formData = new FormData(e.target);
                const publicationId = formData.get('publication_id');

                // Remove title from form data (not editable)
                formData.delete('title');

                // Collect authors data
                const authors = [];
                const authorNames = document.querySelectorAll('input[name*="[name]"]');

                authorNames.forEach((nameInput) => {
                    const container = nameInput.closest('.author-row');
                    const emailInput = container.querySelector('input[name*="[email]"]');
                    const affiliationInput = container.querySelector('input[name*="[affiliation]"]');
                    const correspondingInput = container.querySelector('input[name*="[corresponding]"]');

                    if (nameInput.value.trim()) {
                        const authorData = {
                            name: nameInput.value.trim(),
                            email: emailInput.value.trim(),
                            affiliation: affiliationInput.value.trim(),
                            corresponding: correspondingInput && correspondingInput.checked ? '1' : '0'
                        };

                        // Include user_id if available (from autocomplete selection)
                        const $nameInput = $(nameInput);
                        const userId = $nameInput.data('user-id');
                        const userUid = $nameInput.data('user-uid');

                        if (userId) {
                            authorData.author_id = userId;
                        }
                        if (userUid) {
                            authorData.uid = userUid;
                        }

                        authors.push(authorData);
                    }
                });

                // Add authors to form data as JSON
                formData.append('authors', JSON.stringify(authors));

                // Convert BE year to CE year for database storage
                const beYear = formData.get('publication_year');
                if (beYear && beYear.trim() !== '') {
                    const ceYear = parseInt(beYear) - 543;
                    formData.set('publication_year', ceYear);
                }

                try {
                    // Show loading
                    Swal.fire({
                        title: 'กำลังบันทึก...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Use user route for update
                    const response = await fetch('<?= site_url('publications/update') ?>/' + publicationId, {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        await Swal.fire({
                            title: 'บันทึกสำเร็จ!',
                            text: 'แก้ไขผลงานเรียบร้อยแล้ว',
                            icon: 'success',
                            confirmButtonText: 'ตกลง'
                        });

                        // Close modal and reload data
                        closeEditModal();
                        await loadPublications();
                    } else {
                        throw new Error(result.message || 'ไม่สามารถบันทึกได้');
                    }
                } catch (error) {
                    console.error('Update error:', error);
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด!',
                        text: error.message || 'ไม่สามารถบันทึกการแก้ไขได้',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                }
            });

            // Listen for publication type changes
            document.getElementById('edit_publication_type').addEventListener('change', updateEditConditionalFields);

            // Close suggestions when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.relative')) {
                    document.querySelectorAll('.name-suggestions').forEach(el => {
                        el.classList.add('hidden');
                    });
                }
            });

            // Event listeners
            document.getElementById('search-input').addEventListener('input', filterPublications);
            document.getElementById('type-filter').addEventListener('change', filterPublications);

            // Initialize
            loadPublications();
        </script>

        <!-- Author Search Script -->
        <script src="<?= base_url('public/assets/js/author-search.js') ?>"></script>

        <!-- Email Autocomplete Script -->
        <script src="<?= base_url('public/assets/js/email-autocomplete.js') ?>"></script>
    </div>
</body>

</html>