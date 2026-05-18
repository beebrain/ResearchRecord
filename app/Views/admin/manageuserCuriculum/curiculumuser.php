<!doctype html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการหลักสูตรผู้ใช้</title>
    <link rel="stylesheet" href="<?= base_url('/public/assets/css/tailwind.min.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin-common.css') ?>">
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
            min-height: 100px;
            transition: all 0.2s ease;
        }

        .drop-zone.drag-over {
            background-color: #dbeafe !important;
            border-color: #3b82f6 !important;
            border-width: 2px;
            transform: scale(1.02);
        }

        .drop-zone.drag-over::before {
            content: 'วางที่นี่';
            display: block;
            text-align: center;
            color: #3b82f6;
            font-weight: 600;
            padding: 8px;
            background-color: rgba(59, 130, 246, 0.1);
            border-radius: 4px;
            margin-bottom: 8px;
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
                            <div class="mb-4">
                                <label for="curriculum-faculty-filter" class="block text-sm font-medium text-gray-700 mb-2">กรองตามคณะ:</label>
                                <select id="curriculum-faculty-filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                                    <option value="all">ทุกคณะ</option>
                                    <option value="null">ไม่มีสังกัดคณะ</option>
                                </select>
                            </div>
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
    <script src="<?= base_url('public/assets/js/vendor/jquery-3.6.0.min.js') ?>"></script>

    <!-- SweetAlert2 Library (Local) -->
    <script src="<?= base_url('public/assets/js/vendor/sweetalert2.min.js') ?>"></script>

    <!-- Set BASE_URL for the external script -->
    <script>
        window.BASE_URL = '<?= rtrim(base_url(), '/') ?>';
    </script>

    <!-- Include the curriculum user manager script -->
    <script src="<?= base_url('public/assets/js/curriculum-user-manager-v2.js') ?>"></script>

    <!-- Chair Selection Modal -->
    <div id="chairModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-900">ตั้งประธานหลักสูตร</h3>
                <button onclick="closeChairModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-2">หลักสูตร: <span id="chairModalCurriculumName" class="font-medium text-gray-900"></span></p>
                <input type="hidden" id="chairModalCurriculumId">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">ประธานหลักสูตร</label>
                <select id="chairSelect" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    <option value="">-- ไม่ระบุ --</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">เลือกประธานหลักสูตรจากสมาชิกในหลักสูตรนี้เท่านั้น</p>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeChairModal()"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    ยกเลิก
                </button>
                <button type="button" onclick="saveCurriculumChair()"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    บันทึก
                </button>
            </div>
        </div>
    </div>
</body>

</html>