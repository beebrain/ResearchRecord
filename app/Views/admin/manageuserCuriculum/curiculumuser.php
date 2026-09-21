<!doctype html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการหลักสูตรผู้ใช้</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.min.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-common.css') ?>">
    <style>
        .user-card {
            transition: all 0.2s ease;
        }

        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .curriculum-card {
            transition: all 0.2s ease;
        }

        .curriculum-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .member-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .member-badge.primary {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .member-badge.secondary {
            background-color: #f3f4f6;
            color: #4b5563;
        }

        .scrollable-area {
            max-height: calc(100vh - 300px);
            overflow-y: auto;
        }

        .scrollable-area::-webkit-scrollbar {
            width: 8px;
        }

        .scrollable-area::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .scrollable-area::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .scrollable-area::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Chair teacher autocomplete */
        .chair-ac-wrap {
            position: relative;
        }

        .chair-ac-input {
            min-height: 44px;
        }

        .chair-ac-list {
            position: absolute;
            z-index: 60;
            left: 0;
            right: 0;
            margin-top: 4px;
            max-height: 280px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .chair-ac-option {
            min-height: 44px;
            cursor: pointer;
            transition: background-color 150ms ease;
        }

        .chair-ac-option:hover,
        .chair-ac-option.is-active {
            background: #ecfdf5;
        }

        .chair-ac-option:focus {
            outline: 2px solid #059669;
            outline-offset: -2px;
        }

        .chair-ac-badge-chair {
            background: #dcfce7;
            color: #166534;
        }

        .chair-ac-badge-coordinator {
            background: #dbeafe;
            color: #1e40af;
        }

        .chair-ac-badge-member {
            background: #f3f4f6;
            color: #374151;
        }

        /* Drag and Drop Styles */
        .draggable {
            cursor: move;
            cursor: grab;
            user-select: none;
        }

        .draggable:active {
            cursor: grabbing;
        }

        .dragging {
            opacity: 0.5;
            transform: rotate(2deg);
            z-index: 1000;
        }

        .drop-zone {
            transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
        }

        .curriculum-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            overflow: hidden;
        }

        .curriculum-card.is-expanded {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .curriculum-card-header {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            width: 100%;
            text-align: left;
            padding: 0.85rem 1rem;
            cursor: pointer;
            background: transparent;
            border: 0;
            min-height: 44px;
        }

        .curriculum-card-header:hover {
            background: rgba(15, 23, 42, 0.03);
        }

        .curriculum-card-header:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: -2px;
        }

        .curriculum-chevron {
            width: 1.1rem;
            height: 1.1rem;
            flex-shrink: 0;
            margin-top: 0.15rem;
            color: #64748b;
            transition: transform 0.15s ease;
        }

        .curriculum-card.is-expanded .curriculum-chevron {
            transform: rotate(90deg);
        }

        .curriculum-card-body {
            display: none;
            padding: 0 0.85rem 0.85rem;
            border-top: 1px solid #e2e8f0;
        }

        .curriculum-card.is-expanded .curriculum-card-body {
            display: block;
        }

        .role-drop-zone {
            min-height: 4.5rem;
            border: 1.5px dashed #cbd5e1;
            border-radius: 0.65rem;
            padding: 0.65rem 0.75rem;
            background: #fff;
        }

        .role-drop-zone[data-role="coordinator"] {
            border-color: #c4b5fd;
            background: #faf5ff;
        }

        .role-drop-zone[data-role="instructor"] {
            border-color: #93c5fd;
            background: #f8fafc;
        }

        .role-drop-zone.drag-over {
            border-style: solid;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .role-drop-zone[data-role="coordinator"].drag-over {
            background: #f3e8ff !important;
            border-color: #7c3aed !important;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
        }

        .role-drop-zone[data-role="instructor"].drag-over {
            background: #dbeafe !important;
            border-color: #2563eb !important;
        }

        .role-drop-zone.drag-over::before {
            display: block;
            text-align: center;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 6px;
            border-radius: 6px;
            margin-bottom: 8px;
        }

        .role-drop-zone[data-role="instructor"].drag-over::before {
            content: 'วางเพื่อเพิ่มเป็นอาจารย์ประจำ';
            color: #1d4ed8;
            background-color: rgba(37, 99, 235, 0.08);
        }

        .role-drop-zone[data-role="coordinator"].drag-over::before {
            content: 'วางเพื่อตั้งเป็นผู้รับผิดชอบ';
            color: #6d28d9;
            background-color: rgba(124, 58, 237, 0.08);
        }

        .curriculum-card.drop-zone.drag-over:not(.is-expanded) {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
            background: #eff6ff;
        }

        .curriculum-card.drop-zone.drag-over:not(.is-expanded)::after {
            content: 'วางเพื่อเพิ่มเป็นอาจารย์ประจำ (หรือคลิกเปิดเพื่อเลือกช่อง)';
            display: block;
            margin: 0 1rem 0.75rem;
            padding: 0.4rem 0.6rem;
            border-radius: 0.4rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #1d4ed8;
            background: rgba(37, 99, 235, 0.08);
            text-align: center;
        }

        .member-role-select {
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
            border-radius: 0.375rem;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
            max-width: 8.5rem;
        }

        .member-role-select:focus {
            outline: 2px solid #2563eb;
            outline-offset: 1px;
        }
    </style>
</head>

<body class="min-h-full">
    <div class="min-h-full bg-gray-50">
        <?php
        // Set page title and subtitle for header partial
        $pageTitle = 'จัดการหลักสูตรผู้ใช้';
        $pageSubtitle = 'User Curriculum Management';
        ?>

        <!-- Top Navigation Bar -->
        <?= view('admin/partials/header', ['pageTitle' => $pageTitle, 'pageSubtitle' => $pageSubtitle]) ?>

        <div class="flex">
            <!-- Sidebar Navigation -->
            <?= view('admin/partials/navigation') ?>

            <!-- Main Content -->
            <main class="flex-1 p-6">
                <header class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">จัดการหลักสูตรผู้ใช้</h2>
                    <p class="text-gray-600">ดูและจัดการข้อมูลผู้ใช้และหลักสูตร</p>
                </header>

                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                    <!-- Left Column: User Search and List (2 columns) -->
                    <section class="lg:col-span-2 bg-white rounded-lg shadow-lg p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                </svg>
                                รายชื่อผู้ใช้
                                <span id="user-count-badge" class="ml-2 text-sm font-normal text-gray-500"></span>
                            </h3>

                            <!-- Faculty Filter for Users -->
                            <div class="mb-4">
                                <label for="user-faculty-filter" class="block text-sm font-medium text-gray-700 mb-2">กรองตามคณะ:</label>
                                <select id="user-faculty-filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                                    <option value="all">ทุกคณะ</option>
                                    <option value="null">ไม่มีสังกัดคณะ</option>
                                </select>
                            </div>

                            <!-- Search Box -->
                            <div class="relative">
                                <input type="text" id="user-search" placeholder="ค้นหาชื่อ, อีเมล, หรือคณะ..."
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Users List -->
                        <div id="users-list" class="scrollable-area space-y-3">
                            <div class="text-center text-gray-500 py-8">
                                <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <p>กำลังโหลดข้อมูล...</p>
                            </div>
                        </div>
                    </section>

                    <!-- Right Column: Curriculum by Faculty (3 columns) -->
                    <section class="lg:col-span-3 bg-white rounded-lg shadow-lg p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z" />
                                </svg>
                                หลักสูตรตามคณะ
                            </h3>

                            <!-- Faculty Filter for Curriculums -->
                            <div class="mb-3">
                                <label for="curriculum-faculty-filter" class="block text-sm font-medium text-gray-700 mb-2">กรองตามคณะ:</label>
                                <select id="curriculum-faculty-filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                                    <option value="all">ทุกคณะ</option>
                                    <option value="null">ไม่มีสังกัดคณะ</option>
                                </select>
                            </div>

                            <p class="text-xs text-slate-600 rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                                คลิกชื่อหลักสูตรเพื่อเปิดรายชื่ออาจารย์ · ลากลงช่อง <span class="font-medium text-violet-700">ผู้รับผิดชอบ</span>
                                หรือ <span class="font-medium text-blue-700">อาจารย์ประจำ</span> · ลากลงแถบชื่อที่ยุบอยู่ = เพิ่มเป็นอาจารย์ประจำ
                            </p>
                        </div>

                        <!-- Curriculums List -->
                        <div id="curriculums-list" class="scrollable-area space-y-4">
                            <div class="text-center text-gray-500 py-8">
                                <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <p>กำลังโหลดข้อมูล...</p>
                            </div>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <!-- jQuery Library (Local) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Set BASE_URL for the external script -->
    <script>
        window.BASE_URL = '<?= rtrim(base_url(), '/') ?>';
    </script>
    <script src="<?= base_url('assets/js/app-routes.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/app-routes.js') ?: time() ?>"></script>
    <script src="<?= base_url('assets/js/chair-teacher-autocomplete.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/chair-teacher-autocomplete.js') ?: time() ?>"></script>
    <script src="<?= base_url('assets/js/curriculum-user-manager-v2.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/curriculum-user-manager-v2.js') ?: time() ?>"></script>

    <!-- Chair Selection Modal -->
    <div id="chairModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" role="dialog" aria-modal="true" aria-labelledby="chairModalTitle">
        <div class="relative top-12 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 id="chairModalTitle" class="text-xl font-bold text-gray-900">ตั้งประธานหลักสูตร</h3>
                <button type="button" onclick="closeChairModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100" aria-label="ปิด">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="mb-4 rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                <p class="text-sm text-gray-600">หลักสูตรที่กำลังตั้งประธาน</p>
                <p id="chairModalCurriculumName" class="font-medium text-gray-900"></p>
                <input type="hidden" id="chairModalCurriculumId">
            </div>
            <div class="mb-3">
                <label for="chairTeacherSearch" class="block text-sm font-medium text-gray-700 mb-2">ค้นหาอาจารย์ในมหาวิทยาลัย</label>
                <div id="chairTeacherAutocomplete" class="chair-ac-wrap"></div>
                <p class="text-xs text-gray-500 mt-2">พิมพ์ชื่อหรืออีเมลอย่างน้อย 2 ตัวอักษร — ระบบจะแสดงตำแหน่งประธาน/ผู้รับผิดชอบในหลักสูตรอื่น (ถ้ามี)</p>
            </div>
            <div id="chairConflictWarning" class="hidden mb-4 rounded-lg border border-amber-300 bg-amber-50 px-3 py-3 text-sm text-amber-900" role="alert">
                <p class="font-medium flex items-start gap-2">
                    <svg class="w-5 h-5 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path>
                    </svg>
                    <span>คำเตือน: อาจารย์ท่านนี้มีตำแหน่งในหลักสูตรอื่นแล้ว</span>
                </p>
                <ul id="chairConflictList" class="mt-2 ml-7 list-disc space-y-1 text-amber-800"></ul>
            </div>
            <div class="flex flex-wrap justify-end gap-2 relative z-[70]">
                <button type="button" onclick="clearChairSelection()"
                    class="px-4 py-2 min-h-[44px] bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    ล้าง / ไม่ระบุประธาน
                </button>
                <button type="button" onclick="closeChairModal()"
                    class="px-4 py-2 min-h-[44px] bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    ยกเลิก
                </button>
                <button type="button" id="chairSaveBtn" onclick="saveCurriculumChair()"
                    class="px-4 py-2 min-h-[44px] bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    บันทึก
                </button>
            </div>
        </div>
    </div>
</body>

</html>