<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผลงานวิจัย - Publication Management</title>

    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <!-- Google Font (Sarabun) -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        window.BASE_URL = '<?= rtrim(base_url(), '/') ?>';
        window.API_ENDPOINTS = {
            publications: '<?= site_url('api/dashboard/publications') ?>',
            getPublication: '<?= site_url('admin/publications/get') ?>',
            deletePublication: '<?= site_url('admin/publications/delete') ?>',
            approvePublication: '<?= site_url('admin/publications/approve') ?>',
            setApprovalStatus: '<?= site_url('admin/publications/set_approval_status') ?>',
            updatePublication: '<?= site_url('admin/publications/update') ?>',
            savePublication: '<?= site_url('admin/publications/save') ?>',
            uploadFile: '<?= site_url('utility/uploadFile') ?>',
            downloadFile: '<?= site_url('utility/downloadFile') ?>',
            searchUserNames: '<?= site_url('publications/search-user-names') ?>',
            searchAuthorEmail: '<?= site_url('publications/search-author-email') ?>'
        };
    </script>

    <!-- Modal Handler -->
    <script src="<?= base_url('assets/js/modal-handler.js') ?>"></script>

    <!-- Publication Form Styles (matching create.php) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/publication-form.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/author-search.css') ?>">

    <style>
        body {
            font-family: 'Sarabun', sans-serif;
        }

        /* -----------------------------------------------------------------
           Remove SweetAlert's dark full-screen backdrop on this page.
           On the fast server path a loading dialog (Swal.showLoading) can fail
           to close and leaves a black .swal2-container stacked over the modal,
           making the page look "black / blank". By making the backdrop fully
           transparent and non-interactive, a stuck container can never black
           out or block the page; the white dialog card itself still shows.
           ----------------------------------------------------------------- */
        .swal2-container {
            background: transparent !important;
            pointer-events: none !important;
        }

        .swal2-container .swal2-popup {
            pointer-events: auto !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
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

        /* AI Waiting Modal Animation */
        @keyframes pulse-slow {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.8;
            }
        }

        .animate-pulse-slow {
            animation: pulse-slow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Z-Index Layers:
           Layer 1 (lowest): addModal (Add/Edit publication form) = z-40
           Layer 2: addAiWaitingModal (AI waiting) = z-9000
           SweetAlert uses z-index ~10000+ by default (highest)
           
           NOTE: editModal has been removed. Now using addModal for both Add and Edit modes.
        */

        /* Prevent interaction when AI modal is shown */
        #addAiWaitingModal:not(.hidden) {
            pointer-events: auto !important;
        }

        #addAiWaitingModal:not(.hidden)~* {
            pointer-events: none !important;
        }

        .publication-type-card.selected {
            border-color: currentColor !important;
            background-color: rgba(59, 130, 246, 0.1);
            transform: scale(1.05);
        }

        .publication-type-card.selected.text-blue-600 {
            border-color: #3b82f6 !important;
            background-color: rgba(59, 130, 246, 0.1);
        }

        .publication-type-card.selected.text-green-600 {
            border-color: #16a34a !important;
            background-color: rgba(22, 163, 74, 0.1);
        }

        .publication-type-card.selected.text-purple-600 {
            border-color: #9333ea !important;
            background-color: rgba(147, 51, 234, 0.1);
        }

        .publication-type-card.selected.text-red-600 {
            border-color: #dc2626 !important;
            background-color: rgba(220, 38, 38, 0.1);
        }

        .publication-type-card.selected.text-yellow-600 {
            border-color: #ca8a04 !important;
            background-color: rgba(202, 138, 4, 0.1);
        }

        .publication-type-card.selected.text-gray-600 {
            border-color: #4b5563 !important;
            background-color: rgba(75, 85, 99, 0.1);
        }

        /* Publication row animations */
        .publication-row {
            transition: all 0.15s ease;
        }

        .publication-row:hover {
            background-color: rgb(248 250 252);
        }

        /* Line clamp utilities */
        .line-clamp-1 {
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Scrollbar styling */
        #publications-table::-webkit-scrollbar {
            width: 6px;
        }

        #publications-table::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        #publications-table::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        #publications-table::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>

<body class="min-h-full">
    <div class="min-h-full bg-gray-50">
        <!-- Robust Non-blocking Loading Overlay -->
        <div id="loadingOverlay" class="hidden fixed inset-0 z-[99999] flex items-center justify-center bg-gray-900 bg-opacity-50 backdrop-blur-sm pointer-events-auto">
            <div class="flex flex-col items-center p-6 bg-white rounded-2xl shadow-xl">
                <div class="loading-spinner mb-4"></div>
                <p class="font-semibold text-gray-700">กำลังประมวลผลข้อมูลผู้แต่ง...</p>
            </div>
        </div>

        <?php
        // Set page title and subtitle for header partial
        $pageTitle = 'จัดการผลงานวิจัย';
        $pageSubtitle = 'Publication Management System';
        ?>

        <!-- Top Navigation Bar -->
        <?= view('admin/partials/header', ['pageTitle' => $pageTitle, 'pageSubtitle' => $pageSubtitle]) ?>

        <div class="flex">
            <!-- Sidebar Navigation -->
            <?= view('admin/partials/navigation') ?>

            <!-- Main Content -->
            <main class="flex-1 p-4 lg:p-6 bg-gradient-to-br from-slate-50 via-blue-50/30 to-purple-50/20 min-h-screen">
                <!-- Stats Cards (matching summary.php style) -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                    <!-- Total -->
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center">
                                <span class="text-xl">📊</span>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-slate-500">ผลงานทั้งหมด</p>
                                <p id="total-count" class="text-2xl font-bold text-slate-700">-</p>
                            </div>
                        </div>
                    </div>
                    <!-- Journal -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <span class="text-xl">📚</span>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-blue-600">วารสาร</p>
                                <p id="journal-count" class="text-2xl font-bold text-blue-700">-</p>
                            </div>
                        </div>
                    </div>
                    <!-- Conference -->
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <span class="text-xl">🎤</span>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-purple-600">ประชุมวิชาการ</p>
                                <p id="conference-count" class="text-2xl font-bold text-purple-700">-</p>
                            </div>
                        </div>
                    </div>
                    <!-- Book & Others -->
                    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                                <span class="text-xl">📖</span>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-emerald-600">หนังสือ/อื่นๆ</p>
                                <p id="other-count" class="text-2xl font-bold text-emerald-700">-</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-3 mb-4">
                    <button onclick="openAddModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors inline-flex items-center justify-center gap-2 shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        เพิ่มผลงานใหม่
                    </button>
                </div>

                <!-- Search and Filter Bar -->
                <div class="bg-white rounded-lg shadow p-3 mb-4">
                    <div class="flex flex-wrap gap-3">
                        <input type="text" id="search-input" placeholder="🔍 ค้นหาผลงานวิจัย..." class="flex-1 min-w-[200px] px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <select id="type-filter" class="px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">ประเภทผลงานทั้งหมด</option>
                            <option value="journal">วารสาร (Journal)</option>
                            <option value="proceedings">ประชุมวิชาการ (Conference)</option>
                            <option value="book">หนังสือ (Book)</option>
                            <option value="thesis">วิทยานิพนธ์ (Thesis)</option>
                            <option value="report">รายงาน (Report)</option>
                            <option value="other">อื่นๆ (Other)</option>
                        </select>
                        <select id="status-filter" class="px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">สถานะทั้งหมด</option>
                            <option value="approved">ผ่านเกณฑ์</option>
                            <option value="rejected">ไม่ผ่านเกณฑ์</option>
                            <option value="pending">ยังไม่ได้ตรวจสอบ</option>
                        </select>
                    </div>
                </div>

                <!-- Publications Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <!-- Table Container with Overflow -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Title
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Year
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        มาตรฐาน กพอ.
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        ผู้บันทึก
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="publications-table" class="bg-white divide-y divide-gray-200">
                                <!-- Loading state -->
                                <tr id="loading-row">
                                    <td colspan="6" class="px-6 py-20 text-center">
                                        <div class="flex flex-col items-center justify-center gap-4">
                                            <div class="relative">
                                                <div class="w-12 h-12 border-4 border-blue-200 rounded-full"></div>
                                                <div class="absolute top-0 left-0 w-12 h-12 border-4 border-transparent border-t-blue-600 rounded-full animate-spin"></div>
                                            </div>
                                            <p class="text-sm text-gray-500">กำลังโหลดข้อมูล...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
        </div>

        <!-- NOTE: editModal has been removed. Now using addModal for both Add and Edit modes. -->

        <!-- Add Publication Modal (Layer 1: lowest) -->
        <div id="addModal" class="hidden fixed inset-0 z-[40] flex items-center justify-center bg-gray-900 bg-opacity-70 p-4 sm:p-6">
            <div class="modal-content relative w-full max-w-5xl bg-white rounded-2xl shadow-2xl max-h-[90vh] flex flex-col overflow-hidden">
                <!-- Modal Header (Dynamic: Add/Edit) -->
                <div class="flex justify-between items-center px-8 py-5 border-b border-gray-200 bg-white rounded-t-2xl flex-shrink-0">
                    <h2 id="addModalTitle" class="text-2xl font-bold text-gray-900">เพิ่มผลงานวิจัย</h2>
                    <button onclick="closeAddModal()"
                        class="w-12 h-12 flex items-center justify-center rounded-full bg-gray-100 hover:bg-red-100 text-gray-500 hover:text-red-600 transition-colors"
                        title="ปิด">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Add/Edit Form -->
                <form id="addForm" class="px-8 py-6 space-y-6 overflow-y-auto flex-1">
                    <?= csrf_field() ?>
                    <!-- Hidden field for Edit mode -->
                    <input type="hidden" id="add_publication_id" name="publication_id" value="">

                    <!-- Smart Assistant Section -->
                    <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg border-2 border-blue-200 p-6">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 mb-2">ตัวช่วยอัจฉริยะ AI - กรอกข้อมูลอัตโนมัติ</h3>
                                <p class="text-sm text-gray-600 mb-2">อัปโหลดไฟล์ PDF หรือใส่ลิงก์เอกสาร AI จะช่วยสกัดข้อมูลและกรอกให้อัตโนมัติ</p>
                                <p class="text-xs text-blue-600 bg-blue-50 px-3 py-2 rounded-lg mb-4">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <strong>เคล็ดลับ:</strong> สามารถใส่ลิงก์ DOI (เช่น https://doi.org/10.xxxx/xxxxx) ลงในช่อง URL ได้เลย AI จะดึงข้อมูลให้อัตโนมัติ
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            อัปโหลดไฟล์
                                        </label>
                                        <input type="file" id="add_fileInput" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="hidden">
                                        <button type="button" onclick="document.getElementById('add_fileInput').click()" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium">
                                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                            </svg>
                                            เลือกไฟล์
                                        </button>
                                        <p class="text-xs text-gray-500 mt-2">PDF, DOC, DOCX, JPG, PNG (สูงสุด 10MB)</p>
                                        <div id="add_uploadProgress" class="hidden mt-3">
                                            <div class="flex items-center gap-2 text-sm text-blue-600">
                                                <div class="loading-spinner" style="width: 16px; height: 16px; border-width: 2px;"></div>
                                                <span>กำลังอัปโหลด...</span>
                                            </div>
                                        </div>
                                        <div id="add_uploadedFile" class="hidden mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-xs font-medium text-green-800">✓ อัปโหลดสำเร็จ</p>
                                                    <p id="add_fileName" class="text-sm text-gray-900 truncate"></p>
                                                </div>
                                                <button type="button" onclick="removeAddUploadedFile()" class="text-red-600 hover:text-red-800 flex-shrink-0">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                                        <label for="add_ai-url-input" class="block text-sm font-medium text-gray-700 mb-2">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                            </svg>
                                            หรือใส่ URL
                                        </label>
                                        <input type="url" id="add_ai-url-input" placeholder="https://example.com/paper.pdf"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        <p class="text-xs text-gray-500 mt-2">ลิงก์ไปยัง PDF หรือเว็บไซต์เอกสาร</p>
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-end">
                                    <button type="button" id="add_ai-assist-btn" onclick="processAddWithAI()" class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                        </svg>
                                        ให้ AI ช่วยกรอกข้อมูล
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Publication Type Selection -->
                    <div class="form-section">
                        <label class="block text-sm font-medium text-gray-700 mb-4">ประเภทผลงาน *</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                            <div class="publication-type-card text-blue-600" data-type="journal">
                                <div class="type-card-inner">
                                    <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                    <span class="text-sm font-medium">วารสาร</span>
                                </div>
                            </div>
                            <div class="publication-type-card text-green-600" data-type="book">
                                <div class="type-card-inner">
                                    <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                    <span class="text-sm font-medium">หนังสือ</span>
                                </div>
                            </div>
                            <div class="publication-type-card text-purple-600" data-type="proceedings">
                                <div class="type-card-inner">
                                    <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                                    </svg>
                                    <span class="text-sm font-medium">การประชุมวิชาการ</span>
                                </div>
                            </div>
                            <div class="publication-type-card text-red-600" data-type="thesis">
                                <div class="type-card-inner">
                                    <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span class="text-sm font-medium">วิทยานิพนธ์</span>
                                </div>
                            </div>
                            <div class="publication-type-card text-yellow-600" data-type="report">
                                <div class="type-card-inner">
                                    <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span class="text-sm font-medium">รายงาน</span>
                                </div>
                            </div>
                            <div class="publication-type-card text-gray-600" data-type="other">
                                <div class="type-card-inner">
                                    <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h4"></path>
                                    </svg>
                                    <span class="text-sm font-medium">อื่นๆ</span>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="add_publication_type" name="publication_type" required>
                    </div>

                    <!-- Basic Information Section -->
                    <div class="form-section">
                        <h3 class="section-title">ข้อมูลพื้นฐาน</h3>

                        <div class="space-y-6">
                            <!-- Title - Full Width -->
                            <div>
                                <label for="add_title" class="block text-sm font-medium text-gray-700 mb-2">ชื่อผลงาน *</label>
                                <input type="text" id="add_title" name="title" required placeholder="กรุณากรอกชื่อผลงาน"
                                    class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            </div>

                            <!-- Source - Full Width -->
                            <div>
                                <label for="add_source" class="block text-sm font-medium text-gray-700 mb-2">แหล่งตีพิมพ์ *</label>
                                <input type="text" id="add_source" name="source" placeholder="ชื่อวารสาร, การประชุม, สำนักพิมพ์ เป็นต้น" required
                                    class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            </div>

                            <!-- Year and Month - Side by Side -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="add_publication_year" class="block text-sm font-medium text-gray-700 mb-2">ปีที่ตีพิมพ์ (พ.ศ.) *</label>
                                    <input type="number" id="add_publication_year" name="publication_year" min="2443" max="2643" placeholder="เช่น 2567"
                                        class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                    <p class="mt-2 text-xs text-gray-500">กรุณากรอกปีพุทธศักราช เช่น 2567 สำหรับปี ค.ศ. 2024</p>
                                </div>

                                <div>
                                    <label for="add_publication_month" class="block text-sm font-medium text-gray-700 mb-2">เดือนที่ตีพิมพ์</label>
                                    <select id="add_publication_month" name="publication_month"
                                        class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white">
                                        <option value="">เลือกเดือน...</option>
                                        <option value="01">มกราคม</option>
                                        <option value="02">กุมภาพันธ์</option>
                                        <option value="03">มีนาคม</option>
                                        <option value="04">เมษายน</option>
                                        <option value="05">พฤษภาคม</option>
                                        <option value="06">มิถุนายน</option>
                                        <option value="07">กรกฎาคม</option>
                                        <option value="08">สิงหาคม</option>
                                        <option value="09">กันยายน</option>
                                        <option value="10">ตุลาคม</option>
                                        <option value="11">พฤศจิกายน</option>
                                        <option value="12">ธันวาคม</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Authors Section -->
                    <div class="authors-section form-section">
                        <div class="flex items-center justify-between mb-4">
                            <label class="block text-sm font-medium text-gray-700">ผู้แต่ง/ผู้วิจัย *</label>
                            <div class="flex items-center gap-3">
                                <span id="add_authorStatus" class="text-sm text-gray-500"></span>
                                <button type="button" onclick="addAddAuthor()"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    เพิ่มผู้แต่ง
                                </button>
                            </div>
                        </div>
                        <div id="add_authors_container" class="authors-container"></div>
                        <!-- Authors Help Text -->
                        <p class="mt-2 text-sm text-gray-500">
                            💡 <strong>เคล็ดลับ:</strong> พิมพ์อีเมลเพื่อกรอกข้อมูลผู้แต่งอัตโนมัติหากมีข้อมูลในระบบ
                        </p>
                    </div>

                    <!-- Abstract Section -->
                    <div class="form-section">
                        <h3 class="section-title">บทคัดย่อ</h3>
                        <textarea id="add_abstract" name="abstract" rows="4" placeholder="กรุณากรอกบทคัดย่อ"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"></textarea>
                    </div>

                    <!-- Publication Details Section -->
                    <div class="form-section">
                        <h3 class="section-title">รายละเอียดการตีพิมพ์</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Journal & Conference Fields -->
                            <div class="conditional-field field-transition" data-types="journal,proceedings">
                                <label for="add_volume" class="block text-sm font-medium text-gray-700">ปีที่ (Volume)</label>
                                <input type="text" id="add_volume" name="volume" placeholder="เช่น Vol. 12"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="conditional-field field-transition" data-types="journal,proceedings">
                                <label for="add_issue" class="block text-sm font-medium text-gray-700">ฉบับที่ (Issue)</label>
                                <input type="text" id="add_issue" name="issue" placeholder="เช่น No. 3"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="conditional-field field-transition" data-types="journal,proceedings,book">
                                <label for="add_pages" class="block text-sm font-medium text-gray-700">หน้า</label>
                                <input type="text" id="add_pages" name="pages" placeholder="เช่น 123-145"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="conditional-field field-transition" data-types="journal,proceedings">
                                <label for="add_doi" class="block text-sm font-medium text-gray-700">DOI</label>
                                <input type="text" id="add_doi" name="doi" placeholder="10.1000/xyz123"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Book Fields -->
                            <div class="conditional-field field-transition" data-types="book">
                                <label for="add_isbn" class="block text-sm font-medium text-gray-700">ISBN</label>
                                <input type="text" id="add_isbn" name="isbn" placeholder="978-3-16-148410-0"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="conditional-field field-transition" data-types="book">
                                <label for="add_publisher" class="block text-sm font-medium text-gray-700">สำนักพิมพ์</label>
                                <input type="text" id="add_publisher" name="publisher" placeholder="ชื่อสำนักพิมพ์"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="conditional-field field-transition" data-types="book">
                                <label for="add_book_title" class="block text-sm font-medium text-gray-700">ชื่อหนังสือ</label>
                                <input type="text" id="add_book_title" name="book_title" placeholder="สำหรับบทในหนังสือ"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="conditional-field field-transition" data-types="book">
                                <label for="add_chapter" class="block text-sm font-medium text-gray-700">บทที่</label>
                                <input type="text" id="add_chapter" name="chapter" placeholder="เช่น Chapter 5"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="conditional-field field-transition" data-types="book">
                                <label for="add_editor" class="block text-sm font-medium text-gray-700">บรรณาธิการ</label>
                                <input type="text" id="add_editor" name="editor" placeholder="ชื่อบรรณาธิการ"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Conference Fields -->
                            <div class="conditional-field field-transition" data-types="proceedings">
                                <label for="add_conference_name" class="block text-sm font-medium text-gray-700">ชื่อการประชุม</label>
                                <input type="text" id="add_conference_name" name="conference_name" placeholder="ชื่อการประชุมวิชาการ"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="conditional-field field-transition" data-types="proceedings">
                                <label for="add_conference_location" class="block text-sm font-medium text-gray-700">สถานที่จัด</label>
                                <input type="text" id="add_conference_location" name="conference_location" placeholder="สถานที่จัดการประชุม"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="conditional-field field-transition" data-types="proceedings">
                                <label for="add_conference_date" class="block text-sm font-medium text-gray-700">วันที่จัด</label>
                                <input type="date" id="add_conference_date" name="conference_date"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information Section -->
                    <div class="form-section">
                        <h3 class="section-title">ข้อมูลเพิ่มเติม</h3>

                        <div class="space-y-6">
                            <!-- Keywords -->
                            <div>
                                <label for="add_keywords" class="block text-sm font-medium text-gray-700 mb-2">คำสำคัญ</label>
                                <input type="text" id="add_keywords" name="keywords" placeholder="คั่นด้วยเครื่องหมาย ,"
                                    class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            </div>

                            <!-- URLs -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="add_ref_url" class="block text-sm font-medium text-gray-700 mb-2">URL อ้างอิง</label>
                                    <input type="url" id="add_ref_url" name="ref_url" placeholder="https://..."
                                        class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                    <div id="add_existing_file_link" class="hidden mt-2 p-2 bg-blue-50 border border-blue-200 rounded-lg text-sm">
                                        <span class="text-gray-700">ไฟล์ที่ผูกไว้:</span>
                                        <a id="add_existing_file_link_anchor" href="#" target="_blank" rel="noopener"
                                            class="ml-2 text-blue-600 hover:text-blue-800 hover:underline font-medium inline-flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                            เปิดไฟล์ที่อัปโหลด
                                        </a>
                                    </div>
                                </div>
                                <div>
                                    <label for="add_url" class="block text-sm font-medium text-gray-700 mb-2">URL เพิ่มเติม</label>
                                    <input type="url" id="add_url" name="url" placeholder="https://..."
                                        class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label for="add_notes" class="block text-sm font-medium text-gray-700 mb-2">หมายเหตุ</label>
                                <textarea id="add_notes" name="notes" rows="3" placeholder="หมายเหตุเพิ่มเติม (ถ้ามี)"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"></textarea>
                            </div>
                        </div>
                    </div>

                </form>
                <!-- Modal Footer (Dynamic: Add/Edit) -->
                <div class="flex gap-4 justify-end px-8 py-6 border-t border-gray-100 rounded-b-2xl flex-shrink-0 bg-white">
                    <button type="button" onclick="closeAddModal()"
                        class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                        ยกเลิก
                    </button>
                    <button type="submit" form="addForm" id="addFormSubmitBtn"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        บันทึกผลงาน
                    </button>
                </div>
            </div>
        </div>

        <script>
            const BASE_URL = window.BASE_URL;
            const API = window.API_ENDPOINTS;
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

            // Sort publications by latest saved time (updated_at, then created_at), newest first
            function sortByLatestSaved(list) {
                const savedTime = (pub) => {
                    const raw = pub.updated_at || pub.created_at || '';
                    // MySQL datetime "YYYY-MM-DD HH:MM:SS" -> ISO so Date.parse is reliable
                    const t = Date.parse(String(raw).replace(' ', 'T'));
                    return isNaN(t) ? 0 : t;
                };
                list.sort((a, b) => {
                    const diff = savedTime(b) - savedTime(a);
                    if (diff !== 0) return diff;
                    return (parseInt(b.id) || 0) - (parseInt(a.id) || 0);
                });
                return list;
            }

            // Load publications data
            async function loadPublications() {
                try {
                    const apiUrl = API.publications + '?limit=1000';

                    const response = await fetch(apiUrl);
                    const result = await response.json();

                    if (result.success) {
                        allPublications = result.data;
                        // เรียงตามการบันทึกล่าสุด (updated_at/created_at มากสุดก่อน)
                        sortByLatestSaved(allPublications);
                        filteredPublications = [...allPublications];

                        updateStats();
                        renderTable();
                    } else {
                        console.error('API returned success=false:', result.message);
                        showError('ไม่สามารถโหลดข้อมูลได้: ' + result.message);
                    }
                } catch (error) {
                    console.error('Load publications error:', error);
                    showError('เกิดข้อผิดพลาดในการโหลดข้อมูล');
                }
            }

            // Update statistics
            function updateStats() {
                const totalCount = allPublications.length;
                const journalCount = allPublications.filter(p => p.publication_type === 'journal').length;
                const conferenceCount = allPublications.filter(p => p.publication_type === 'proceedings').length;

                const otherCount = allPublications.filter(p =>
                    !['journal', 'proceedings'].includes(p.publication_type)
                ).length;

                document.getElementById('total-count').textContent = totalCount;
                document.getElementById('journal-count').textContent = journalCount;
                document.getElementById('conference-count').textContent = conferenceCount;
                document.getElementById('other-count').textContent = otherCount;
            }

            // Filter publications
            function filterPublications() {
                const searchTerm = document.getElementById('search-input').value.toLowerCase();
                const typeFilter = document.getElementById('type-filter').value;
                const statusFilter = document.getElementById('status-filter')?.value || '';

                filteredPublications = allPublications.filter(pub => {
                    // Search filter
                    const matchesSearch = !searchTerm ||
                        pub.title.toLowerCase().includes(searchTerm) ||
                        (pub.authors_names_thai && pub.authors_names_thai.toLowerCase().includes(searchTerm)) ||
                        (pub.authors_names_en && pub.authors_names_en.toLowerCase().includes(searchTerm)) ||
                        (pub.source && pub.source.toLowerCase().includes(searchTerm));

                    // Type filter
                    const matchesType = !typeFilter || pub.publication_type === typeFilter;

                    // Status filter
                    let matchesStatus = true;
                    if (statusFilter === 'approved') {
                        matchesStatus = pub.approve == 1 || pub.approve === '1' || pub.approve === 1;
                    } else if (statusFilter === 'rejected') {
                        matchesStatus = pub.approve == 0 || pub.approve === '0' || pub.approve === 0;
                    } else if (statusFilter === 'pending') {
                        matchesStatus = pub.approve === null || pub.approve === undefined || pub.approve === '' || pub.approve === 'null';
                    }

                    return matchesSearch && matchesType && matchesStatus;
                });

                renderTable();
            }

            // Render publications table
            function renderTable() {
                const tbody = document.getElementById('publications-table');

                if (filteredPublications.length === 0) {
                    tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                    <span class="text-4xl">📭</span>
                                </div>
                                <p class="text-lg font-semibold text-gray-700 mb-2">No publications found</p>
                                <p class="text-sm text-gray-500 mb-4">Try adjusting your search or add a new publication</p>
                                <button onclick="openAddModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Add Your First Publication
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                    return;
                }

                try {
                    tbody.innerHTML = filteredPublications.map((pub, index) => {
                        const authorsChips = renderAuthorChips(pub);
                        const source = pub.source || '-';
                        const recorderName = (pub.created_by_name && pub.created_by_name.trim()) ? pub.created_by_name.trim() : '';
                        const recorderEmail = pub.created_by_email || '';
                        const recorder = recorderName || recorderEmail || '-';

                        return `
                    <tr class="hover:bg-gray-50 transition-colors">
                        <!-- Title + source + author chips -->
                        <td class="px-6 py-4 max-w-md">
                            <div class="font-semibold text-gray-900 text-sm leading-snug mb-1">${escapeHtml(pub.title)}</div>
                            <div class="text-xs text-gray-500 mb-2">${escapeHtml(source)}</div>
                            ${authorsChips}
                        </td>

                        <!-- Type Badge -->
                        <td class="px-6 py-4">
                            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full ${getTypeColorClass(pub.publication_type)}">
                                ${getPublicationTypeThai(pub.publication_type).toUpperCase()}
                            </span>
                        </td>
                        
                        <!-- Year -->
                        <td class="px-6 py-4 text-center">
                            <div class="text-sm font-medium text-gray-900">${pub.publication_year || '-'}</div>
                        </td>
                        
                        <!-- Quality Standard Dropdown (มาตรฐาน กพอ.) -->
                        <td class="px-6 py-4 text-center">
                            <select 
                                onchange="handleApprovalChange(${pub.id}, this.value, '${escapeHtml(pub.title).replace(/'/g, "\\'")}')" 
                                class="px-3 py-1.5 text-xs font-semibold rounded-md cursor-pointer transition-colors border-0 ${
                                    pub.approve == 1 ? 'bg-green-100 text-green-800' : 
                                    pub.approve == 0 ? 'bg-red-100 text-red-800' : 
                                    'bg-yellow-100 text-yellow-800'
                                }"
                                style="min-width: 120px;">
                                <option value="1" ${pub.approve == 1 ? 'selected' : ''}>ผ่านเกณฑ์</option>
                                <option value="0" ${pub.approve == 0 ? 'selected' : ''}>ไม่ผ่านเกณฑ์</option>
                                <option value="null" ${pub.approve === null || pub.approve === undefined || pub.approve === '' ? 'selected' : ''}>ยังไม่ได้ตรวจสอบ</option>
                            </select>
                        </td>

                        <!-- ผู้บันทึก (recorder / created by) -->
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-700">${escapeHtml(recorder)}</div>
                            ${recorderName && recorderEmail ? `<div class="text-xs text-gray-400">${escapeHtml(recorderEmail)}</div>` : ''}
                        </td>

                        <!-- Action Buttons -->
                        <td class="px-6 py-4">
                            <div class="flex justify-center items-center gap-2">
                                <button onclick="viewPublication(${pub.id})" class="w-9 h-9 flex items-center justify-center rounded-lg bg-emerald-100 border border-emerald-300 text-emerald-700 hover:bg-emerald-200 transition-all" title="ดูรายละเอียด">
                                    <span class="text-base">👁️</span>
                                </button>
                                <button onclick="editPublication(${pub.id})" class="w-9 h-9 flex items-center justify-center rounded-lg bg-amber-100 border border-amber-300 text-amber-700 hover:bg-amber-200 transition-all" title="แก้ไข">
                                    <span class="text-base">✏️</span>
                                </button>
                                <button onclick="deletePublication(${pub.id}, '${escapeHtml(pub.title)}')" class="w-9 h-9 flex items-center justify-center rounded-lg bg-rose-100 border border-rose-300 text-rose-700 hover:bg-rose-200 transition-all" title="ลบ">
                                    <span class="text-base">🗑️</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                    }).join('');
                } catch (error) {
                    console.error('Render table error:', error);
                }
            }

            // Get approval status badge
            function getApprovalStatusBadge(approve) {
                // null or undefined = ยังไม่ได้รับการยืนยัน
                if (approve === null || approve === undefined || approve === '') {
                    return `
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full flex items-center gap-1 w-fit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            ยังไม่ได้ตรวจสอบ
                        </span>
                    `;
                }

                // 1 = ตรงการเกณฑ์ กพอ.
                if (approve == 1 || approve === '1') {
                    return `
                        <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full flex items-center gap-1 w-fit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            ผ่านเกณฑ์
                        </span>
                    `;
                }

                // 0 = ไม่เข้าตามเกณฑ์ กพอ.
                if (approve == 0 || approve === '0') {
                    return `
                        <span class="px-3 py-1 bg-red-100 text-red-800 text-xs font-medium rounded-full flex items-center gap-1 w-fit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            ไม่ผ่านเกณฑ์
                        </span>
                    `;
                }

                // Fallback (should not happen)
                return `
                    <span class="px-3 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded-full flex items-center gap-1 w-fit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        ยังไม่ได้ตรวจสอบ
                    </span>
                `;
            }

            // Get color class for publication type badge
            function getTypeColorClass(type) {
                const colorMap = {
                    'journal': 'bg-blue-100 text-blue-800',
                    'proceedings': 'bg-green-100 text-green-800',
                    'book': 'bg-pink-100 text-pink-800',
                    'thesis': 'bg-amber-100 text-amber-800',
                    'report': 'bg-cyan-100 text-cyan-800',
                    'other': 'bg-gray-100 text-gray-800'
                };
                return colorMap[type] || 'bg-gray-100 text-gray-800';
            }

            // Get icon for publication type
            function getTypeIcon(type) {
                const iconMap = {
                    'journal': '📚',
                    'proceedings': '🎤',
                    'book': '📖',
                    'thesis': '📝',
                    'report': '📊',
                    'other': '📄'
                };
                return iconMap[type] || '📄';
            }

            // Get border color based on approval status
            function getStatusBorderClass(approve) {
                if (approve === null || approve === undefined || approve === '') {
                    return 'border-amber-400';
                }
                if (approve == 1 || approve === '1') {
                    return 'border-emerald-500';
                }
                if (approve == 0 || approve === '0') {
                    return 'border-rose-400';
                }
                return 'border-slate-200';
            }

            // Get status badge HTML
            function getStatusBgClass(approve) {
                if (approve == 1 || approve === '1') {
                    return `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-full bg-gradient-to-r from-emerald-400 to-green-500 text-white shadow-sm">✓ ผ่านเกณฑ์</span>`;
                }
                if (approve == 0 || approve === '0') {
                    return `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-full bg-gradient-to-r from-rose-400 to-red-500 text-white shadow-sm">✗ ไม่ผ่านเกณฑ์</span>`;
                }
                return `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-full bg-gradient-to-r from-amber-400 to-orange-500 text-white shadow-sm">⏳ ยังไม่ได้ตรวจสอบ</span>`;
            }

            // Render author chips under the title.
            // - Authors matched to a registered user (in the database) => colored chip
            // - Authors not in the database / not matched => black & white chip
            function renderAuthorChips(pub) {
                let authors = Array.isArray(pub.authors_list) ? pub.authors_list : [];

                // Fallback for older payloads without authors_list: build plain (unmatched) chips
                if (authors.length === 0) {
                    const raw = pub.authors_names_thai || pub.authors_names_en || '';
                    authors = raw
                        .split(',')
                        .map(name => ({ name: name.trim(), matched: false }))
                        .filter(a => a.name !== '');
                }

                if (authors.length === 0) {
                    return '<span class="text-xs text-gray-400">ไม่มีข้อมูลผู้แต่ง</span>';
                }

                const chips = authors.map(a => {
                    const name = escapeHtml(a.name || '-');
                    const cls = a.matched
                        ? 'bg-blue-50 text-blue-700 border border-blue-200'
                        : 'bg-gray-100 text-gray-500 border border-gray-300';
                    const dot = a.matched ? 'bg-blue-500' : 'bg-gray-400';
                    return `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ${cls}">
                        <span class="w-1.5 h-1.5 rounded-full ${dot}"></span>${name}
                    </span>`;
                }).join('');

                return `<div class="flex flex-wrap gap-1.5">${chips}</div>`;
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
                if (isEditLoading) {
                    console.log('Edit publication already in progress, ignoring click.');
                    return;
                }
                isEditLoading = true;

                // Show robust CSS-based loading overlay
                const loader = document.getElementById('loadingOverlay');
                if (loader) loader.classList.remove('hidden');

                try {
                    console.log('=== EDIT PUBLICATION START ===');
                    console.log('Publication ID:', id);

                    // Fetch publication data
                    const apiUrl = API.getPublication + '/' + id;
                    console.log('Fetching from:', apiUrl);
                    const response = await fetch(apiUrl);
                    console.log('Response status:', response.status);

                    const result = await response.json();
                    console.log('Edit publication result:', result);
                    console.log('Publication data:', result.data);

                    if (!result.success) {
                        console.error('Failed to load publication:', result.message);
                        throw new Error(result.message || 'ไม่สามารถโหลดข้อมูลได้');
                    }

                    // Open edit modal with author matching
                    console.log('Opening edit modal with data and matching authors...');
                    await openAddModalForEdit(result.data);
                    console.log('=== EDIT PUBLICATION COMPLETE ===');
                } catch (error) {
                    console.error('=== EDIT PUBLICATION ERROR ===');
                    console.error('Error type:', error.name);
                    console.error('Error message:', error.message);
                    console.error('Error stack:', error.stack);
                    
                    // Close the modal just in case it was opened before the error occurred
                    closeAddModal();

                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด!',
                        text: error.message || 'ไม่สามารถโหลดข้อมูลผลงานได้',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                } finally {
                    isEditLoading = false;
                    if (loader) loader.classList.add('hidden');
                }
            }

            // Delete publication with confirmation
            async function deletePublication(id, title) {
                console.log('=== DELETE PUBLICATION START ===');
                console.log('Publication ID:', id);
                console.log('Publication Title:', title);

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

                console.log('Delete confirmation result:', result.isConfirmed);

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

                        const apiUrl = API.deletePublication + '/' + id;
                        console.log('Deleting from:', apiUrl);

                        const response = await fetch(apiUrl, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json'
                            }
                        });

                        console.log('Delete response status:', response.status);

                        const result = await response.json();
                        console.log('Delete result:', result);

                        if (result.success) {
                            console.log('Delete successful');
                            await Swal.fire({
                                title: 'ลบสำเร็จ!',
                                text: 'ผลงานถูกลบเรียบร้อยแล้ว',
                                icon: 'success',
                                confirmButtonText: 'ตกลง'
                            });

                            // Reload publications
                            console.log('Reloading publications...');
                            await loadPublications();
                            console.log('=== DELETE PUBLICATION COMPLETE ===');
                        } else {
                            console.error('Delete failed:', result.message);
                            throw new Error(result.message || 'ไม่สามารถลบได้');
                        }
                    } catch (error) {
                        console.error('=== DELETE PUBLICATION ERROR ===');
                        console.error('Error type:', error.name);
                        console.error('Error message:', error.message);
                        console.error('Error stack:', error.stack);
                        Swal.fire({
                            title: 'เกิดข้อผิดพลาด!',
                            text: error.message || 'ไม่สามารถลบผลงานได้',
                            icon: 'error',
                            confirmButtonText: 'ตกลง'
                        });
                    }
                } else {
                    console.log('Delete cancelled by user');
                    console.log('=== DELETE PUBLICATION CANCELLED ===');
                }
            }

            // Approve publication with confirmation
            async function approvePublication(id, title) {
                const result = await Swal.fire({
                    title: 'ยืนยันการอนุมัติ?',
                    html: `
                        <div class="text-left">
                            <p class="mb-3">คุณต้องการอนุมัติผลงานนี้หรือไม่?</p>
                            <p class="mb-4 font-semibold text-gray-900">${escapeHtml(title)}</p>
                            <div class="bg-blue-50 border-l-4 border-blue-500 p-3 mb-3 rounded">
                                <p class="text-sm text-gray-700 mb-2">
                                    เป็นผลงานทางวิชาการที่ได้รับการเผยแพร่ตามหลักเกณฑ์ที่กำหนด
                                    ในการพิจารณาแต่งตั้งให้บุคคลดำรงตำแหน่งทางวิชาการ
                                </p>
                                <p class="text-sm">
                                    <a href="https://www.ratchakitcha.soc.go.th/DATA/PDF/2565/E/004/T_0022.PDF" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="text-blue-600 hover:text-blue-800 underline font-medium">
                                        ดูหลักเกณฑ์ ก.พ.อ. →
                                    </a>
                                </p>
                            </div>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'อนุมัติ',
                    cancelButtonText: 'ยกเลิก',
                    reverseButtons: true,
                    width: '600px'
                });

                if (result.isConfirmed) {
                    try {
                        // Show loading
                        Swal.fire({
                            title: 'กำลังอนุมัติ...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Call admin API to approve publication
                        const apiUrl = API.approvePublication + '/' + id;
                        const response = await fetch(apiUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            }
                        });

                        const approveResult = await response.json();

                        if (approveResult.success) {
                            await Swal.fire({
                                title: 'อนุมัติสำเร็จ!',
                                text: 'ผลงานได้รับการอนุมัติเรียบร้อยแล้ว',
                                icon: 'success',
                                confirmButtonText: 'ตกลง'
                            });

                            // Reload publications
                            await loadPublications();
                        } else {
                            throw new Error(approveResult.message || 'ไม่สามารถอนุมัติได้');
                        }
                    } catch (error) {
                        console.error('Approve error:', error);
                        Swal.fire({
                            title: 'เกิดข้อผิดพลาด!',
                            text: error.message || 'ไม่สามารถอนุมัติผลงานได้',
                            icon: 'error',
                            confirmButtonText: 'ตกลง'
                        });
                    }
                }
            }

            // Set publication approval status (3-level system: null, 0, 1)
            async function setApprovalStatus(id, status, title) {
                // Get status details for each approval level
                const statusInfo = {
                    1: {
                        title: 'ยืนยันการอนุมัติ',
                        text: 'ผลงานนี้ผ่านเกณฑ์ กพอ.',
                        icon: 'success',
                        confirmText: 'อนุมัติ',
                        confirmColor: '#10b981'
                    },
                    0: {
                        title: 'ยืนยันการไม่อนุมัติ',
                        text: 'ผลงานนี้ไม่ผ่านเกณฑ์ กพอ.',
                        icon: 'warning',
                        confirmText: 'ไม่อนุมัติ',
                        confirmColor: '#ef4444'
                    },
                    null: {
                        title: 'รีเซ็ตสถานะเป็น "ยังไม่ได้ตรวจสอบ"',
                        text: 'ผลงานนี้จะกลับสู่สถานะยังไม่ได้ตรวจสอบ',
                        icon: 'question',
                        confirmText: 'รีเซ็ต',
                        confirmColor: '#eab308'
                    }
                };

                const info = statusInfo[status];

                const result = await Swal.fire({
                    title: info.title,
                    html: `
                        <div class="text-left">
                            <p class="mb-3">${info.text}</p>
                            <p class="mb-4 font-semibold text-gray-900">${escapeHtml(title)}</p>
                            ${status === 1 ? `
                            <div class="bg-blue-50 border-l-4 border-blue-500 p-3 mb-3 rounded">
                                <p class="text-sm text-gray-700 mb-2">
                                    เป็นผลงานทางวิชาการที่ได้รับการเผยแพร่ตามหลักเกณฑ์ที่กำหนด
                                    ในการพิจารณาแต่งตั้งให้บุคคลดำรงตำแหน่งทางวิชาการ
                                </p>
                                <p class="text-sm">
                                    <a href="https://www.ratchakitcha.soc.go.th/DATA/PDF/2565/E/004/T_0022.PDF" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="text-blue-600 hover:text-blue-800 underline font-medium">
                                        ดูหลักเกณฑ์ ก.พ.อ. →
                                    </a>
                                </p>
                            </div>
                            ` : ''}
                        </div>
                    `,
                    icon: info.icon,
                    showCancelButton: true,
                    confirmButtonColor: info.confirmColor,
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: info.confirmText,
                    cancelButtonText: 'ยกเลิก',
                    reverseButtons: true,
                    width: '600px'
                });

                if (result.isConfirmed) {
                    try {
                        // Show loading
                        Swal.fire({
                            title: 'กำลังบันทึก...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Call API endpoint via AJAX
                        const apiUrl = API.setApprovalStatus + '/' + id;
                        const response = await fetch(apiUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                status: status
                            })
                        });

                        const result = await response.json();

                        if (result.success) {
                            await Swal.fire({
                                icon: 'success',
                                title: 'สำเร็จ',
                                text: 'อัปเดตสถานะการอนุมัติเรียบร้อยแล้ว',
                                timer: 1500,
                                showConfirmButton: false
                            });

                            // Reload publications to reflect changes
                            await loadPublications();
                        } else {
                            throw new Error(result.message || 'เกิดข้อผิดพลาด');
                        }
                    } catch (error) {
                        console.error('Error setting approval status:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: error.message || 'ไม่สามารถอัปเดตสถานะการอนุมัติได้'
                        });
                    }
                }
            }

            // Handle approval dropdown change
            async function handleApprovalChange(id, value, title) {
                const status = value === 'null' ? null : parseInt(value);
                await setApprovalStatus(id, status, title);
            }

            // Current publication being viewed
            let currentViewPublicationId = null;
            let currentViewPublicationData = null;

            // View publication details in read-only modal
            async function viewPublication(id) {
                try {
                    currentViewPublicationId = id;

                    // Show modal with loading state (ensure visible for reopen: clear inline display so class controls, then show)
                    const viewModal = document.getElementById('viewModal');
                    if (!viewModal) throw new Error('viewModal not found');
                    viewModal.classList.remove('hidden');
                    viewModal.style.removeProperty('display');
                    viewModal.style.display = 'flex';
                    viewModal.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';

                    // Show loading, hide content
                    const loadEl = document.getElementById('viewModalLoading');
                    const dataEl = document.getElementById('viewModalData');
                    if (loadEl) loadEl.classList.remove('hidden');
                    if (dataEl) dataEl.classList.add('hidden');

                    // Fetch publication data
                    const apiUrl = API.getPublication + '/' + id;
                    const response = await fetch(apiUrl);
                    const result = await response.json();

                    if (!result.success) {
                        throw new Error(result.message || 'ไม่สามารถโหลดข้อมูลได้');
                    }

                    currentViewPublicationData = result.data;
                    populateViewModal(result.data);

                    // Hide loading, show content
                    document.getElementById('viewModalLoading').classList.add('hidden');
                    document.getElementById('viewModalData').classList.remove('hidden');

                } catch (error) {
                    console.error('View publication error:', error);
                    closeViewModal();
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด!',
                        text: error.message || 'ไม่สามารถโหลดข้อมูลผลงานได้',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                }
            }

            // Populate view modal with publication data
            function populateViewModal(pub) {
                // Type badge
                const typeColors = {
                    'journal': 'bg-blue-100 text-blue-800',
                    'proceedings': 'bg-purple-100 text-purple-800',
                    'book': 'bg-emerald-100 text-emerald-800',
                    'thesis': 'bg-amber-100 text-amber-800',
                    'report': 'bg-cyan-100 text-cyan-800',
                    'other': 'bg-gray-100 text-gray-800'
                };
                const typeIcons = {
                    'journal': '📚',
                    'proceedings': '🎤',
                    'book': '📖',
                    'thesis': '📝',
                    'report': '📊',
                    'other': '📄'
                };
                const typeBadge = document.getElementById('view_type_badge');
                if (typeBadge) {
                    typeBadge.className = `inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-full ${typeColors[pub.publication_type] || typeColors['other']}`;
                    typeBadge.innerHTML = `${typeIcons[pub.publication_type] || '📄'} ${getPublicationTypeThai(pub.publication_type)}`;
                }

                // Status badge
                const statusBadge = document.getElementById('view_status_badge');
                if (statusBadge) {
                    if (pub.approve == 1) {
                        statusBadge.className = 'inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800';
                        statusBadge.innerHTML = '✓ ผ่านเกณฑ์';
                    } else if (pub.approve == 0) {
                        statusBadge.className = 'inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800';
                        statusBadge.innerHTML = '✗ ไม่ผ่านเกณฑ์';
                    } else {
                        statusBadge.className = 'inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800';
                        statusBadge.innerHTML = '⏳ ยังไม่ได้ตรวจสอบ';
                    }
                }

                // Title
                const titleEl = document.getElementById('view_title');
                if (titleEl) titleEl.textContent = pub.title || '-';

                // Authors
                const authorsContainer = document.getElementById('view_authors');
                if (authorsContainer) {
                    if (pub.authors && pub.authors.length > 0) {
                        authorsContainer.innerHTML = pub.authors.map((author, index) => {
                            const name = author.name || author.author_name || '-';
                            const email = author.email || '';
                            const affiliation = author.affiliation || '';
                            const isCorresponding = author.corresponding === '1' || author.corresponding === 1;

                            return `
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg ${isCorresponding ? 'border-l-4 border-indigo-500' : ''}">
                                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold text-sm">
                                    ${index + 1}
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">${escapeHtml(name)} ${isCorresponding ? '<span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full ml-2">Corresponding</span>' : ''}</p>
                                    ${email ? `<p class="text-sm text-gray-500">${escapeHtml(email)}</p>` : ''}
                                    ${affiliation ? `<p class="text-xs text-gray-400">${escapeHtml(affiliation)}</p>` : ''}
                                </div>
                            </div>
                        `;
                        }).join('');
                    } else {
                        const authorNames = pub.authors_names_thai || pub.authors_names_en || '-';
                        authorsContainer.innerHTML = `<p class="text-gray-700">${escapeHtml(authorNames)}</p>`;
                    }
                }

                // Source (use view_source or view_journal)
                const sourceEl = document.getElementById('view_source') || document.getElementById('view_journal');
                if (sourceEl) sourceEl.textContent = pub.source || '-';

                // Year
                const year = pub.publication_year ? parseInt(pub.publication_year) + 543 : '-';
                const yearEl = document.getElementById('view_year');
                if (yearEl) yearEl.textContent = year;

                // Publication Details
                const detailsContainer = document.getElementById('view_details');
                const detailsSection = document.getElementById('view_details_section');
                let detailsHtml = '';

                if (pub.volume) detailsHtml += createDetailItem('ปีที่ (Volume)', pub.volume);
                if (pub.issue) detailsHtml += createDetailItem('ฉบับที่ (Issue)', pub.issue);
                if (pub.pages) detailsHtml += createDetailItem('หน้า', pub.pages);
                if (pub.doi) detailsHtml += createDetailItem('DOI', pub.doi, true);
                if (pub.isbn) detailsHtml += createDetailItem('ISBN', pub.isbn);
                if (pub.publisher) detailsHtml += createDetailItem('สำนักพิมพ์', pub.publisher);
                if (pub.conference_name) detailsHtml += createDetailItem('ชื่อการประชุม', pub.conference_name);
                if (pub.conference_location) detailsHtml += createDetailItem('สถานที่', pub.conference_location);

                if (detailsContainer && detailsSection) {
                    if (detailsHtml) {
                        detailsContainer.innerHTML = detailsHtml;
                        detailsSection.classList.remove('hidden');
                    } else {
                        detailsSection.classList.add('hidden');
                    }
                }

                // Abstract
                const abstractSection = document.getElementById('view_abstract_section');
                const abstractEl = document.getElementById('view_abstract');
                if (abstractSection && abstractEl) {
                    if (pub.abstract) {
                        abstractEl.textContent = pub.abstract;
                        abstractSection.classList.remove('hidden');
                    } else {
                        abstractSection.classList.add('hidden');
                    }
                }

                // Keywords
                const keywordsSection = document.getElementById('view_keywords_section');
                const keywordsContainer = document.getElementById('view_keywords');
                if (keywordsSection && keywordsContainer) {
                    if (pub.keywords) {
                        const keywords = pub.keywords.split(',').map(k => k.trim()).filter(k => k);
                        keywordsContainer.innerHTML = keywords.map(kw =>
                            `<span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">${escapeHtml(kw)}</span>`
                        ).join('');
                        keywordsSection.classList.remove('hidden');
                    } else {
                        keywordsSection.classList.add('hidden');
                    }
                }

                // URLs
                const urlsSection = document.getElementById('view_urls_section');
                const urlsContainer = document.getElementById('view_urls');
                let urlsHtml = '';

                if (pub.url) {
                    urlsHtml += `<a href="${escapeHtml(pub.url)}" target="_blank" class="flex items-center gap-2 text-blue-600 hover:text-blue-800 hover:underline"><span>🔗</span> ${escapeHtml(pub.url)}</a>`;
                }
                if (pub.ref_url && pub.ref_url !== pub.url) {
                    urlsHtml += `<a href="${escapeHtml(pub.ref_url)}" target="_blank" class="flex items-center gap-2 text-blue-600 hover:text-blue-800 hover:underline"><span>📎</span> ${escapeHtml(pub.ref_url)}</a>`;
                }

                if (urlsSection && urlsContainer) {
                    if (urlsHtml) {
                        urlsContainer.innerHTML = urlsHtml;
                        urlsSection.classList.remove('hidden');
                    } else {
                        urlsSection.classList.add('hidden');
                    }
                }
            }

            // Helper function to create detail item
            function createDetailItem(label, value, isDoi = false) {
                const displayValue = isDoi ?
                    `<a href="https://doi.org/${escapeHtml(value)}" target="_blank" class="text-blue-600 hover:underline">${escapeHtml(value)}</a>` :
                    escapeHtml(value);
                return `
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500 mb-1">${label}</p>
                        <p class="font-semibold text-gray-900">${displayValue}</p>
                    </div>
                `;
            }

            // Close view modal (must allow reopening: only toggle visibility, don't leave broken state)
            function closeViewModal() {
                const viewModal = document.getElementById('viewModal');
                if (!viewModal) return;
                viewModal.classList.add('hidden');
                viewModal.style.display = 'none';
                viewModal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                currentViewPublicationId = null;
                currentViewPublicationData = null;
            }

            // Edit from view modal
            function editFromView() {
                if (currentViewPublicationData) {
                    closeViewModal();
                    openAddModalForEdit(currentViewPublicationData);
                } else if (currentViewPublicationId) {
                    closeViewModal();
                    editPublication(currentViewPublicationId);
                }
            }

            // Close view modal when clicking outside
            document.getElementById('viewModal')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeViewModal();
                }
            });

            // Show error message
            function showError(message) {
                const tbody = document.getElementById('publications-table');
                tbody.innerHTML = `
                <div class="flex flex-col items-center justify-center py-20">
                    <div class="w-32 h-32 bg-gradient-to-br from-rose-100 to-red-200 rounded-full flex items-center justify-center mb-6 shadow-inner">
                        <span class="text-6xl">😵</span>
                    </div>
                    <p class="text-xl font-bold text-rose-600 mb-2">เกิดข้อผิดพลาด</p>
                    <p class="text-sm text-slate-500 mb-6">${escapeHtml(message)}</p>
                    <button onclick="loadPublications()" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-semibold shadow-lg shadow-blue-500/30 hover:shadow-xl hover:-translate-y-0.5 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        ลองใหม่อีกครั้ง
                    </button>
                </div>
            `;
            }

            // ============================================================================
            // EDIT MODAL FUNCTIONS
            // ============================================================================

            // NOTE: editModal functions have been removed. 
            // Now using addModal with openAddModalForEdit() for both Add and Edit modes.

            // Close suggestions when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.relative')) {
                    document.querySelectorAll('.name-suggestions').forEach(el => {
                        el.classList.add('hidden');
                    });
                }
            });

            // ============================================================================
            // ADD/EDIT MODAL FUNCTIONS (Unified Form)
            // ============================================================================

            let addAuthorsCount = 0;
            let addUploadedFileData = null;
            let isEditMode = false; // Track if we're in edit mode
            let currentEditPublicationId = null; // Store current publication ID when editing
            let isEditLoading = false; // Guard to prevent concurrent edit triggers
            let currentEditLoadId = 0; // Token to cancel stale async matching loops

            // Remove any leftover SweetAlert loading/backdrop residue. A fast fire()/close()
            // (e.g. the edit-loading spinner) can leave a .swal2-container behind; after a few
            // open/close cycles these stack on top of the modal and hide it. Purge them all
            // and reset the body styles SweetAlert mutates so the modal is always visible.
            function purgeSwalResidue() {
                try {
                    if (window.Swal && typeof Swal.close === 'function' && Swal.isVisible && Swal.isVisible()) {
                        Swal.close();
                    }
                } catch (e) { /* ignore */ }
                document.querySelectorAll('.swal2-container').forEach(el => el.remove());
                document.body.classList.remove('swal2-shown', 'swal2-height-auto', 'swal2-no-backdrop');
                document.documentElement.classList.remove('swal2-shown', 'swal2-height-auto');
                document.body.style.removeProperty('padding-right');
            }

            // Open add modal (for new publication)
            function openAddModal() {
                console.log('Opening add modal (Add Mode)...');
                isEditMode = false;
                currentEditPublicationId = null;

                const addModal = document.getElementById('addModal');
                if (addModal) {
                    purgeSwalResidue();
                    addModal.style.display = 'flex';
                    addModal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';

                    // Update modal title and button for Add mode
                    document.getElementById('addModalTitle').textContent = 'เพิ่มผลงานวิจัย';
                    document.getElementById('addFormSubmitBtn').textContent = 'บันทึกผลงาน';
                    document.getElementById('add_publication_id').value = '';

                    // Initialize authors container with one empty author
                    document.getElementById('add_authors_container').innerHTML = '';
                    addAuthorsCount = 0;
                    addAddAuthor();

                    // Reset form
                    document.getElementById('addForm').reset();

                    // Reset publication type selection
                    document.querySelectorAll('#addModal .publication-type-card').forEach(card => {
                        card.classList.remove('selected');
                    });
                    document.getElementById('add_publication_type').value = '';

                    // Reset upload UI
                    document.getElementById('add_uploadProgress').classList.add('hidden');
                    document.getElementById('add_uploadedFile').classList.add('hidden');
                    document.getElementById('add_fileInput').value = '';
                    document.getElementById('add_ai-url-input').value = '';
                    addUploadedFileData = null;

                    // Hide "เปิดไฟล์ที่อัปโหลด" (only shown in Edit when ref points to file)
                    const existingLinkBlock = document.getElementById('add_existing_file_link');
                    if (existingLinkBlock) existingLinkBlock.classList.add('hidden');

                    // Hide all conditional fields
                    updateAddConditionalFields();
                }
            }

            // Resolve URL for uploaded file: local:filename or path containing downloadFile
            function getFileDownloadUrl(refUrl, url) {
                const r = (refUrl || url || '').trim();
                if (!r) return null;
                const base = (typeof BASE_URL !== 'undefined' ? BASE_URL : '').replace(/\/$/, '');
                if (r.startsWith('local:')) {
                    return API.downloadFile + '/' + r.slice(6).trim();
                }
                if (r.indexOf('downloadFile') !== -1) {
                    if (r.startsWith('http://') || r.startsWith('https://')) return r;
                    return (r.startsWith('/') ? base + r : API.downloadFile + '/' + r.replace(/^.*\/downloadFile\/?/, ''));
                }
                return null;
            }

            // Open add modal for editing (pre-fill with existing data)
            async function openAddModalForEdit(publication) {
                console.log('Opening add modal (Edit Mode)...');
                console.log('Publication data:', publication);

                isEditMode = true;
                currentEditPublicationId = publication.id;

                // Increment and capture load ID
                currentEditLoadId++;
                const loadId = currentEditLoadId;

                const addModal = document.getElementById('addModal');
                if (addModal) {
                    // Show robust CSS-based loading overlay
                    const loader = document.getElementById('loadingOverlay');
                    if (loader) loader.classList.remove('hidden');

                    try {
                        // Clear any stuck SweetAlert backdrop so it can't hide the modal after
                        // several open/close cycles (1st open works, 3rd doesn't, etc.).
                        purgeSwalResidue();
                        addModal.style.display = 'flex';
                        addModal.classList.remove('hidden');
                        document.body.style.overflow = 'hidden';

                        // Update modal title and button for Edit mode
                        document.getElementById('addModalTitle').textContent = 'แก้ไขผลงานวิจัย';
                        document.getElementById('addFormSubmitBtn').textContent = 'บันทึกการแก้ไข';
                        document.getElementById('add_publication_id').value = publication.id;

                        // Reset upload UI
                        document.getElementById('add_uploadProgress').classList.add('hidden');
                        document.getElementById('add_uploadedFile').classList.add('hidden');
                        document.getElementById('add_fileInput').value = '';
                        document.getElementById('add_ai-url-input').value = '';
                        addUploadedFileData = null;

                        // Fill form with publication data
                        document.getElementById('add_publication_type').value = publication.publication_type || '';
                        document.getElementById('add_title').value = publication.title || '';
                        document.getElementById('add_abstract').value = publication.abstract || '';
                        document.getElementById('add_source').value = publication.source || '';

                        // Convert CE year to BE year for display
                        const ceYear = publication.publication_year;
                        document.getElementById('add_publication_year').value = ceYear ? parseInt(ceYear) + 543 : '';
                        document.getElementById('add_publication_month').value = publication.publication_month || '';

                        document.getElementById('add_volume').value = publication.volume || '';
                        document.getElementById('add_issue').value = publication.issue || '';
                        document.getElementById('add_pages').value = publication.pages || '';
                        document.getElementById('add_doi').value = publication.doi || '';
                        document.getElementById('add_isbn').value = publication.isbn || '';
                        document.getElementById('add_publisher').value = publication.publisher || '';
                        document.getElementById('add_conference_name').value = publication.conference_name || '';
                        document.getElementById('add_conference_location').value = publication.conference_location || '';
                        document.getElementById('add_conference_date').value = publication.conference_date || '';
                        document.getElementById('add_book_title').value = publication.book_title || '';
                        document.getElementById('add_chapter').value = publication.chapter || '';
                        document.getElementById('add_editor').value = publication.editor || '';
                        document.getElementById('add_keywords').value = publication.keywords || '';
                        document.getElementById('add_ref_url').value = publication.ref_url || '';
                        document.getElementById('add_url').value = publication.url || '';

                        // Show "เปิดไฟล์ที่อัปโหลด" when ref_url/url/file_link points to an uploaded file
                        const fileRef = publication.ref_url || publication.file_link || publication.url;
                        const fileUrl = getFileDownloadUrl(fileRef, publication.url || publication.ref_url);
                        const existingLinkBlock = document.getElementById('add_existing_file_link');
                        const existingLinkAnchor = document.getElementById('add_existing_file_link_anchor');
                        if (existingLinkBlock && existingLinkAnchor) {
                            if (fileUrl) {
                                existingLinkAnchor.href = fileUrl;
                                existingLinkBlock.classList.remove('hidden');
                            } else {
                                existingLinkBlock.classList.add('hidden');
                            }
                        }

                        document.getElementById('add_notes').value = publication.notes || '';

                        // Select publication type card
                        document.querySelectorAll('#addModal .publication-type-card').forEach(card => {
                            card.classList.remove('selected');
                            if (card.dataset.type === publication.publication_type) {
                                card.classList.add('selected');
                            }
                        });

                        // Clear and add authors with 3-Step Matching (Email → Thai Name → English Name)
                        document.getElementById('add_authors_container').innerHTML = '';
                        addAuthorsCount = 0;

                        if (publication.authors && publication.authors.length > 0) {
                            console.log(`=== EDIT MODE: Processing ${publication.authors.length} authors with 3-Step Matching ===`);

                            // Process each author with comprehensive matching
                            for (let i = 0; i < publication.authors.length; i++) {
                                // Check if this load operation was cancelled or superseded
                                if (loadId !== currentEditLoadId || !isEditMode) {
                                    console.log('Edit load cancelled/superseded, aborting author matching.');
                                    return;
                                }

                                const author = publication.authors[i];
                                const authorName = author.name || author.author_name || '';
                                // getPublication returns the email as author_email — email mapping is
                                // the primary match, so fall back to author_email (was a blank string).
                                const authorEmail = author.email || author.author_email || '';
                                const authorAffiliation = author.affiliation || author.author_affiliation || '';

                                console.log(`\n--- Author ${i + 1}/${publication.authors.length} ---`);
                                console.log('Original data:', {
                                    name: authorName,
                                    email: authorEmail,
                                    user_id: author.user_id
                                });

                                // If author already has user_id/uid/is_user_matched, keep the existing match directly
                                const matchedUid = author.user_id || author.uid || (author.is_user_matched ? author.author_email : null);
                                if (matchedUid) {
                                    console.log(`✓ Author ${i + 1} already matched with user_id: ${matchedUid}`);
                                    addAddAuthor({
                                        author_name: authorName,
                                        author_email: authorEmail,
                                        author_affiliation: authorAffiliation,
                                        corresponding: author.corresponding === '1' || author.corresponding === 1,
                                        user_uid: matchedUid,
                                        matched: true
                                    });
                                    continue;
                                }

                                // Enrich author data (split name fields for better matching)
                                const enrichedAuthor = {
                                    author_name: authorName,
                                    name: authorName,
                                    author_email: authorEmail,
                                    email: authorEmail,
                                    author_affiliation: authorAffiliation,
                                    affiliation: authorAffiliation,
                                    corresponding: author.corresponding === '1' || author.corresponding === 1
                                };

                                // Apply ensureAuthorSplitFields if available
                                const processedAuthor = typeof window.ensureAuthorSplitFields === 'function' ?
                                    window.ensureAuthorSplitFields(enrichedAuthor) :
                                    enrichedAuthor;

                                console.log('Enriched author data:', processedAuthor);

                                let matchedUser = null;

                                // ============================================================
                                // STEP 1: Try to match by EMAIL first
                                // ============================================================
                                if (authorEmail && typeof window.searchAuthorByEmail === 'function') {
                                    console.log(`[Step 1] Searching by email: "${authorEmail}"...`);
                                    try {
                                        matchedUser = await window.searchAuthorByEmail(authorEmail);
                                        if (loadId !== currentEditLoadId || !isEditMode) {
                                            console.log('Edit load cancelled/superseded, aborting.');
                                            return;
                                        }
                                        if (matchedUser) {
                                            console.log(`✓ Author ${i + 1} MATCHED by email!`, matchedUser);
                                        } else {
                                            console.log(`✗ No match found by email`);
                                        }
                                    } catch (err) {
                                        console.warn(`✗ Email search failed:`, err);
                                    }
                                } else {
                                    console.log(`[Step 1] Skipped - No email or searchAuthorByEmail not available`);
                                }

                                // ============================================================
                                // STEP 2: Try to match by THAI NAME (if not matched by email)
                                // ============================================================
                                if (!matchedUser && authorName && typeof window.searchAuthorByThaiName === 'function') {
                                    console.log(`[Step 2] Searching by Thai name: "${authorName}"...`);
                                    try {
                                        // Split Thai name into first and last parts
                                        let thaiFirst = processedAuthor.thai_first_name;
                                        let thaiLast = processedAuthor.thai_last_name;

                                        // If not already split, split now
                                        if (!thaiFirst && typeof window.splitNameParts === 'function') {
                                            const parts = window.splitNameParts(authorName);
                                            thaiFirst = parts.firstName;
                                            thaiLast = parts.lastName;
                                            console.log(`  Split Thai name: first="${thaiFirst}", last="${thaiLast}"`);
                                        }

                                        matchedUser = await window.searchAuthorByThaiName({
                                            fullName: authorName,
                                            firstName: thaiFirst,
                                            lastName: thaiLast
                                        });
                                        if (loadId !== currentEditLoadId || !isEditMode) {
                                            console.log('Edit load cancelled/superseded, aborting.');
                                            return;
                                        }
                                        if (matchedUser) {
                                            console.log(`✓ Author ${i + 1} MATCHED by Thai name!`, matchedUser);
                                        } else {
                                            console.log(`✗ No match found by Thai name`);
                                        }
                                    } catch (err) {
                                        console.warn(`✗ Thai name search failed:`, err);
                                    }
                                } else if (!matchedUser) {
                                    console.log(`[Step 2] Skipped - No name or searchAuthorByThaiName not available`);
                                }

                                // ============================================================
                                // STEP 3: Try to match by ENGLISH NAME (if still not matched)
                                // ============================================================
                                if (!matchedUser && authorName && typeof window.searchAuthorByEnglishName === 'function') {
                                    console.log(`[Step 3] Searching by English name: "${authorName}"...`);
                                    try {
                                        // Split English name into first and last parts
                                        let engFirst = processedAuthor.gf_name;
                                        let engLast = processedAuthor.gl_name;

                                        // If not already split, split now
                                        if (!engFirst && typeof window.splitNameParts === 'function') {
                                            const parts = window.splitNameParts(authorName);
                                            engFirst = parts.firstName;
                                            engLast = parts.lastName;
                                            console.log(`  Split English name: first="${engFirst}", last="${engLast}"`);
                                        }

                                        matchedUser = await window.searchAuthorByEnglishName({
                                            fullName: authorName,
                                            firstName: engFirst,
                                            lastName: engLast
                                        });
                                        if (loadId !== currentEditLoadId || !isEditMode) {
                                            console.log('Edit load cancelled/superseded, aborting.');
                                            return;
                                        }
                                        if (matchedUser) {
                                            console.log(`✓ Author ${i + 1} MATCHED by English name!`, matchedUser);
                                        } else {
                                            console.log(`✗ No match found by English name`);
                                        }
                                    } catch (err) {
                                        console.warn(`✗ English name search failed:`, err);
                                    }
                                } else if (!matchedUser) {
                                    console.log(`[Step 3] Skipped - No name or searchAuthorByEnglishName not available`);
                                }

                                // ============================================================
                                // FINAL: Add author with matched or original data
                                // ============================================================
                                if (matchedUser) {
                                    // Successfully matched - use matched user data
                                    const matchedName = matchedUser.thai_name ||
                                        (matchedUser.gf_name && matchedUser.gl_name ? `${matchedUser.gf_name} ${matchedUser.gl_name}` : '') ||
                                        authorName;

                                    console.log(`✓✓ Author ${i + 1} FINAL: Using matched data`);
                                    addAddAuthor({
                                        author_name: matchedName,
                                        author_email: matchedUser.email || authorEmail,
                                        author_affiliation: matchedUser.affiliation || authorAffiliation || 'มหาวิทยาลัยราชภัฏอุตรดิตถ์',
                                        corresponding: author.corresponding === '1' || author.corresponding === 1,
                                        user_uid: matchedUser.uid || matchedUser.id || '',
                                        matched: true
                                    });
                                } else {
                                    // Not found in database - use original data
                                    console.log(`✗✗ Author ${i + 1} FINAL: No match found, using original data`);
                                    addAddAuthor({
                                        author_name: authorName,
                                        author_email: authorEmail,
                                        author_affiliation: authorAffiliation,
                                        corresponding: author.corresponding === '1' || author.corresponding === 1,
                                        user_uid: '',
                                        matched: false
                                    });
                                }
                            }

                            console.log(`\n=== EDIT MODE: Finished processing ${publication.authors.length} authors ===\n`);
                        } else {
                            console.log('No authors in publication, adding empty author field');
                            addAddAuthor(); // Add one empty author field
                        }

                        // Update conditional fields based on publication type
                        updateAddConditionalFields();
                    } catch (err) {
                        console.error('Error in openAddModalForEdit:', err);
                        // Close modal immediately to avoid leaving a broken backdrop visible
                        closeAddModal();
                        throw err;
                    } finally {
                        // Only hide loading overlay if this load hasn't been superseded
                        if (loadId === currentEditLoadId) {
                            if (loader) loader.classList.add('hidden');
                        }
                    }
                }
            }

            // Close add modal
            function closeAddModal() {
                const addModal = document.getElementById('addModal');
                if (addModal) {
                    addModal.classList.add('hidden');
                    addModal.style.display = 'none';
                }
                document.body.style.overflow = '';
                document.getElementById('addForm').reset();
                addUploadedFileData = null;

                // Reset edit mode
                isEditMode = false;
                currentEditPublicationId = null;
                // Increment currentEditLoadId to cancel any active async load loops
                currentEditLoadId++;

                // Hide loader overlay just in case
                const loader = document.getElementById('loadingOverlay');
                if (loader) loader.classList.add('hidden');

                // Reset upload UI and hide "เปิดไฟล์ที่อัปโหลด"
                document.getElementById('add_uploadProgress').classList.add('hidden');
                document.getElementById('add_uploadedFile').classList.add('hidden');
                document.getElementById('add_fileInput').value = '';
                document.getElementById('add_ai-url-input').value = '';
                const linkBlock = document.getElementById('add_existing_file_link');
                if (linkBlock) linkBlock.classList.add('hidden');
            }

            // Update conditional fields based on publication type (matching create.php style)
            function updateAddConditionalFields() {
                const pubType = document.getElementById('add_publication_type').value;
                const addModal = document.getElementById('addModal');
                if (!addModal) return;

                // Get all conditional fields within the add modal
                const conditionalFields = addModal.querySelectorAll('.conditional-field');

                conditionalFields.forEach(field => {
                    const allowedTypes = field.dataset.types ? field.dataset.types.split(',') : [];

                    if (pubType && allowedTypes.includes(pubType)) {
                        // Show field with animation
                        field.classList.remove('field-hidden');
                        field.classList.add('field-visible');
                        field.style.display = '';
                    } else {
                        // Hide field with animation
                        field.classList.add('field-hidden');
                        field.classList.remove('field-visible');
                        // Don't set display:none immediately to allow animation
                        setTimeout(() => {
                            if (field.classList.contains('field-hidden')) {
                                field.style.display = 'none';
                            }
                        }, 300);
                    }
                });
            }

            // Setup publication type selection
            function setupAddPublicationTypes() {
                const addModal = document.getElementById('addModal');
                if (!addModal) {
                    console.warn('addModal not found - publication type setup skipped');
                    return;
                }

                addModal.querySelectorAll('.publication-type-card').forEach(card => {
                    card.addEventListener('click', function() {
                        // Remove selection from all cards in addModal
                        addModal.querySelectorAll('.publication-type-card').forEach(c => {
                            c.classList.remove('selected');
                        });

                        // Add selection to clicked card
                        this.classList.add('selected');

                        // Set hidden input value
                        const type = this.getAttribute('data-type');
                        document.getElementById('add_publication_type').value = type;

                        // Update conditional fields
                        updateAddConditionalFields();
                    });
                });
                console.log('Publication type cards setup complete');
            }

            // Add author field
            function addAddAuthor(authorData = null) {
                addAuthorsCount++;
                const container = document.getElementById('add_authors_container');
                const authorId = 'add_author_' + addAuthorsCount;

                // Extract author name from various possible fields
                let authorName = '';
                let authorEmail = '';
                let authorAffiliation = '';
                let isMatched = false;
                let userUid = '';

                if (authorData) {
                    authorName = authorData.author_name ||
                        authorData.thai_name ||
                        authorData.name_th ||
                        authorData.english_name ||
                        authorData.name_en ||
                        authorData.name ||
                        '';
                    authorEmail = authorData.author_email ||
                        authorData.email ||
                        '';
                    authorAffiliation = authorData.author_affiliation ||
                        authorData.affiliation ||
                        '';
                    isMatched = authorData.matched === true;
                    userUid = authorData.user_uid || authorData.uid || '';
                }

                // Determine border color and badges based on match status (matching create.php style)
                const borderClass = isMatched ?
                    'border-green-500 bg-green-50' :
                    (authorData ? 'border-yellow-500 bg-yellow-50' : 'border-gray-300');

                // Build badges HTML matching publication-ai.js attachMatchBadges style
                let badgesHtml = '';
                if (authorData) {
                    if (isMatched) {
                        badgesHtml = `
                            <span class="matched-badge inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full">✓ พบข้อมูลในระบบ</span>
                            <span class="matched-id-badge inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded-full ml-2">${userUid ? 'รหัสผู้ใช้: ' + escapeHtml(userUid) : 'รหัสผู้ใช้ไม่ระบุ'}</span>
                        `;
                    } else {
                        badgesHtml = `<span class="new-badge inline-flex items-center px-2 py-1 text-xs font-medium text-yellow-700 bg-yellow-100 rounded-full">⚠ ผู้แต่งใหม่</span>`;
                    }
                }

                const authorDiv = document.createElement('div');
                authorDiv.className = 'author-row author-row-enter';
                authorDiv.id = authorId;

                authorDiv.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                    <div class="input-group">
                        <input type="text"
                               name="authors[${addAuthorsCount}][name]"
                               value="${escapeHtml(authorName)}"
                               placeholder="ชื่อผู้แต่ง"
                               required
                               class="w-full px-3 py-2 border ${borderClass} rounded-md focus:ring-blue-500 focus:border-blue-500 ${isMatched ? 'auto-filled' : ''}">
                        ${badgesHtml ? `<div class="flex items-center flex-wrap gap-1 mt-1">${badgesHtml}</div>` : ''}
                        <input type="hidden" name="authors[${addAuthorsCount}][user_uid]" value="${escapeHtml(userUid)}">
                    </div>
                    <div class="input-group">
                        <input type="email"
                               name="authors[${addAuthorsCount}][email]"
                               value="${escapeHtml(authorEmail)}"
                               placeholder="อีเมล (ไม่บังคับ)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="input-group flex items-start">
                        <input type="text"
                               name="authors[${addAuthorsCount}][affiliation]"
                               value="${escapeHtml(authorAffiliation)}"
                               placeholder="หน่วยงาน (ไม่บังคับ)"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <button type="button"
                                onclick="removeAddAuthor('${authorId}')"
                                class="ml-2 px-2 py-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <!-- Corresponding Author Checkbox -->
                <div class="flex items-center ml-1">
                    <input type="checkbox"
                           name="authors[${addAuthorsCount}][corresponding]"
                           value="1"
                           ${authorData && authorData.corresponding ? 'checked' : ''}
                           class="corresponding-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                    <label class="ml-2 text-sm font-medium text-gray-700">
                        ผู้แต่งที่ติดต่อได้ (Corresponding Author)
                    </label>
                </div>
            `;

                container.appendChild(authorDiv);

                // Update author count display
                updateAddAuthorCount();

                // Reinitialize author search for the new input after a small delay
                setTimeout(() => {
                    if (window.AuthorNameSearch) {
                        const $newInput = $(authorDiv).find('input[name*="[name]"]');
                        window.AuthorNameSearch.setupSingleNameInput($newInput);

                        // If this author is matched to a system user, render the chip so the
                        // "ผู้ใช้ในระบบ" state persists on reload (same as live selection), and keep
                        // the user link on the name input so the save serializer submits it.
                        if (isMatched && typeof window.AuthorNameSearch.renderChip === 'function') {
                            const $row = $(authorDiv);
                            if (userUid) {
                                $newInput.data('user-uid', userUid);
                                $row.find('input[name*="[email]"]').data('user-uid', userUid);
                            }
                            $row.data('matched-user', {
                                uid: userUid,
                                name: authorName,
                                email: authorEmail,
                                affiliation: authorAffiliation
                            });
                            window.AuthorNameSearch.renderChip($row);
                        }
                    }
                }, 100);
            }

            // Update author count display
            function updateAddAuthorCount() {
                const container = document.getElementById('add_authors_container');
                const count = container ? container.querySelectorAll('.author-row').length : 0;
                const statusElement = document.getElementById('add_authorStatus');
                if (statusElement) {
                    statusElement.textContent = count > 0 ? `${count} authors added` : '';
                }
            }

            // Remove author field
            function removeAddAuthor(authorId) {
                const element = document.getElementById(authorId);
                if (element) {
                    element.remove();
                    updateAddAuthorCount();
                }
            }

            // Process with AI for add form - uses functions from publication-ai.js
            async function processAddWithAI() {
                const fileInput = document.getElementById('add_fileInput');
                const urlInput = document.getElementById('add_ai-url-input');

                // Use AI_API_URL from publication-ai.js (must be loaded first)
                if (!window.AI_API_URL) {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถโหลด AI API configuration ได้ กรุณารีเฟรชหน้าเว็บ',
                        confirmButtonText: 'ตกลง'
                    });
                    return;
                }
                const AI_API_URL = window.AI_API_URL;

                // Check if file is uploaded or URL is provided
                let requestUrl = null;

                if (urlInput && urlInput.value.trim()) {
                    // Validate URL using function from publication-ai.js
                    if (typeof window.validateUrlForAI === 'function') {
                        const validation = window.validateUrlForAI(urlInput.value.trim());
                        if (!validation.valid) {
                            Swal.fire({
                                icon: 'error',
                                title: 'URL ไม่ถูกต้อง',
                                text: validation.message,
                                confirmButtonText: 'ตกลง'
                            });
                            return;
                        }
                        requestUrl = validation.url;
                    } else {
                        requestUrl = urlInput.value.trim();
                    }
                } else if (addUploadedFileData && addUploadedFileData.download_url) {
                    requestUrl = addUploadedFileData.download_url;
                } else if (fileInput && fileInput.files.length) {
                    // File selected but not uploaded yet - upload it first
                    fileInput.dispatchEvent(new Event('change'));
                    await new Promise(resolve => setTimeout(resolve, 1000)); // Wait for upload
                    if (addUploadedFileData && addUploadedFileData.download_url) {
                        requestUrl = addUploadedFileData.download_url;
                    }
                }

                if (!requestUrl) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'ไม่พบข้อมูล',
                        text: 'กรุณาอัปโหลดไฟล์หรือใส่ URL ของเอกสารก่อนใช้ AI',
                        confirmButtonText: 'ตกลง'
                    });
                    return;
                }

                // Show AI Waiting Modal (matching create.php style)
                const aiWaitingModal = document.getElementById('addAiWaitingModal');
                const aiWaitingMessage = document.getElementById('addAiWaitingMessage');
                if (aiWaitingModal) {
                    aiWaitingModal.classList.remove('hidden');
                    aiWaitingModal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                }
                if (aiWaitingMessage) {
                    aiWaitingMessage.innerHTML = `AI กำลังอ่านและวิเคราะห์เอกสารของคุณ<br>แล้วกรอกข้อมูลลงในฟอร์มให้อัตโนมัติ`;
                }

                console.log('Sending to AI API:', AI_API_URL);
                console.log('Request URL:', requestUrl);

                try {
                    // Single API call - wait for response (no polling)
                    const response = await fetch(AI_API_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            url: requestUrl
                        })
                    });

                    if (!response.ok) {
                        let errorDetails = '';
                        try {
                            const errorBody = await response.text();
                            console.error('AI API Error Response:', errorBody);
                            errorDetails = errorBody ? ` - ${errorBody.substring(0, 200)}` : '';
                        } catch (e) {
                            console.error('Could not read error response body');
                        }

                        let userMessage = `AI API error: ${response.status}`;
                        if (response.status === 500) {
                            userMessage = 'AI ไม่สามารถเข้าถึง URL นี้ได้ (500)\n\n' +
                                '📌 วิธีแก้ไข:\n' +
                                '1. Capture หน้าเว็บไซต์เป็น PDF\n' +
                                '   • กด Ctrl+P (หรือ Cmd+P บน Mac)\n' +
                                '   • เลือก "Save as PDF"\n' +
                                '   • บันทึกไฟล์\n\n' +
                                '2. หรือ Screenshot หน้าเว็บ\n' +
                                '   • กด Print Screen หรือใช้ Snipping Tool\n' +
                                '   • บันทึกเป็นรูปภาพ\n\n' +
                                '3. อัปโหลดไฟล์ที่ได้แทน URL';
                        } else if (response.status === 404) {
                            userMessage = 'ไม่พบ AI API endpoint (404)';
                        } else if (response.status === 403) {
                            userMessage = 'ไม่มีสิทธิ์เข้าถึง AI API (403)';
                        } else if (response.status === 408 || response.status === 504) {
                            userMessage = 'AI API หมดเวลา - URL อาจใช้เวลาโหลดนานเกินไป\n\nลอง Capture หน้าเว็บเป็น PDF แล้วอัปโหลดแทน';
                        }

                        throw new Error(userMessage + errorDetails);
                    }

                    const result = await response.json();
                    console.log('AI API Response:', result);

                    // Hide AI waiting modal
                    hideAddAiWaitingModal();

                    if (result.output) {
                        console.log('Transforming AI output:', result.output);

                        // Use transformAIResponse from publication-ai.js
                        let transformedData;
                        if (typeof window.transformAIResponse === 'function') {
                            transformedData = window.transformAIResponse(result.output);
                            // Adapt the transformed data for the add form format
                            transformedData = adaptAIDataForAddForm(transformedData);
                        } else {
                            // Fallback to old function if publication-ai.js not loaded
                            transformedData = transformAIResponseForAdd(result.output);
                        }

                        console.log('Transformed data:', transformedData);

                        await fillAddFormWithAIData(transformedData);

                        Swal.fire({
                            title: 'สำเร็จ!',
                            text: 'AI ได้กรอกข้อมูลให้เรียบร้อยแล้ว',
                            icon: 'success',
                            confirmButtonText: 'ตกลง'
                        });
                    } else {
                        console.error('No output in AI response:', result);
                        throw new Error('ไม่พบข้อมูลจาก AI - ' + (result.message || 'ไม่มี output ใน response'));
                    }
                } catch (error) {
                    // Hide AI waiting modal
                    hideAddAiWaitingModal();

                    console.error('AI processing error:', error);
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด!',
                        text: error.message || 'ไม่สามารถประมวลผลด้วย AI ได้',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                }
            }

            // Hide AI waiting modal helper function
            function hideAddAiWaitingModal() {
                const aiWaitingModal = document.getElementById('addAiWaitingModal');
                if (aiWaitingModal) {
                    aiWaitingModal.classList.add('hidden');
                    aiWaitingModal.style.display = 'none';
                }
                document.body.style.overflow = '';
            }

            // Adapt AI data from publication-ai.js format to add form format
            function adaptAIDataForAddForm(data) {
                // Transform keywords array to string
                let keywords = '';
                if (data.keywords && Array.isArray(data.keywords)) {
                    keywords = data.keywords.join(', ');
                } else if (data.keywords) {
                    keywords = data.keywords;
                }

                // Transform authors to add form format - preserve all name fields for matching
                const authors = (data.authors || []).map(author => ({
                    author_name: author.thai_name || author.name_th || author.english_name || author.name_en || '',
                    name_en: author.name_en || author.english_name || null,
                    name_th: author.name_th || author.thai_name || null,
                    english_name: author.english_name || author.name_en || null,
                    thai_name: author.thai_name || author.name_th || null,
                    // Preserve split name fields for author matching
                    gf_name: author.gf_name || null,
                    gl_name: author.gl_name || null,
                    thai_first_name: author.thai_first_name || null,
                    thai_last_name: author.thai_last_name || null,
                    author_email: author.email || null,
                    author_affiliation: author.affiliation || null
                }));

                return {
                    publication_type: data.publication_type || 'journal',
                    title: data.title_th || data.title_en || data.title || '',
                    source: data.source || null,
                    abstract: data.abstract_th || data.abstract_en || data.abstract || '',
                    keywords: keywords,
                    publication_year: data.year || null,
                    publication_month: data.month || data.month_en || data.month_th || null,
                    volume: data.volume || null,
                    issue: data.issue || null,
                    pages: data.pages || null,
                    doi: data.doi || null,
                    isbn: data.isbn || null,
                    publisher: data.publisher || null,
                    book_title: data.book_title || null,
                    chapter: data.chapter || null,
                    editor: data.editor || null,
                    conference_name: data.conference_name || null,
                    conference_location: data.conference_location || null,
                    conference_date: data.conference_date || null,
                    url: data.url || data.file_link || null,
                    ref_url: data.url || data.file_link || null,
                    authors: authors
                };
            }

            // Transform AI response to form data format (matching original transformAIResponse)
            function transformAIResponseForAdd(aiOutput) {
                // Helper function to split name into first and last parts
                function splitNameParts(fullName) {
                    if (!fullName || typeof fullName !== 'string') {
                        return {
                            firstName: '',
                            lastName: ''
                        };
                    }
                    const parts = fullName.trim().split(/\s+/).filter(Boolean);
                    if (parts.length === 0) return {
                        firstName: '',
                        lastName: ''
                    };
                    if (parts.length === 1) return {
                        firstName: parts[0],
                        lastName: ''
                    };
                    return {
                        firstName: parts[0],
                        lastName: parts[parts.length - 1]
                    };
                }

                // Transform authors
                const transformAuthors = (authorsEn, authorsTh) => {
                    const authors = [];
                    const maxLength = Math.max(
                        Array.isArray(authorsEn) ? authorsEn.length : 0,
                        Array.isArray(authorsTh) ? authorsTh.length : 0
                    );

                    for (let i = 0; i < maxLength; i++) {
                        const englishRaw = Array.isArray(authorsEn) && authorsEn[i] ? authorsEn[i].trim() : '';
                        const thaiRaw = Array.isArray(authorsTh) && authorsTh[i] ? authorsTh[i].trim() : '';

                        const {
                            firstName: enFirst,
                            lastName: enLast
                        } = splitNameParts(englishRaw);
                        const {
                            firstName: thFirst,
                            lastName: thLast
                        } = splitNameParts(thaiRaw);

                        // Use Thai name if available, otherwise English
                        const displayName = thaiRaw || englishRaw;

                        authors.push({
                            author_name: displayName,
                            name_en: englishRaw || null,
                            name_th: thaiRaw || null,
                            english_name: englishRaw || null,
                            thai_name: thaiRaw || null,
                            author_email: null,
                            author_affiliation: null
                        });
                    }

                    return authors;
                };

                // Transform keywords
                let keywords = '';
                if (aiOutput.keywords_th && Array.isArray(aiOutput.keywords_th)) {
                    keywords = aiOutput.keywords_th.join(', ');
                } else if (aiOutput.keywords_en && Array.isArray(aiOutput.keywords_en)) {
                    keywords = aiOutput.keywords_en.join(', ');
                } else if (aiOutput.keywords) {
                    keywords = Array.isArray(aiOutput.keywords) ? aiOutput.keywords.join(', ') : aiOutput.keywords;
                }

                return {
                    publication_type: aiOutput.type || 'journal',
                    title: aiOutput.title_th || aiOutput.title_en || '',
                    source: aiOutput.journalname ||
                        aiOutput.conference_name_th || aiOutput.conference_name_en ||
                        aiOutput.publisher_th || aiOutput.publisher_en ||
                        aiOutput.book_title_th || aiOutput.book_title_en || null,
                    abstract: aiOutput.abstract_th || aiOutput.abstract_en || '',
                    keywords: keywords,
                    publication_year: aiOutput.year_en || aiOutput.year_th || aiOutput.year || null,
                    publication_month: aiOutput.month_en || aiOutput.month_th || aiOutput.month || null,
                    volume: aiOutput.volume || null,
                    issue: aiOutput.issue || null,
                    pages: aiOutput.pages || null,
                    doi: aiOutput.doi || null,
                    isbn: aiOutput.isbn || null,
                    publisher: aiOutput.publisher_th || aiOutput.publisher_en || null,
                    book_title: aiOutput.book_title_th || aiOutput.book_title_en || null,
                    chapter: aiOutput.chapter || null,
                    editor: aiOutput.editor_th || aiOutput.editor_en || null,
                    conference_name: aiOutput.conference_name_th || aiOutput.conference_name_en || null,
                    conference_location: aiOutput.conference_location_th || aiOutput.conference_location_en || null,
                    conference_date: aiOutput.conference_date || null,
                    url: aiOutput.url || aiOutput.file_link || null,
                    ref_url: aiOutput.url || aiOutput.file_link || null,
                    authors: transformAuthors(aiOutput.authors_en, aiOutput.authors_th)
                };
            }

            // Fill add form with AI data (async for author matching)
            async function fillAddFormWithAIData(data) {
                console.log('=== FILL ADD FORM WITH AI DATA START ===');
                console.log('Data received:', JSON.stringify(data, null, 2));

                try {
                    // Publication type
                    if (data.publication_type) {
                        const typeField = document.getElementById('add_publication_type');
                        if (typeField) {
                            typeField.value = data.publication_type;
                            console.log('✓ Set publication_type:', data.publication_type);
                        }

                        // Select the type card
                        document.querySelectorAll('.publication-type-card').forEach(card => {
                            card.classList.remove('selected');
                            if (card.getAttribute('data-type') === data.publication_type) {
                                card.classList.add('selected');
                                console.log('✓ Selected type card:', data.publication_type);
                            }
                        });
                        updateAddConditionalFields();
                    }

                    // Title - handle both formats
                    const title = data.title_th || data.title_en || data.title;
                    if (title) {
                        const titleField = document.getElementById('add_title');
                        if (titleField) {
                            titleField.value = title;
                            console.log('✓ Set title:', title);
                        } else {
                            console.error('✗ Title field not found!');
                        }
                    }

                    // Source - handle both formats
                    const source = data.source || data.journal;
                    if (source) {
                        const sourceField = document.getElementById('add_source');
                        if (sourceField) {
                            sourceField.value = source;
                            console.log('✓ Set source:', source);
                        } else {
                            console.error('✗ Source field not found!');
                        }
                    }

                    // Abstract - handle both formats
                    const abstract = data.abstract_th || data.abstract_en || data.abstract;
                    if (abstract) {
                        const abstractField = document.getElementById('add_abstract');
                        if (abstractField) {
                            abstractField.value = abstract;
                            console.log('✓ Set abstract');
                        } else {
                            console.error('✗ Abstract field not found!');
                        }
                    }

                    // Year - convert CE to BE if needed
                    const yearValue = data.year || data.publication_year;
                    if (yearValue) {
                        const yearField = document.getElementById('add_publication_year');
                        if (yearField) {
                            let year = parseInt(yearValue, 10);
                            if (!isNaN(year) && year < 2500) {
                                year += 543;
                            }
                            if (!isNaN(year)) {
                                yearField.value = year;
                                console.log('✓ Set year:', year);
                            }
                        } else {
                            console.error('✗ Year field not found!');
                        }
                    }

                    // Month - format to 2 digits if needed
                    const monthValue = data.month || data.month_th || data.month_en || data.publication_month;
                    if (monthValue) {
                        const monthField = document.getElementById('add_publication_month');
                        if (monthField) {
                            let month = String(monthValue).trim();
                            // Convert month name to number if needed
                            const monthMap = {
                                'มกราคม': '01',
                                'กุมภาพันธ์': '02',
                                'มีนาคม': '03',
                                'เมษายน': '04',
                                'พฤษภาคม': '05',
                                'มิถุนายน': '06',
                                'กรกฎาคม': '07',
                                'สิงหาคม': '08',
                                'กันยายน': '09',
                                'ตุลาคม': '10',
                                'พฤศจิกายน': '11',
                                'ธันวาคม': '12',
                                'January': '01',
                                'February': '02',
                                'March': '03',
                                'April': '04',
                                'May': '05',
                                'June': '06',
                                'July': '07',
                                'August': '08',
                                'September': '09',
                                'October': '10',
                                'November': '11',
                                'December': '12',
                                'Jan': '01',
                                'Feb': '02',
                                'Mar': '03',
                                'Apr': '04',
                                'Jun': '06',
                                'Jul': '07',
                                'Aug': '08',
                                'Sep': '09',
                                'Oct': '10',
                                'Nov': '11',
                                'Dec': '12'
                            };
                            if (monthMap[month]) {
                                month = monthMap[month];
                            } else {
                                const monthNum = parseInt(month, 10);
                                if (!isNaN(monthNum) && monthNum >= 1 && monthNum <= 12) {
                                    month = monthNum < 10 ? '0' + monthNum : String(monthNum);
                                }
                            }
                            monthField.value = month;
                            console.log('✓ Set month:', month);
                        } else {
                            console.error('✗ Month field not found!');
                        }
                    }

                    // Publication details
                    if (data.volume) {
                        const volumeField = document.getElementById('add_volume');
                        if (volumeField) volumeField.value = data.volume;
                    }
                    if (data.issue) {
                        const issueField = document.getElementById('add_issue');
                        if (issueField) issueField.value = data.issue;
                    }
                    if (data.pages) {
                        const pagesField = document.getElementById('add_pages');
                        if (pagesField) pagesField.value = data.pages;
                    }
                    if (data.doi) {
                        const doiField = document.getElementById('add_doi');
                        if (doiField) doiField.value = data.doi;
                    }
                    if (data.isbn) {
                        const isbnField = document.getElementById('add_isbn');
                        if (isbnField) isbnField.value = data.isbn;
                    }
                    if (data.publisher) {
                        const publisherField = document.getElementById('add_publisher');
                        if (publisherField) publisherField.value = data.publisher;
                    }

                    // Conference fields
                    if (data.conference_name) {
                        const confNameField = document.getElementById('add_conference_name');
                        if (confNameField) confNameField.value = data.conference_name;
                    }
                    if (data.conference_location) {
                        const confLocationField = document.getElementById('add_conference_location');
                        if (confLocationField) confLocationField.value = data.conference_location;
                    }
                    if (data.conference_date) {
                        const confDateField = document.getElementById('add_conference_date');
                        if (confDateField) confDateField.value = data.conference_date;
                    }

                    // Book fields
                    if (data.book_title) {
                        const bookTitleField = document.getElementById('add_book_title');
                        if (bookTitleField) bookTitleField.value = data.book_title;
                    }
                    if (data.chapter) {
                        const chapterField = document.getElementById('add_chapter');
                        if (chapterField) chapterField.value = data.chapter;
                    }
                    if (data.editor) {
                        const editorField = document.getElementById('add_editor');
                        if (editorField) editorField.value = data.editor;
                    }

                    // Additional fields
                    if (data.keywords) {
                        const keywordsField = document.getElementById('add_keywords');
                        if (keywordsField) {
                            const keywordsValue = Array.isArray(data.keywords) ? data.keywords.join(', ') : data.keywords;
                            keywordsField.value = keywordsValue;
                        }
                    }
                    if (data.ref_url) {
                        const refUrlField = document.getElementById('add_ref_url');
                        if (refUrlField) refUrlField.value = data.ref_url;
                    }
                    if (data.url) {
                        const urlField = document.getElementById('add_url');
                        if (urlField) urlField.value = data.url;
                    }

                    // Fill authors with matching from database (using logic from publication-ai.js)
                    if (data.authors && Array.isArray(data.authors) && data.authors.length > 0) {
                        console.log('Filling authors:', data.authors.length, 'authors');
                        const authorsContainer = document.getElementById('add_authors_container');
                        if (authorsContainer) {
                            authorsContainer.innerHTML = '';
                            addAuthorsCount = 0;

                            // Process each author with matching
                            for (let i = 0; i < data.authors.length; i++) {
                                const authorData = data.authors[i];
                                console.log(`Processing author ${i + 1}:`, authorData);

                                // Ensure split fields using function from publication-ai.js
                                const enrichedAuthor = typeof window.ensureAuthorSplitFields === 'function' ?
                                    window.ensureAuthorSplitFields(authorData) :
                                    authorData;

                                // Try to match author in database
                                let matchedAuthor = null;

                                // Search by email first
                                const email = enrichedAuthor.email || enrichedAuthor.author_email || null;
                                if (email && typeof window.searchAuthorByEmail === 'function') {
                                    matchedAuthor = await window.searchAuthorByEmail(email);
                                    if (matchedAuthor) console.log(`✓ Author ${i + 1} matched by email`);
                                }

                                // Search by Thai name
                                const thaiName = enrichedAuthor.name_th || enrichedAuthor.thai_name || null;
                                if (!matchedAuthor && thaiName && typeof window.searchAuthorByThaiName === 'function') {
                                    let thaiFirst = enrichedAuthor.thai_first_name;
                                    let thaiLast = enrichedAuthor.thai_last_name;
                                    if (!thaiFirst && typeof window.splitNameParts === 'function') {
                                        const parts = window.splitNameParts(thaiName);
                                        thaiFirst = parts.firstName;
                                        thaiLast = parts.lastName;
                                    }
                                    matchedAuthor = await window.searchAuthorByThaiName({
                                        fullName: thaiName,
                                        firstName: thaiFirst,
                                        lastName: thaiLast
                                    });
                                    if (matchedAuthor) console.log(`✓ Author ${i + 1} matched by Thai name`);
                                }

                                // Search by English name
                                const engName = enrichedAuthor.name_en || enrichedAuthor.english_name || null;
                                if (!matchedAuthor && engName && typeof window.searchAuthorByEnglishName === 'function') {
                                    let engFirst = enrichedAuthor.gf_name;
                                    let engLast = enrichedAuthor.gl_name;
                                    if (!engFirst && typeof window.splitNameParts === 'function') {
                                        const parts = window.splitNameParts(engName);
                                        engFirst = parts.firstName;
                                        engLast = parts.lastName;
                                    }
                                    matchedAuthor = await window.searchAuthorByEnglishName({
                                        fullName: engName,
                                        firstName: engFirst,
                                        lastName: engLast
                                    });
                                    if (matchedAuthor) console.log(`✓ Author ${i + 1} matched by English name`);
                                }

                                // Merge matched data with AI data
                                const finalAuthorData = matchedAuthor ? {
                                    ...enrichedAuthor,
                                    author_name: matchedAuthor.thai_name || matchedAuthor.english_name || enrichedAuthor.author_name,
                                    name_th: matchedAuthor.thai_name || enrichedAuthor.name_th,
                                    name_en: matchedAuthor.english_name || enrichedAuthor.name_en,
                                    author_email: matchedAuthor.email || enrichedAuthor.author_email,
                                    author_affiliation: matchedAuthor.affiliation || matchedAuthor.organization || enrichedAuthor.author_affiliation,
                                    user_uid: matchedAuthor.user_uid || matchedAuthor.uid || null,
                                    matched: true
                                } : {
                                    ...enrichedAuthor,
                                    matched: false
                                };

                                addAddAuthor(finalAuthorData);

                                if (!matchedAuthor) {
                                    console.log(`✗ Author ${i + 1} not found in database`);
                                }
                            }

                            console.log('✓ Authors processed and filled:', data.authors.length);
                        } else {
                            console.error('✗ Authors container not found!');
                        }
                    } else {
                        console.log('No authors to fill');
                    }

                    console.log('=== FILL ADD FORM WITH AI DATA COMPLETE ===');
                } catch (error) {
                    console.error('=== ERROR FILLING FORM ===');
                    console.error('Error:', error);
                    console.error('Stack:', error.stack);
                    throw error;
                }
            }

            // Handle file upload for add form
            document.getElementById('add_fileInput')?.addEventListener('change', async function(e) {
                if (this.files.length > 0) {
                    const file = this.files[0];
                    const maxSize = 10 * 1024 * 1024; // 10MB

                    if (file.size > maxSize) {
                        Swal.fire({
                            icon: 'error',
                            title: 'ไฟล์ใหญ่เกินไป',
                            text: 'ขนาดไฟล์ต้องไม่เกิน 10MB',
                            confirmButtonText: 'ตกลง'
                        });
                        this.value = '';
                        return;
                    }

                    // Show upload progress
                    document.getElementById('add_uploadProgress').classList.remove('hidden');
                    document.getElementById('add_uploadedFile').classList.add('hidden');

                    try {
                        const formData = new FormData();
                        formData.append('file', file);

                        const response = await fetch(API.uploadFile, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const result = await response.json();

                        if (result.success && result.file) {
                            addUploadedFileData = result.file;
                            document.getElementById('add_uploadProgress').classList.add('hidden');
                            document.getElementById('add_uploadedFile').classList.remove('hidden');
                            document.getElementById('add_fileName').textContent = result.file.original_name || file.name;
                            document.getElementById('add_ref_url').value = result.file.ref_url || result.file.download_url || '';
                        } else {
                            throw new Error(result.message || 'อัปโหลดไม่สำเร็จ');
                        }
                    } catch (error) {
                        console.error('Upload error:', error);
                        document.getElementById('add_uploadProgress').classList.add('hidden');
                        Swal.fire({
                            icon: 'error',
                            title: 'อัปโหลดไม่สำเร็จ',
                            text: error.message || 'เกิดข้อผิดพลาดในการอัปโหลด',
                            confirmButtonText: 'ตกลง'
                        });
                    }
                }
            });

            // Remove uploaded file
            function removeAddUploadedFile() {
                addUploadedFileData = null;
                document.getElementById('add_fileInput').value = '';
                document.getElementById('add_uploadedFile').classList.add('hidden');
                document.getElementById('add_ref_url').value = '';
            }

            // Handle add form submission
            document.getElementById('addForm')?.addEventListener('submit', async (e) => {
                e.preventDefault();

                const formData = new FormData(e.target);
                const publicationType = formData.get('publication_type');

                if (!publicationType) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'กรุณาเลือกประเภทผลงาน',
                        text: 'กรุณาเลือกประเภทผลงานก่อนบันทึก',
                        confirmButtonText: 'ตกลง'
                    });
                    return;
                }

                // Collect authors data
                const authors = [];
                const authorNames = document.querySelectorAll('#add_authors_container input[name*="[name]"]');

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

                        // Include user link if available. The matched user-uid may live on the
                        // name input, the email input (email-autocomplete), or the hidden
                        // authors[..][user_uid] field — check all so the link is never lost.
                        const $nameInput = $(nameInput);
                        const $emailInput = $(emailInput);
                        const $uidHidden = $(container.querySelector('input[name*="[user_uid]"]'));
                        const userId = $nameInput.data('user-id') || $emailInput.data('user-id');
                        const userUid = $nameInput.data('user-uid') || $emailInput.data('user-uid') || ($uidHidden.length ? $uidHidden.val() : '');

                        if (userId) {
                            authorData.author_id = userId;
                        }
                        if (userUid) {
                            authorData.uid = userUid;
                            authorData.user_uid = userUid;
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
                        title: isEditMode ? 'กำลังอัพเดท...' : 'กำลังบันทึก...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Determine API endpoint based on mode
                    let apiUrl;
                    if (isEditMode && currentEditPublicationId) {
                        apiUrl = API.updatePublication + '/' + currentEditPublicationId;
                    } else {
                        apiUrl = API.savePublication;
                    }

                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        await Swal.fire({
                            title: 'บันทึกสำเร็จ!',
                            text: isEditMode ? 'แก้ไขผลงานเรียบร้อยแล้ว' : 'เพิ่มผลงานเรียบร้อยแล้ว',
                            icon: 'success',
                            confirmButtonText: 'ตกลง'
                        });

                        // Close modal and reload data
                        closeAddModal();
                        await loadPublications();
                    } else {
                        throw new Error(result.message || 'ไม่สามารถบันทึกได้');
                    }
                } catch (error) {
                    console.error('Save error:', error);
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด!',
                        text: error.message || 'ไม่สามารถบันทึกได้',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                }
            });

            // Initialize add modal functionality
            setupAddPublicationTypes();

            // Close modal when clicking outside
            document.getElementById('addModal')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeAddModal();
                }
            });

            // Event listeners with safety checks
            document.getElementById('search-input')?.addEventListener('input', filterPublications);
            document.getElementById('type-filter')?.addEventListener('change', filterPublications);

            // Status filter event listener with debug
            const statusFilter = document.getElementById('status-filter');
            if (statusFilter) {
                console.log('Status filter element found, adding event listener');
                statusFilter.addEventListener('change', function(e) {
                    console.log('Status filter changed to:', e.target.value);
                    filterPublications();
                });
            } else {
                console.error('Status filter element not found!');
            }

            // Initialize - load publications when page is ready
            console.log('=== PAGE INITIALIZATION ===');
            console.log('BASE_URL:', BASE_URL);
            loadPublications().catch(error => {
                console.error('Failed to load publications:', error);
            });
        </script>

        <!-- Author Search Script -->
        <script src="<?= base_url('assets/js/author-search.js') ?>"></script>

        <!-- Email Autocomplete Script -->
        <script src="<?= base_url('assets/js/email-autocomplete.js') ?>"></script>

        <!-- Publication AI Script (for validation functions) -->
        <script>window.N8N_EXTRACT_ARTICLE_URL = <?= json_encode(config(\Config\N8n::class)->extractArticleUrl(), JSON_UNESCAPED_SLASHES) ?>;</script>
        <script src="<?= base_url('assets/js/publication-ai.js?v=' . time()) ?>"></script>

        </main>
    </div>
    </div>

    <!-- View Publication Modal (Read-only) -->
    <div id="viewModal" class="hidden fixed inset-0 w-full min-h-screen z-[9999] flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/70 p-4" style="top:0;left:0;right:0;bottom:0;">
        <div class="view-modal-card relative w-full max-w-4xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl overflow-hidden my-auto">
            <!-- Modal Header -->
            <div class="flex-shrink-0 flex justify-between items-center px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-indigo-600 to-purple-600">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span>📄</span> รายละเอียดผลงานวิจัย
                </h2>
                <button type="button" onclick="closeViewModal();" class="w-10 h-10 flex items-center justify-center rounded-full bg-white/20 hover:bg-white/30 text-white transition-colors" title="ปิด">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body (scrollable) -->
            <div id="viewModalContent" class="flex-1 min-h-0 overflow-y-auto px-6 py-5 space-y-5">
                <!-- Loading state -->
                <div id="viewModalLoading" class="flex flex-col items-center justify-center py-12">
                    <div class="loading-spinner mb-4"></div>
                    <p class="text-gray-500">กำลังโหลดข้อมูล...</p>
                </div>

                <!-- Content (hidden until loaded) -->
                <div id="viewModalData" class="hidden space-y-5">
                    <!-- Publication Type & Status Badges -->
                    <div class="flex items-center gap-3 flex-wrap">
                        <div id="view_type_badge"></div>
                        <div id="view_status_badge"></div>
                    </div>

                    <!-- Basic Info -->
                    <div class="bg-white border border-gray-200 rounded-xl p-5">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <span>📌</span> ข้อมูลพื้นฐาน
                        </h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500 mb-1">ชื่อผลงาน</p>
                                <p id="view_title" class="font-medium text-gray-900"></p>
                            </div>
                            <div>
                                <p class="text-gray-500 mb-1">ผู้แต่ง</p>
                                <div id="view_authors" class="font-medium text-gray-900"></div>
                            </div>
                            <div>
                                <p class="text-gray-500 mb-1">ปีที่เผยแพร่</p>
                                <p id="view_year" class="font-medium text-gray-900"></p>
                            </div>
                            <div>
                                <p class="text-gray-500 mb-1">ชื่อวารสาร/การประชุม</p>
                                <p id="view_journal" class="font-medium text-gray-900"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="bg-white border border-gray-200 rounded-xl p-5">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <span>ℹ️</span> รายละเอียดเพิ่มเติม
                        </h3>
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500 mb-1">ฉบับที่/เล่มที่</p>
                                <p id="view_volume_issue" class="font-medium text-gray-900"></p>
                            </div>
                            <div>
                                <p class="text-gray-500 mb-1">หน้า</p>
                                <p id="view_pages" class="font-medium text-gray-900"></p>
                            </div>
                            <div>
                                <p class="text-gray-500 mb-1">สำนักพิมพ์</p>
                                <p id="view_publisher" class="font-medium text-gray-900"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Abstract -->
                    <div id="view_abstract_section" class="bg-white border border-gray-200 rounded-xl p-5">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <span>📝</span> บทคัดย่อ
                        </h3>
                        <p id="view_abstract" class="text-gray-700 leading-relaxed text-sm"></p>
                    </div>

                    <!-- Keywords -->
                    <div id="view_keywords_section" class="bg-white border border-gray-200 rounded-xl p-5">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <span>🏷️</span> คำสำคัญ
                        </h3>
                        <div id="view_keywords" class="flex flex-wrap gap-2"></div>
                    </div>

                    <!-- URLs -->
                    <div id="view_urls_section" class="bg-white border border-gray-200 rounded-xl p-5">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <span>🔗</span> ลิงก์
                        </h3>
                        <div id="view_urls" class="space-y-2"></div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer (ปิดได้เฉพาะกากบาทข้างบน) -->
            <div class="flex-shrink-0 flex gap-3 justify-end px-6 py-4 border-t border-gray-100 bg-gray-50">
            </div>
        </div>
    </div>

</body>

</html>