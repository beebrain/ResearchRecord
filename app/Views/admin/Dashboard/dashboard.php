<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ระบบบริหารจัดการข้อมูล</title>
    <link rel="stylesheet" href="<?= asset_url('css/tailwind.min.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="<?= asset_url('css/admin-common.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/browser-popup-fix.css') ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="<?= asset_url('js/modal-handler.js') ?>"></script>
    <style>
        .stat-card {
            transition: all 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .chart-container {
            position: relative;
            height: 280px;
        }
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .badge-bachelor { background-color: #dbeafe; color: #1e40af; }
        .badge-master { background-color: #dcfce7; color: #166534; }
        .badge-doctoral { background-color: #fef3c7; color: #92400e; }
    </style>
</head>

<body class="min-h-full">
    <div class="min-h-full bg-gradient-to-br from-slate-50 to-blue-50">
        <?php
        $pageTitle = 'Admin Dashboard';
        $pageSubtitle = session()->get('institution_name') ?? 'ระบบบริหารจัดการข้อมูล';
        ob_start();
        ?>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2" onclick="window.location.href='<?= site_url('admin/publications/add') ?>'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            เพิ่มผลงานวิจัย
        </button>
        <?php $headerActions = ob_get_clean(); ?>

        <!-- Top Navigation Bar -->
        <?= view('admin/partials/header', [
            'pageTitle' => $pageTitle,
            'pageSubtitle' => $pageSubtitle,
            'headerActions' => $headerActions
        ]) ?>

        <div class="flex">
            <!-- Sidebar Navigation -->
            <?= view('admin/partials/navigation') ?>

            <!-- Main Content -->
            <main class="flex-1 p-6">
                <!-- Dashboard Header -->
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-1">ภาพรวมระบบ</h2>
                    <p class="text-gray-500 text-sm">สรุปข้อมูลสถิติและการวิเคราะห์ทั้งหมด</p>
                </div>

                <!-- Row 1: Main Statistics Cards -->
                <div id="main-stats-row" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
                    <!-- Total Publications -->
                    <div class="stat-card bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">ผลงานวิจัยทั้งหมด</p>
                                <p id="total-publications" class="text-3xl font-bold text-gray-900 mt-1">
                                    <span class="loading-spinner inline-block"></span>
                                </p>
                            </div>
                            <div class="p-3 rounded-xl bg-blue-50">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center text-xs">
                            <span id="pub-this-year" class="text-blue-600 font-medium">-</span>
                            <span class="text-gray-400 ml-1">ในปีนี้</span>
                        </div>
                    </div>

                    <!-- Total Authors -->
                    <div class="stat-card bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">นักวิจัย/ผู้แต่ง</p>
                                <p id="total-authors" class="text-3xl font-bold text-gray-900 mt-1">
                                    <span class="loading-spinner inline-block"></span>
                                </p>
                            </div>
                            <div class="p-3 rounded-xl bg-green-50">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center text-xs">
                            <span id="edu-percentage" class="text-green-600 font-medium">-</span>
                            <span class="text-gray-400 ml-1">มีประวัติการศึกษา</span>
                        </div>
                    </div>

                    <!-- Total Curricula -->
                    <div class="stat-card bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">หลักสูตร</p>
                                <p id="total-curricula" class="text-3xl font-bold text-gray-900 mt-1">
                                    <span class="loading-spinner inline-block"></span>
                                </p>
                            </div>
                            <div class="p-3 rounded-xl bg-purple-50">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        </div>
                        <div id="curricula-breakdown" class="mt-3 flex items-center gap-2 text-xs">
                            <span class="px-2 py-0.5 rounded badge-bachelor">ป.ตรี: <span id="cur-bachelor">-</span></span>
                            <span class="px-2 py-0.5 rounded badge-master">ป.โท: <span id="cur-master">-</span></span>
                            <span class="px-2 py-0.5 rounded badge-doctoral">ป.เอก: <span id="cur-doctoral">-</span></span>
                        </div>
                    </div>

                    <!-- Active Faculties (Super Admin Only) -->
                    <div id="faculties-card" class="stat-card bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">คณะ/วิทยาลัย</p>
                                <p id="active-faculties" class="text-3xl font-bold text-gray-900 mt-1">
                                    <span class="loading-spinner inline-block"></span>
                                </p>
                            </div>
                            <div class="p-3 rounded-xl bg-amber-50">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-3 text-xs text-gray-400">ที่เปิดใช้งานในระบบ</div>
                    </div>
                </div>

                <!-- Row 2: Secondary Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
                    <!-- Admission Forms -->
                    <div class="stat-card bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">แบบฟอร์มรับ นศ.</p>
                                <p id="total-admission" class="text-3xl font-bold text-gray-900 mt-1">
                                    <span class="loading-spinner inline-block"></span>
                                </p>
                            </div>
                            <div class="p-3 rounded-xl bg-cyan-50">
                                <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div id="admission-breakdown" class="mt-3 flex items-center gap-2 text-xs">
                            <span class="text-gray-500">Draft: <span id="adm-draft" class="font-medium text-gray-700">-</span></span>
                            <span class="text-gray-400">|</span>
                            <span class="text-gray-500">Submitted: <span id="adm-submitted" class="font-medium text-blue-600">-</span></span>
                            <span class="text-gray-400">|</span>
                            <span class="text-gray-500">Approved: <span id="adm-approved" class="font-medium text-green-600">-</span></span>
                        </div>
                    </div>

                    <!-- Education Completion -->
                    <div class="stat-card bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">ความครบถ้วนประวัติศึกษา</p>
                                <p id="edu-completion-pct" class="text-3xl font-bold text-gray-900 mt-1">
                                    <span class="loading-spinner inline-block"></span>
                                </p>
                            </div>
                            <div class="p-3 rounded-xl bg-rose-50">
                                <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 14l9-5-9-5-9 5 9 5z"></path>
                                    <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div id="edu-progress-bar" class="bg-rose-500 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1"><span id="edu-with">0</span> / <span id="edu-total">0</span> คน</p>
                        </div>
                    </div>

                    <!-- Publications by Type Summary -->
                    <div class="stat-card bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภทผลงาน</p>
                                <p id="pub-type-count" class="text-3xl font-bold text-gray-900 mt-1">
                                    <span class="loading-spinner inline-block"></span>
                                </p>
                            </div>
                            <div class="p-3 rounded-xl bg-indigo-50">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div id="pub-types-breakdown" class="mt-3 text-xs text-gray-500">
                            กำลังโหลด...
                        </div>
                    </div>

                    <!-- Publications This Year -->
                    <div class="stat-card bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">ผลงานปี <?= date('Y') ?></p>
                                <p id="pub-year-count" class="text-3xl font-bold text-gray-900 mt-1">
                                    <span class="loading-spinner inline-block"></span>
                                </p>
                            </div>
                            <div class="p-3 rounded-xl bg-emerald-50">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-3 text-xs text-gray-400">
                            เทียบกับปีที่แล้ว: <span id="year-comparison" class="font-medium">-</span>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Publications by Year Chart -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">ผลงานตามปี</h3>
                                <p class="text-xs text-gray-500">แนวโน้มการตีพิมพ์ 5 ปีย้อนหลัง</p>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="year-chart"></canvas>
                        </div>
                    </div>

                    <!-- Publications by Type Chart -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">ผลงานตามประเภท</h3>
                                <p class="text-xs text-gray-500">การกระจายประเภทผลงานวิจัย</p>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="type-chart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Row 4: More Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Publications by Faculty Chart -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">ผลงานตามคณะ</h3>
                                <p class="text-xs text-gray-500">จำนวนผลงานในแต่ละคณะ</p>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="faculty-chart"></canvas>
                        </div>
                    </div>

                    <!-- Admission Form Status Chart -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">สถานะแบบฟอร์มรับนักศึกษา</h3>
                                <p class="text-xs text-gray-500">การกระจายสถานะแบบฟอร์ม</p>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="admission-chart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Row 5: Curriculum Readiness Section -->
                <div id="curriculum-readiness-section" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">ความพร้อมหลักสูตรในการเปิดรับนักศึกษา</h3>
                            <p class="text-xs text-gray-500">สถานะความพร้อมของแต่ละหลักสูตร ปีการศึกษา <span id="readiness-year"><?= date('Y') + 543 ?></span></p>
                        </div>
                        <div class="flex items-center gap-3">
                            <select id="readiness-year-filter" class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:ring-blue-500 focus:border-blue-500">
                                <?php for ($y = date('Y') + 544; $y >= date('Y') + 540; $y--): ?>
                                <option value="<?= $y ?>" <?= $y == date('Y') + 543 ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                            <a href="<?= site_url('admin/admission') ?>" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                จัดการทั้งหมด →
                            </a>
                        </div>
                    </div>

                    <!-- Readiness Summary Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg p-4 border border-green-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-green-600 font-medium">พร้อมเปิดรับ</p>
                                    <p id="readiness-ready" class="text-2xl font-bold text-green-700 mt-1">-</p>
                                </div>
                                <div class="p-2 bg-green-100 rounded-lg">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-lg p-4 border border-amber-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-amber-600 font-medium">พร้อมบางส่วน</p>
                                    <p id="readiness-partial" class="text-2xl font-bold text-amber-700 mt-1">-</p>
                                </div>
                                <div class="p-2 bg-amber-100 rounded-lg">
                                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-red-50 to-rose-50 rounded-lg p-4 border border-red-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-red-600 font-medium">ยังไม่พร้อม</p>
                                    <p id="readiness-not-ready" class="text-2xl font-bold text-red-700 mt-1">-</p>
                                </div>
                                <div class="p-2 bg-red-100 rounded-lg">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-4 border border-blue-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-blue-600 font-medium">คะแนนเฉลี่ย</p>
                                    <p id="readiness-avg-score" class="text-2xl font-bold text-blue-700 mt-1">-</p>
                                </div>
                                <div class="p-2 bg-blue-100 rounded-lg">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Curriculum Readiness Table -->
                    <div class="overflow-x-auto">
                        <table id="curriculum-readiness-table" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หลักสูตร</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">ระดับ</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">อาจารย์</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะฟอร์ม</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">แผนรับ</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">คะแนนความพร้อม</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody id="curriculum-readiness-body" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                        <div class="loading-spinner mx-auto mb-2"></div>
                                        กำลังโหลดข้อมูล...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Row 6: Curriculum Publications Summary (5 Years) -->
                <div id="curriculum-publications-section" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">สรุปผลงานวิจัยตามหลักสูตร</h3>
                            <p class="text-xs text-gray-500">ติดตามสถานะผลงานวิจัยของผู้รับผิดชอบหลักสูตรในรอบ 5 ปี (<?= date('Y') - 4 ?> - <?= date('Y') ?>)</p>
                        </div>
                        <a href="<?= site_url('admin/publications/summary') ?>" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            ดูรายละเอียด →
                        </a>
                    </div>

                    <!-- Curriculum Publications Summary Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-lg p-4 border border-indigo-100">
                            <p class="text-xs text-indigo-600 font-medium">หลักสูตรทั้งหมด</p>
                            <p id="cur-pub-total-curricula" class="text-2xl font-bold text-indigo-700 mt-1">-</p>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg p-4 border border-green-100">
                            <p class="text-xs text-green-600 font-medium">หลักสูตรที่มีผลงาน</p>
                            <p id="cur-pub-with-pubs" class="text-2xl font-bold text-green-700 mt-1">-</p>
                        </div>
                        <div class="bg-gradient-to-br from-red-50 to-rose-50 rounded-lg p-4 border border-red-100">
                            <p class="text-xs text-red-600 font-medium">หลักสูตรไม่มีผลงาน</p>
                            <p id="cur-pub-without-pubs" class="text-2xl font-bold text-red-700 mt-1">-</p>
                        </div>
                        <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-lg p-4 border border-blue-100">
                            <p class="text-xs text-blue-600 font-medium">ผลงานรวม 5 ปี</p>
                            <p id="cur-pub-total" class="text-2xl font-bold text-blue-700 mt-1">-</p>
                        </div>
                        <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-lg p-4 border border-amber-100">
                            <p class="text-xs text-amber-600 font-medium">% ครอบคลุม</p>
                            <p id="cur-pub-coverage" class="text-2xl font-bold text-amber-700 mt-1">-</p>
                        </div>
                    </div>

                    <!-- Yearly Trend Mini Chart -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">แนวโน้มผลงานรายปี</h4>
                        <div class="h-32">
                            <canvas id="curriculum-yearly-chart"></canvas>
                        </div>
                    </div>

                    <!-- Curriculum Publications Table -->
                    <div class="overflow-x-auto">
                        <table id="curriculum-publications-table" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หลักสูตร</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">ระดับ</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">อาจารย์</th>
                                    <?php for ($y = date('Y') - 4; $y <= date('Y'); $y++): ?>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><?= $y ?></th>
                                    <?php endfor; ?>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">รวม</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">แนวโน้ม</th>
                                </tr>
                            </thead>
                            <tbody id="curriculum-publications-body" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                                        <div class="loading-spinner mx-auto mb-2"></div>
                                        กำลังโหลดข้อมูล...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Row 7: Faculty Summary Table (Super Admin Only) -->
                <div id="faculty-summary-section" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">สรุปข้อมูลรายคณะ</h3>
                            <p class="text-xs text-gray-500">ภาพรวมข้อมูลของแต่ละคณะ</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table id="faculty-summary-table" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">คณะ</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">หลักสูตร</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">อาจารย์</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">ผลงานวิจัย</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">แบบฟอร์มรับ นศ.</th>
                                </tr>
                            </thead>
                            <tbody id="faculty-summary-body" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                        <div class="loading-spinner mx-auto mb-2"></div>
                                        กำลังโหลดข้อมูล...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Row 6: Recent Publications Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">ผลงานวิจัยล่าสุด</h3>
                            <p class="text-xs text-gray-500">10 ผลงานที่เพิ่มเข้าระบบล่าสุด</p>
                        </div>
                        <a href="<?= site_url('admin/publications/manage') ?>" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            ดูทั้งหมด →
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table id="recent-publications-table" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อผลงาน</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้สร้าง</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">ปี</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">คณะ</th>
                                </tr>
                            </thead>
                            <tbody id="recent-publications-body" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                        <div class="loading-spinner mx-auto mb-2"></div>
                                        กำลังโหลดข้อมูล...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Pass base URL and API endpoints to JavaScript
        // Use site_url() which CodeIgniter handles routing automatically
        const API_ENDPOINTS = {
            // New Dashboard APIs
            statistics: '<?= site_url('api/dashboard/stats') ?>',
            summary: '<?= site_url('api/dashboard/summary') ?>',
            publicationTypes: '<?= site_url('api/dashboard/publication-types') ?>',
            admissionStats: '<?= site_url('api/dashboard/admission-stats') ?>',
            educationStats: '<?= site_url('api/dashboard/education-stats') ?>',
            facultySummary: '<?= site_url('api/dashboard/faculty-summary') ?>',
            recentPublications: '<?= site_url('api/dashboard/recent-publications') ?>',
            curriculumReadiness: '<?= site_url('api/dashboard/curriculum-readiness') ?>',
            curriculumPublications: '<?= site_url('api/dashboard/curriculum-publications') ?>',
            // Legacy APIs
            facultyData: '<?= site_url('api/dashboard/faculty-data') ?>',
            yearData: '<?= site_url('api/dashboard/year-data') ?>'
        };
        
        // Debug: Log endpoints for troubleshooting
        if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
            console.log('API Endpoints:', API_ENDPOINTS);
        }
    </script>
    <script src="<?= base_url('assets/js/browser-popup-fix.js') ?>"></script>
    <script src="<?= base_url('assets/js/admin-dashboard.js') ?>"></script>
</body>

</html>
