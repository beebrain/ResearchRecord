<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สรุปผลงานวิจัยตามหลักสูตร - Publication Summary</title>

    <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.min.css') ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-common.css') ?>">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const BASE_URL = '<?= rtrim(base_url(), '/') ?>';
        const API_ENDPOINTS = {
            summaryData: '<?= site_url('admin/publications/summary-data') ?>',
            getPublication: '<?= site_url('admin/publications/get') ?>',
            getCurricula: '<?= site_url('admin/getCurricula') ?>',
            pdfSummary: '<?= site_url('admin/publications/pdf-summary') ?>',
            downloadFile: '<?= site_url('utility/downloadFile') ?>'
        };
    </script>

    <style>
        body {
            font-family: 'Sarabun', sans-serif;
        }

        .status-badge {
            transition: all 0.3s ease;
        }

        .curriculum-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .curriculum-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .detail-section {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .warning-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .5;
            }
        }

        /* Modal container - ensure proper height */
        #detail-modal>div {
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        /* Custom scrollbar for modal - always visible */
        #modal-content {
            overflow-y: auto !important;
            overflow-x: hidden !important;
            flex: 1;
            min-height: 0;
            max-height: calc(90vh - 80px);
            /* Subtract header height */
            scrollbar-width: auto;
            scrollbar-color: #94a3b8 #f1f5f9;
            -webkit-overflow-scrolling: touch;
            /* Smooth scrolling on iOS */
            overscroll-behavior: contain;
            /* Prevent scroll chaining */
        }

        #modal-content::-webkit-scrollbar {
            width: 12px;
        }

        #modal-content::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 6px;
            margin: 4px;
        }

        #modal-content::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 6px;
            border: 2px solid #f1f5f9;
            min-height: 30px;
        }

        #modal-content::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        #modal-content::-webkit-scrollbar-thumb:active {
            background: #475569;
        }

        /* Prevent body scrolling when modal is active */
        body.modal-active {
            overflow: hidden !important;
            position: fixed;
            width: 100%;
            height: 100%;
        }

        /* Publication Detail Modal Scrollbar Styling */
        #publication-modal-content {
            overflow-y: auto !important;
            overflow-x: hidden !important;
            max-height: calc(90vh - 80px);
            /* Subtract header height */
            scrollbar-width: auto;
            scrollbar-color: #94a3b8 #f1f5f9;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
        }

        #publication-modal-content::-webkit-scrollbar {
            width: 12px;
        }

        #publication-modal-content::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 6px;
            margin: 4px;
        }

        #publication-modal-content::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 6px;
            border: 2px solid #f1f5f9;
            min-height: 30px;
        }

        #publication-modal-content::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        #publication-modal-content::-webkit-scrollbar-thumb:active {
            background: #475569;
        }
    </style>
</head>

<body class="min-h-full">
    <div class="min-h-full bg-gray-50">
        <?php
        // Set page title and subtitle for header partial
        $pageTitle = 'สรุปผลงานวิจัยตามหลักสูตร';
        $pageSubtitle = 'Publication Summary by Curriculum';
        ?>

        <!-- Top Navigation Bar -->
        <?= view('admin/partials/header', ['pageTitle' => $pageTitle, 'pageSubtitle' => $pageSubtitle]) ?>

        <div class="flex">
            <!-- Sidebar Navigation -->
            <?= view('admin/partials/navigation') ?>

            <!-- Main Content -->
            <main class="flex-1 p-4">
                <!-- Page Header -->
                <div class="mb-4">
                    <h2 class="text-xl font-bold text-gray-900 mb-1">สรุปผลงานวิจัยตามหลักสูตร</h2>
                    <p class="text-sm text-gray-600">ติดตามสถานะผลงานวิจัยของผู้รับผิดชอบหลักสูตรในรอบ 5 ปี</p>
                </div>

                <!-- Two Column Layout -->
                <div class="flex gap-4">
                    <!-- Left Column: Faculty List with Status Counts -->
                    <div class="w-72 flex-shrink-0">
                        <div class="bg-white rounded-lg shadow p-4 sticky top-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">คณะและสถานะหลักสูตร</h3>
                            <div id="faculty-list" class="space-y-2">
                                <!-- Faculty items will be populated here -->
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Curriculum Cards -->
                    <div class="flex-1 min-w-0">
                        <!-- Summary Stats (Compact) -->
                        <div class="grid grid-cols-3 gap-3 mb-4">
                            <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                <p class="text-xs font-medium text-green-700">ปลอดภัย</p>
                                <p id="safe-curriculums" class="text-2xl font-bold text-green-600">0</p>
                            </div>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                                <p class="text-xs font-medium text-yellow-700">ใกล้หมดอายุ</p>
                                <p id="warning-curriculums" class="text-2xl font-bold text-yellow-600">0</p>
                            </div>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                                <p class="text-xs font-medium text-red-700">ไม่ผ่าน/หมดอายุ</p>
                                <p id="danger-curriculums" class="text-2xl font-bold text-red-600">0</p>
                            </div>
                        </div>

                        <!-- Search Box -->
                        <div class="bg-white rounded-lg shadow p-3 mb-4">
                            <input type="text" id="search-input" placeholder="🔍 ค้นหาหลักสูตร..." class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Curriculum Cards Grid -->
                        <div id="curriculum-grid" class="flex flex-col gap-4 mb-6">
                            <!-- Cards will be populated here -->
                        </div>
                    </div>
                </div>

                <!-- Detail Modal (Curriculum) -->
                <div id="detail-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4" onclick="handleModalBackdropClick(event)">
                    <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] flex flex-col" onclick="event.stopPropagation()">
                        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center z-10 shadow-sm">
                            <h3 id="modal-title" class="text-xl font-bold text-gray-900"></h3>
                            <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded p-1" title="ปิด (ESC)">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div id="modal-content" class="p-6 flex-1" style="overflow-y: auto; overflow-x: hidden; max-height: calc(90vh - 80px);">
                            <!-- Content will be populated here -->
                        </div>
                    </div>
                </div>

                <!-- Publication Detail Modal (Nested - Higher z-index) -->
                <div id="publication-detail-modal" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center p-4" onclick="handlePublicationModalBackdropClick(event)" style="z-index: 9999;">
                    <div class="bg-white rounded-lg max-w-5xl w-full max-h-[90vh] flex flex-col shadow-2xl" onclick="event.stopPropagation()" style="z-index: 10000;">
                        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center shadow-sm" style="z-index: 10001;">
                            <h3 id="publication-modal-title" class="text-xl font-bold text-gray-900"></h3>
                            <button onclick="closePublicationDetailModal()" class="text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded p-1" title="ปิด (ESC)">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div id="publication-modal-content" class="p-6 flex-1" style="overflow-y: auto; overflow-x: hidden; max-height: calc(90vh - 80px);">
                            <!-- Content will be populated here -->
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- PDFMake Library with Thai Fonts (project root pdfmake/, not under public/) -->
    <script src="<?= pdfmake_url('build/pdfmake.min.js') ?>"></script>
    <script src="<?= pdfmake_url('build/vfs_fonts.js') ?>"></script>
    <script>
        if (typeof pdfMake !== 'undefined') {
        // Register Thai fonts (following EvaluateDocument.js pattern)
        pdfMake.fonts = {
            Sarabun: {
                normal: "THSarabunNew.ttf",
                bold: "THSarabunNew Bold.ttf",
                italics: "THSarabunNew Italic.ttf",
                bolditalics: "THSarabunNew BoldItalic.ttf",
            },
            Roboto: {
                normal: "Roboto-Regular.ttf",
                bold: "Roboto-Medium.ttf",
                italics: "Roboto-Italic.ttf",
                bolditalics: "Roboto-MediumItalic.ttf",
            }
        };
        }
    </script>

    <script src="<?= base_url('assets/js/publication-summary.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/publication-summary.js') ?: time() ?>"></script>

    <!-- PDF Generation Script (Separate File) -->
    <script src="<?= base_url('assets/js/publication-summary-pdf.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/publication-summary-pdf.js') ?: time() ?>"></script>
</body>

</html>