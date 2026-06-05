<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'เพิ่มผลงานวิจัย' ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.min.css') ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta name="csrf-token" content="<?= csrf_hash() ?>">

    <script>
        // CI4-generated endpoints — site_url() picks the right URL form
        // per environment (index.php?/... locally, index.php/... on IIS).
        window.API_ENDPOINTS = window.API_ENDPOINTS || {};
        window.API_ENDPOINTS.searchUserNames = '<?= site_url('publications/search-user-names') ?>';
        window.API_ENDPOINTS.searchAuthorEmail = '<?= site_url('publications/search-author-email') ?>';
    </script>

    <!-- Shared Admin Styles -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-common.css') ?>">

    <!-- Publication Form Specific Styles -->
    <link rel="stylesheet" href="<?= base_url('assets/css/publication-form.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/author-search.css') ?>">

    <style>
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

        /* Prevent interaction when modal is shown */
        #aiWaitingModal:not(.hidden) {
            pointer-events: auto !important;
        }

        #aiWaitingModal:not(.hidden)~* {
            pointer-events: none !important;
        }

        /* Page-specific overrides */
        body div.name-search-container {
            position: relative !important;
            display: block !important;
            width: 100% !important;
        }

        body div.name-search-dropdown {
            position: absolute !important;
            top: 100% !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 999999 !important;
            margin: 0 !important;
            background: white !important;
            border: 1px solid #ccc !important;
            border-radius: 6px !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Header Section -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900">เพิ่มผลงานวิจัย</h1>
                <a href="<?= esc($cancel_url ?? site_url('publications/manage'), 'attr') ?>"
                    class="text-sm text-gray-600 hover:text-gray-900">
                    ← กลับ
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Flash Messages -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded flex items-center">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span><?= session()->getFlashdata('success') ?></span>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded flex items-center">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span><?= session()->getFlashdata('error') ?></span>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('warning')): ?>
            <div class="mb-6 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded flex items-center">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <span><?= session()->getFlashdata('warning') ?></span>
            </div>
        <?php endif; ?>

        <?php
        $p = is_array($publication ?? null) ? $publication : [];
        $isEdit = ! empty($is_edit) && (int) ($p['id'] ?? 0) > 0;
        $formAction = $form_action ?? site_url('publications/store');
        ?>

        <!-- Publication Form -->
        <div class="bg-white rounded-lg shadow-sm border">

            <!-- Form Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">รายละเอียดผลงานวิจัย</h2>
                <p class="text-sm text-gray-600 mt-1"><?= $isEdit ? 'แก้ไขข้อมูลผลงานวิจัย' : 'กรุณากรอกข้อมูลด้านล่างเพื่อเพิ่มผลงานวิจัยใหม่' ?> — หลังบันทึกจะกลับหน้าที่มา (ถ้ามี)</p>
            </div>

            <!-- Form Content -->
            <form id="publicationForm" action="<?= esc($formAction, 'attr') ?>" method="POST">
                <?= csrf_field() ?>

                <!-- Hidden field for file reference -->
                <input type="hidden" id="ref_url" name="ref_url" value="<?= esc((string) ($p['ref_url'] ?? ''), 'attr') ?>">

                <div class="p-6 space-y-8">

                    <!-- Smart Assistant Section - Simple Design -->
                    <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg border-2 border-blue-200 p-6">
                        <div class="flex items-start gap-4">
                            <!-- Icon -->
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>

                            <!-- Content -->
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
                                    <!-- File Upload -->
                                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            อัปโหลดไฟล์
                                        </label>
                                        <input type="file" id="fileInput" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="hidden">
                                        <button type="button" onclick="document.getElementById('fileInput').click()" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium">
                                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                            </svg>
                                            เลือกไฟล์
                                        </button>
                                        <p class="text-xs text-gray-500 mt-2">PDF, DOC, DOCX, JPG, PNG (สูงสุด 10MB)</p>

                                        <!-- Upload Status -->
                                        <div id="uploadProgress" class="hidden mt-3">
                                            <div class="flex items-center gap-2 text-sm text-blue-600">
                                                <div class="loading-spinner" style="width: 16px; height: 16px; border-width: 2px;"></div>
                                                <span>กำลังอัปโหลด...</span>
                                            </div>
                                        </div>

                                        <div id="uploadedFile" class="hidden mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-xs font-medium text-green-800">✓ อัปโหลดสำเร็จ</p>
                                                    <p id="fileName" class="text-sm text-gray-900 truncate"></p>
                                                </div>
                                                <button type="button" onclick="removeUploadedFile()" class="text-red-600 hover:text-red-800 flex-shrink-0">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- URL Input -->
                                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                                        <label for="ai-url-input" class="block text-sm font-medium text-gray-700 mb-2">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                            </svg>
                                            หรือใส่ URL
                                        </label>
                                        <input
                                            type="url"
                                            id="ai-url-input"
                                            placeholder="https://example.com/paper.pdf"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        <p class="text-xs text-gray-500 mt-2">ลิงก์ไปยัง PDF หรือเว็บไซต์เอกสาร</p>
                                    </div>
                                </div>

                                <!-- Process Button -->
                                <div class="mt-4 flex justify-end">
                                    <button type="button" id="ai-assist-btn" onclick="processWithAI()" class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all flex items-center gap-2">
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
                        <input type="hidden" id="publication_type" name="publication_type" required>
                    </div>

                    <!-- Basic Information -->
                    <div class="form-section">
                        <h3 class="section-title">ข้อมูลพื้นฐาน</h3>

                        <div class="space-y-6">
                            <!-- Title - Full Width -->
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                    ชื่อผลงาน *
                                </label>
                                <input type="text" id="title" name="title" required placeholder="กรุณากรอกชื่อผลงาน"
                                    class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            </div>

                            <!-- Source - Full Width -->
                            <div>
                                <label for="source" class="block text-sm font-medium text-gray-700 mb-2">
                                    แหล่งตีพิมพ์ *
                                </label>
                                <input type="text" id="source" name="source" placeholder="ชื่อวารสาร, การประชุม, สำนักพิมพ์ เป็นต้น" required
                                    class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            </div>

                            <!-- Year and Month - Side by Side -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="publication_year" class="block text-sm font-medium text-gray-700 mb-2">
                                        ปีที่ตีพิมพ์ (พ.ศ.) *
                                    </label>
                                    <input type="number" id="publication_year" name="publication_year" min="2443" max="2643" placeholder="เช่น 2567"
                                        class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                    <p class="mt-2 text-xs text-gray-500">กรุณากรอกปีพุทธศักราช เช่น 2567 สำหรับปี ค.ศ. 2024</p>
                                </div>

                                <div>
                                    <label for="publication_month" class="block text-sm font-medium text-gray-700 mb-2">
                                        เดือนที่ตีพิมพ์
                                    </label>
                                    <select id="publication_month" name="publication_month"
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

                    <!-- Publication Details -->
                    <div class="form-section">
                        <h3 class="section-title">รายละเอียดการตีพิมพ์</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Journal & Conference Fields -->
                            <div class="conditional-field field-transition" data-types="journal,proceedings">
                                <label for="volume" class="block text-sm font-medium text-gray-700">ปีที่ (Volume)</label>
                                <input type="text" id="volume" name="volume" placeholder="เช่น Vol. 12"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="conditional-field field-transition" data-types="journal,proceedings">
                                <label for="issue" class="block text-sm font-medium text-gray-700">ฉบับที่ (Issue)</label>
                                <input type="text" id="issue" name="issue" placeholder="เช่น No. 3"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="conditional-field field-transition" data-types="journal,proceedings,book">
                                <label for="pages" class="block text-sm font-medium text-gray-700">หน้า</label>
                                <input type="text" id="pages" name="pages" placeholder="เช่น 123-145"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="conditional-field field-transition" data-types="journal,proceedings">
                                <label for="doi" class="block text-sm font-medium text-gray-700">DOI</label>
                                <input type="text" id="doi" name="doi" placeholder="10.1000/xyz123"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Book Fields -->
                            <div class="conditional-field field-transition" data-types="book">
                                <label for="isbn" class="block text-sm font-medium text-gray-700">ISBN</label>
                                <input type="text" id="isbn" name="isbn" placeholder="978-3-16-148410-0"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="conditional-field field-transition" data-types="book">
                                <label for="publisher" class="block text-sm font-medium text-gray-700">สำนักพิมพ์</label>
                                <input type="text" id="publisher" name="publisher" placeholder="ชื่อสำนักพิมพ์"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="conditional-field field-transition" data-types="book">
                                <label for="book_title" class="block text-sm font-medium text-gray-700">ชื่อหนังสือ</label>
                                <input type="text" id="book_title" name="book_title" placeholder="สำหรับบทในหนังสือ"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="conditional-field field-transition" data-types="book">
                                <label for="chapter" class="block text-sm font-medium text-gray-700">บทที่</label>
                                <input type="text" id="chapter" name="chapter" placeholder="เช่น Chapter 5"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="conditional-field field-transition" data-types="book">
                                <label for="editor" class="block text-sm font-medium text-gray-700">บรรณาธิการ</label>
                                <input type="text" id="editor" name="editor" placeholder="ชื่อบรรณาธิการ"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Conference/Proceedings Fields -->
                            <div class="conditional-field field-transition" data-types="proceedings">
                                <label for="conference_name" class="block text-sm font-medium text-gray-700">ชื่อการประชุม</label>
                                <input type="text" id="conference_name" name="conference_name" placeholder="ชื่อการประชุมวิชาการ"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="conditional-field field-transition" data-types="proceedings">
                                <label for="conference_location" class="block text-sm font-medium text-gray-700">สถานที่จัดการประชุม</label>
                                <input type="text" id="conference_location" name="conference_location" placeholder="เช่น กรุงเทพฯ, ประเทศไทย"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="conditional-field field-transition" data-types="proceedings">
                                <label for="conference_date" class="block text-sm font-medium text-gray-700">วันที่จัดการประชุม</label>
                                <input type="date" id="conference_date" name="conference_date"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- URL Field (for all types) -->
                            <div class="field-transition">
                                <label for="url" class="block text-sm font-medium text-gray-700">URL</label>
                                <input type="url" id="url" name="url" placeholder="https://example.com/publication"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Authors Section -->
                    <div class="authors-section">
                        <div class="flex items-center justify-between mb-4">
                            <label class="block text-sm font-medium text-gray-700">ผู้แต่ง/ผู้วิจัย *</label>
                            <div class="flex items-center gap-3">
                                <span id="authorStatus" class="text-sm text-gray-500">เพิ่มผู้แต่งแล้ว 1 คน</span>
                                <button type="button" onclick="addAuthor()"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    เพิ่มผู้แต่ง
                                </button>
                            </div>
                        </div>

                        <!-- Authors Container -->
                        <div id="authorsContainer" class="authors-container">
                            <!-- First author row -->
                            <div class="author-row">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                                    <div class="input-group">
                                        <input type="text"
                                            name="authors[0][name]"
                                            placeholder="ชื่อผู้แต่ง"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div class="input-group">
                                        <input type="email"
                                            name="authors[0][email]"
                                            placeholder="อีเมล (ไม่บังคับ)"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div class="input-group flex">
                                        <input type="text"
                                            name="authors[0][affiliation]"
                                            placeholder="หน่วยงาน (ไม่บังคับ)"
                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                        <button type="button"
                                            onclick="removeAuthor(this)"
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
                                        name="authors[0][corresponding]"
                                        value="1"
                                        class="corresponding-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                                        onchange="handleCorrespondingChange(this)">
                                    <label class="ml-2 text-sm font-medium text-gray-700">
                                        ผู้แต่งที่ติดต่อได้ (Corresponding Author)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Authors Help Text -->
                        <p class="mt-2 text-sm text-gray-500">
                            💡 <strong>เคล็ดลับ:</strong> พิมพ์อีเมลเพื่อกรอกข้อมูลผู้แต่งอัตโนมัติหากมีข้อมูลในระบบ
                        </p>
                    </div>

                    <!-- Additional Information -->
                    <div class="form-section">
                        <h3 class="section-title">ข้อมูลเพิ่มเติม</h3>

                        <div class="space-y-6">
                            <div>
                                <label for="abstract" class="block text-sm font-medium text-gray-700">บทคัดย่อ</label>
                                <textarea id="abstract" name="abstract" rows="4" placeholder="กรุณากรอกบทคัดย่อของผลงาน"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></textarea>
                            </div>

                            <div>
                                <label for="keywords" class="block text-sm font-medium text-gray-700">คำสำคัญ</label>
                                <input type="text" id="keywords" name="keywords" placeholder="แยกคำสำคัญด้วยเครื่องหมายจุลภาค (,)"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700">หมายเหตุ</label>
                                <textarea id="notes" name="notes" rows="3" placeholder="หมายเหตุเพิ่มเติม (ถ้ามี)"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></textarea>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Form Actions -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <a href="<?= esc($cancel_url ?? site_url('publications/manage'), 'attr') ?>"
                        class="mr-3 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        ยกเลิก
                    </a>
                    <button type="button" onclick="submitForm(); return false;"
                        class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span id="submitText">บันทึกผลงาน</span>
                        <span id="submitLoading" class="hidden">
                            <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            กำลังบันทึก...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- JavaScript -->
    <script>
        // Configuration
        const BASE_URL = '<?= rtrim(base_url(), '/') ?>';
        let authorCount = 1;
    </script>
    <script src="<?= base_url('assets/js/publication-form.js') ?>"></script>
    <?php if ($isEdit): ?>
    <script>
    $(function () {
        var pub = <?= json_encode($p, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        var scalarFields = ['title', 'source', 'volume', 'issue', 'pages', 'doi', 'isbn', 'publisher', 'book_title', 'chapter', 'editor', 'conference_name', 'conference_location', 'conference_date', 'url', 'abstract', 'keywords', 'notes'];
        scalarFields.forEach(function (key) {
            if (pub[key]) {
                $('#' + key).val(pub[key]);
            }
        });
        if (pub.publication_type) {
            $('#publication_type').val(pub.publication_type);
            $('.publication-type-card[data-type="' + pub.publication_type + '"]').trigger('click');
        }
        if (pub.publication_year) {
            var y = parseInt(pub.publication_year, 10);
            if (y > 0 && y < 2400) {
                y += 543;
            }
            $('#publication_year').val(y);
        }
        if (pub.publication_month) {
            var m = String(pub.publication_month);
            if (m.length === 1) {
                m = '0' + m;
            }
            $('#publication_month').val(m);
        }
        var authors = Array.isArray(pub.authors) ? pub.authors : [];
        if (authors.length) {
            $('#authorsContainer').empty();
            authorCount = 0;
            authors.forEach(function (a) {
                addAuthor();
                var $row = $('.author-row').last();
                $row.find('[name*="[name]"]').val(a.author_name || a.name || '');
                $row.find('[name*="[email]"]').val(a.user_email || a.email || '');
                $row.find('[name*="[affiliation]"]').val(a.affiliation || '');
                if (a.corresponding_author || a.corresponding) {
                    $row.find('[name*="[corresponding]"]').prop('checked', true);
                }
            });
            updateAuthorStatus();
        }
    });
    </script>
    <?php endif; ?>
    <script src="<?= base_url('assets/js/email-autocomplete.js') ?>"></script>
    <script src="<?= base_url('assets/js/author-search.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // EMERGENCY JS - Add after your existing JS
        function fixDropdownPositioning() {
            $('.name-search-dropdown').each(function() {
                this.style.position = 'absolute';
                this.style.top = '100%';
                this.style.zIndex = '999999';
                this.style.left = '0';
                this.style.right = '0';
            });
        }

        $(document).ready(fixDropdownPositioning);
        setInterval(fixDropdownPositioning, 1000);
        window.fixDropdownPositioning = fixDropdownPositioning;

        // Thai Buddhist Year Validation
        document.getElementById('publication_year')?.addEventListener('blur', function() {
            const year = parseInt(this.value);
            const currentThaiYear = new Date().getFullYear() + 543;

            if (year && year < 2443) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ปีไม่ถูกต้อง',
                    text: 'กรุณากรอกปีพุทธศักราช เช่น 2567 แทนที่จะเป็น 2024',
                    confirmButtonText: 'ตกลง'
                });
                this.value = '';
                this.focus();
            } else if (year && year > 2643) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ปีไม่ถูกต้อง',
                    text: 'ปีที่กรอกอยู่ในอนาคตไกลเกินไป กรุณาตรวจสอบอีกครั้ง',
                    confirmButtonText: 'ตกลง'
                });
                this.value = '';
                this.focus();
            } else if (year && (year >= 1900 && year <= 2100)) {
                // User likely entered Christian Era year, auto-convert
                const thaiYear = year + 543;
                Swal.fire({
                    icon: 'info',
                    title: 'แปลงเป็นปีพุทธศักราช',
                    text: `คุณหมายถึง ${thaiYear} (พ.ศ.) ใช่หรือไม่? ระบบได้แปลง ${year} (ค.ศ.) เป็น ${thaiYear} (พ.ศ.) แล้ว`,
                    confirmButtonText: 'ตกลง'
                });
                this.value = thaiYear;
            }
        });
    </script>

    <!-- AI Assistant Modal -->
    <!-- AI Waiting Overlay - BLOCKING -->
    <div id="aiWaitingModal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900 bg-opacity-75 backdrop-blur-sm" style="pointer-events: auto;" data-no-outside-click="true" data-no-esc-close="true">
        <div class="bg-white rounded-xl shadow-2xl px-10 py-8 text-center space-y-5 max-w-md mx-4 animate-pulse-slow">
            <div class="loading-spinner mx-auto" style="width: 56px; height: 56px; border-width: 5px;"></div>
            <div>
                <p class="text-xl font-bold text-gray-800 mb-2">กำลังให้ AI ประมวลผล...</p>
                <p class="text-base text-blue-600 font-medium mb-2">⏱️ กรุณารอสักครู่ ประมาณ 1-2 นาที</p>
                <p id="aiWaitingMessage" class="text-sm text-gray-600 leading-relaxed">AI กำลังอ่านและวิเคราะห์เอกสารของคุณ<br>แล้วกรอกข้อมูลลงในฟอร์มให้อัตโนมัติ</p>
            </div>
            <div class="pt-2">
                <div class="inline-flex items-center gap-2 text-xs text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <span>กรุณาอย่าปิดหน้าต่างนี้ ห้ามคลิกที่อื่น</span>
                </div>
            </div>
        </div>
    </div>

    <div id="aiModal" class="modal hidden" data-no-outside-click="true">
        <div class="modal-content bg-white rounded-xl shadow-2xl max-w-2xl mx-auto mt-20 overflow-hidden">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4 flex justify-between items-center">
                <div class="flex items-center">
                    <svg class="w-6 h-6 mr-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <h3 class="text-xl font-bold text-white">AI ช่วยกรอกข้อมูล</h3>
                </div>
                <button onclick="closeAIModal()" class="text-white hover:text-gray-200 text-2xl font-bold">&times;</button>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
                <div id="aiStatus" class="space-y-4">
                    <!-- AI Processing Status -->
                    <div id="aiProcessing" class="hidden text-center py-8">
                        <div class="loading-spinner mx-auto mb-4" style="width: 40px; height: 40px; border-width: 4px;"></div>
                        <p class="text-gray-700 font-medium text-lg">กำลังประมวลผลด้วย AI...</p>
                        <p class="text-gray-500 text-sm mt-2">กรุณารอสักครู่ ประมาณ 1-2 นาที</p>
                        <p class="text-gray-400 text-xs mt-1">AI กำลังอ่านและวิเคราะห์เอกสารของคุณ</p>
                    </div>

                    <!-- AI Success -->
                    <div id="aiSuccess" class="hidden">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <p class="text-green-800 font-medium">ประมวลผลสำเร็จ!</p>
                                    <p class="text-green-600 text-sm">AI ได้สกัดข้อมูลจากไฟล์เรียบร้อยแล้ว</p>
                                </div>
                            </div>
                        </div>
                        <div id="aiPreview" class="bg-gray-50 rounded-lg p-4 max-h-96 overflow-y-auto">
                            <!-- AI extracted data will be shown here -->
                        </div>
                        <p id="aiAutoFillMessage" class="hidden mt-4 text-sm text-green-700 bg-green-100 border border-green-200 rounded-lg px-3 py-2">
                            AI ได้กรอกข้อมูลลงในแบบฟอร์มให้เรียบร้อยแล้ว
                        </p>
                        <div id="aiRawJsonContainer" class="hidden mt-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">AI JSON Response</h4>
                            <pre id="aiRawJson" class="bg-white border border-gray-200 rounded-lg p-3 text-xs text-gray-800 overflow-x-auto whitespace-pre-wrap"></pre>
                        </div>
                    </div>

                    <!-- AI Error -->
                    <div id="aiError" class="hidden">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <p class="text-red-800 font-medium">เกิดข้อผิดพลาด</p>
                                    <p id="aiErrorMessage" class="text-red-600 text-sm"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Initial State -->
                    <div id="aiInitial">
                        <div class="text-center py-8">
                            <svg class="w-20 h-20 mx-auto text-blue-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                            <h4 class="text-lg font-semibold text-gray-900 mb-2">ใช้ AI ช่วยกรอกข้อมูล</h4>
                            <p class="text-gray-600 mb-4">อัปโหลดไฟล์ PDF หรือเอกสารของคุณ หรือใส่ URL แล้ว AI จะช่วยสกัดข้อมูลให้อัตโนมัติ</p>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-left mb-6">
                                <p class="text-sm font-medium text-blue-900 mb-2">💡 เคล็ดลับ:</p>
                                <ul class="text-sm text-blue-700 space-y-1">
                                    <li>• ไฟล์ PDF คุณภาพดีจะได้ผลลัพธ์ที่แม่นยำกว่า</li>
                                    <li>• ระบบจะสกัดข้อมูลชื่อ, ผู้แต่ง, ปี, วารสาร และข้อมูลอื่นๆ</li>
                                    <li>• คุณสามารถแก้ไขข้อมูลได้หลังจากที่ AI กรอกให้</li>
                                    <li>• รองรับทั้งภาษาไทยและภาษาอังกฤษ</li>
                                </ul>
                            </div>
                            <button type="button" onclick="triggerAIExtraction()" class="px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-medium transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    เริ่มประมวลผลด้วย AI
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-between">
                <button type="button" onclick="closeAIModal()" class="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg font-medium transition-colors">
                    ยกเลิก
                </button>
                <button type="button" id="applyAIData" class="hidden px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                    นำข้อมูลไปใช้
                </button>
            </div>
        </div>
    </div>

    <!-- File Upload & AI Assistant Scripts -->
    <script src="<?= base_url('assets/js/publication-upload.js?v=' . time()) ?>"></script>
    <script>window.N8N_EXTRACT_ARTICLE_URL = <?= json_encode(config(\Config\N8n::class)->extractArticleUrl(), JSON_UNESCAPED_SLASHES) ?>;</script>
    <script src="<?= base_url('assets/js/publication-ai.js?v=' . time()) ?>"></script>
</body>

</html>