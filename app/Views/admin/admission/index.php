<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แบบฟอร์มขอเปิดรับนักศึกษาใหม่</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.min.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/sarabun.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-common.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery-datetimepicker@2.5.21/jquery.datetimepicker.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-datetimepicker@2.5.21/build/jquery.datetimepicker.full.min.js"></script>
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
        }

        .status-draft {
            background: #fef3c7;
            color: #92400e;
        }

        .status-submitted {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 50;
            overflow-y: auto;
        }

        .modal-overlay.active {
            display: flex;
            justify-content: center;
            padding: 2rem;
        }

        .modal-content {
            background: white;
            border-radius: 0.5rem;
            width: 100%;
            max-width: 1200px;
            max-height: 90vh;
            overflow-y: auto;
        }

        /* Table layout improvements */
        #forms-table-body tr td {
            vertical-align: middle;
        }

        #forms-table-body tr td:nth-child(2) {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #forms-table-body tr td:nth-child(6) {
            white-space: nowrap;
        }
    </style>
</head>

<body class="min-h-full">
    <div class="min-h-full bg-gray-50">
        <?php
        $pageTitle = 'แบบฟอร์มขอเปิดรับนักศึกษาใหม่';
        $pageSubtitle = 'Student Admission Form Management';
        ?>
        <?= view('admin/partials/header', ['pageTitle' => $pageTitle, 'pageSubtitle' => $pageSubtitle]) ?>

        <div class="flex">
            <?= view('admin/partials/navigation') ?>

            <main class="flex-1 p-4 lg:p-6 bg-gradient-to-br from-slate-50 via-blue-50/30 to-purple-50/20 min-h-screen">
                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center">📋</div>
                            <div>
                                <p class="text-xs font-medium text-slate-500">ทั้งหมด</p>
                                <p id="total-count" class="text-2xl font-bold text-slate-700"><?= $stats['total'] ?? 0 ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">✏️</div>
                            <div>
                                <p class="text-xs font-medium text-yellow-600">ฉบับร่าง</p>
                                <p class="text-2xl font-bold text-yellow-700"><?= $stats['draft_count'] ?? 0 ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">📤</div>
                            <div>
                                <p class="text-xs font-medium text-blue-600">ส่งแล้ว</p>
                                <p class="text-2xl font-bold text-blue-700"><?= $stats['submitted_count'] ?? 0 ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">✅</div>
                            <div>
                                <p class="text-xs font-medium text-green-600">อนุมัติแล้ว</p>
                                <p class="text-2xl font-bold text-green-700"><?= $stats['approved_count'] ?? 0 ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions & Filter -->
                <div class="flex flex-wrap gap-3 mb-4 items-center justify-between">
                    <div class="flex gap-3">
                        <button onclick="generateNewYear()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors inline-flex items-center gap-2 shadow-md">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            สร้างแบบฟอร์มปีใหม่
                        </button>
                    </div>
                    <div class="flex gap-4 items-center flex-wrap">
                        <div class="flex gap-2 items-center">
                            <label class="text-sm font-medium text-gray-700">คณะ:</label>
                            <select id="faculty-filter" onchange="applyFilters()" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 min-w-[200px]">
                                <option value="">-- ทุกคณะ --</option>
                                <?php foreach ($faculties as $f): ?>
                                    <option value="<?= $f['id'] ?>" <?= $f['id'] == $selectedFaculty ? 'selected' : '' ?>><?= esc($f['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex gap-2 items-center">
                            <label class="text-sm font-medium text-gray-700">ปีการศึกษา:</label>
                            <select id="year-filter" onchange="applyFilters()" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <?php
                                // Group years by decade for better organization
                                $groupedYears = [];
                                foreach ($years as $y) {
                                    $decade = floor($y / 10) * 10;
                                    if (!isset($groupedYears[$decade])) {
                                        $groupedYears[$decade] = [];
                                    }
                                    $groupedYears[$decade][] = $y;
                                }
                                krsort($groupedYears); // Sort decades descending

                                foreach ($groupedYears as $decade => $yearList):
                                    // Show optgroup if there are multiple decades
                                    if (count($groupedYears) > 1):
                                ?>
                                        <optgroup label="ปี <?= $decade ?> - <?= $decade + 9 ?>">
                                        <?php endif; ?>
                                        <?php foreach ($yearList as $y): ?>
                                            <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                                        <?php endforeach; ?>
                                        <?php if (count($groupedYears) > 1): ?>
                                        </optgroup>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200" style="min-width: 1000px;">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase" style="width: 60px;">ลำดับ</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase" style="width: 200px; min-width: 200px;">คณะ</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase" style="min-width: 250px;">หลักสูตร</th>
                                    <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase" style="width: 100px;">ปีการศึกษา</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase" style="width: 140px;">ความครบถ้วน</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase" style="width: 120px;">สถานะ</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase" style="width: 150px; min-width: 150px;">อัปเดตล่าสุด</th>
                                    <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase" style="width: 100px;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="forms-table-body" class="bg-white divide-y divide-gray-200">
                                <?php if (empty($forms)): ?>
                                    <tr id="empty-row">
                                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                            <div class="text-4xl mb-2">📋</div>
                                            <p>ไม่พบข้อมูลแบบฟอร์มสำหรับปีการศึกษานี้</p>
                                            <button onclick="generateNewYear()" class="mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg">สร้างแบบฟอร์มใหม่</button>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($forms as $index => $form): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-3 py-3 text-sm text-gray-900 text-center"><?= $index + 1 ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap" title="<?= esc($form['faculty_name'] ?? '-') ?>"><?= esc($form['faculty_name'] ?? '-') ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-900 truncate" title="<?= esc($form['curriculum_name_display'] ?? '-') ?>"><?= esc($form['curriculum_name_display'] ?? '-') ?></td>
                                            <td class="px-3 py-3 text-sm text-gray-900 text-center"><?= $form['academic_year'] ?></td>
                                            <td class="px-4 py-3">
                                                <?php
                                                // Calculate completion percentage
                                                $fields = [
                                                    !empty($form['curriculum_name']),
                                                    !empty($form['ministry_approval_date']),
                                                    !empty($form['quality_assessment_result1']) || !empty($form['quality_assessment_result2']),
                                                    !empty($form['target_highschool']) || !empty($form['target_diploma']),
                                                    !empty($form['qualification_highschool']) || !empty($form['qualification_diploma']),
                                                    !empty($form['curriculum_head_name']),
                                                    !empty($form['dean_name'])
                                                ];
                                                $completed = count(array_filter($fields));
                                                $total = count($fields);
                                                $percent = round(($completed / $total) * 100);

                                                // Color based on percentage
                                                if ($percent >= 80) {
                                                    $barColor = 'bg-green-500';
                                                    $bgColor = 'bg-green-100';
                                                } elseif ($percent >= 50) {
                                                    $barColor = 'bg-yellow-500';
                                                    $bgColor = 'bg-yellow-100';
                                                } else {
                                                    $barColor = 'bg-red-500';
                                                    $bgColor = 'bg-red-100';
                                                }
                                                ?>
                                                <div class="flex items-center gap-2">
                                                    <div class="flex-1 h-2 <?= $bgColor ?> rounded-full overflow-hidden">
                                                        <div class="h-full <?= $barColor ?> rounded-full transition-all" style="width: <?= $percent ?>%"></div>
                                                    </div>
                                                    <span class="text-xs font-medium text-gray-600 whitespace-nowrap"><?= $percent ?>%</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                                <?php
                                                $status = $form['status'] ?? 'draft';
                                                switch ($status) {
                                                    case 'submitted':
                                                        echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium status-submitted">ส่งแล้ว</span>';
                                                        break;
                                                    case 'approved':
                                                        echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium status-approved">อนุมัติแล้ว</span>';
                                                        break;
                                                    case 'rejected':
                                                        echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium status-rejected">ส่งกลับเพื่อแก้ไข</span>';
                                                        break;
                                                    case 'draft':
                                                    default:
                                                        echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium status-draft">ร่าง</span>';
                                                        break;
                                                }
                                                ?>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500 text-center whitespace-nowrap"><?= date('d/m/Y H:i', strtotime($form['updated_at'])) ?></td>
                                            <td class="px-3 py-3 text-center">
                                                <div class="flex justify-center gap-2">
                                                    <button onclick="openEditModal(<?= $form['id'] ?>)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="แก้ไข">✏️</button>
                                                    <button onclick="openViewModal(<?= $form['id'] ?>)" class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="ดูข้อมูล">👁️</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <div class="p-4 border-b flex justify-between items-center bg-blue-600 text-white rounded-t-lg">
                <h2 class="text-lg font-semibold" id="modalTitle">แก้ไขแบบฟอร์มขอเปิดรับนักศึกษาใหม่</h2>
                <button onclick="closeModal()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
            </div>
            <div id="modalBody" class="p-6">
                <div class="flex justify-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                </div>
            </div>
            <div class="p-4 border-t bg-gray-50 flex justify-end gap-2 rounded-b-lg" id="modalFooterActions">
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg">ยกเลิก</button>
                <button onclick="saveForm()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">💾 บันทึก</button>
            </div>
        </div>
    </div>

    <!-- View Modal (Read-only) -->
    <div id="viewModal" class="modal-overlay">
        <div class="modal-content">
            <div class="p-4 border-b flex justify-between items-center bg-green-600 text-white rounded-t-lg">
                <h2 class="text-lg font-semibold" id="viewModalTitle">ดูแบบฟอร์มขอเปิดรับนักศึกษาใหม่</h2>
                <button onclick="closeViewModal()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
            </div>
            <div id="viewModalBody" class="p-6">
                <div class="flex justify-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
                </div>
            </div>
            <div class="p-4 border-t bg-gray-50 flex justify-end gap-2 rounded-b-lg">
                <button onclick="closeViewModal()" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg">ปิด</button>
                <button onclick="generatePDFForCurrentForm()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">📄 ดาวน์โหลด PDF</button>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?= rtrim(base_url(), '/') ?>';
    </script>
    <script src="<?= base_url('assets/js/app-routes.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/app-routes.js') ?: time() ?>"></script>
    <script>
        const userRole = '<?= $userRole ?? "teacher" ?>';
        let currentFormId = null;

        const statusMap = {
            'draft': ['ฉบับร่าง', 'status-draft'],
            'submitted': ['ส่งแล้ว', 'status-submitted'],
            'approved': ['อนุมัติแล้ว', 'status-approved'],
            'rejected': ['ส่งกลับเพื่อแก้ไข', 'status-rejected']
        };

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Format date CE (YYYY-MM-DD) to BE (DD/MM/YYYY+543)
        // ถ้าไม่มีข้อมูลหรือเป็น 0000-00-00 ให้แสดงวันที่ปัจจุบันเป็น พ.ศ.
        function formatDateToBE(ceDate) {
            // ตรวจสอบว่าเป็นค่าว่างหรือวันที่ไม่ถูกต้อง
            if (!ceDate || ceDate === '0000-00-00' || ceDate === 'null' || ceDate === 'undefined') {
                return '';
            }
            const parts = ceDate.split('-');
            if (parts.length !== 3) return '';
            const year = parseInt(parts[0]);
            const month = parts[1];
            const day = parts[2];
            // ตรวจสอบว่าปีไม่ใช่ 0 หรือค่าที่ไม่ถูกต้อง
            if (year <= 0 || isNaN(year)) {
                return '';
            }
            const beYear = year + 543;
            return `${day}/${month}/${beYear}`;
        }

        // Get current date in CE format (YYYY-MM-DD) for hidden input
        function getCurrentDateCE() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Simple date format helper (CE format - YYYY-MM-DD to DD/MM/YYYY with พ.ศ.)
        function formatDateForDisplay(ceDate) {
            if (!ceDate || ceDate === '0000-00-00' || ceDate === 'null' || ceDate === 'undefined') return '-';
            const parts = ceDate.split('-');
            if (parts.length !== 3) return '-';
            const year = parseInt(parts[0]);
            if (year <= 0 || isNaN(year)) return '-';
            const beYear = year + 543;
            return `${parts[2]}/${parts[1]}/${beYear}`;
        }

        function applyFilters() {
            const year = document.getElementById('year-filter').value;
            const faculty = document.getElementById('faculty-filter').value;

            // Show loading
            $('#forms-table-body').html('<tr><td colspan="8" class="px-6 py-8 text-center"><div class="animate-spin inline-block w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full"></div><p class="mt-2 text-gray-500">กำลังโหลด...</p></td></tr>');

            // Update URL without reload (year/faculty as path segments; IIS drops GET params)
            const newUrl = appRoute('admin/admission/' + year + (faculty ? '/' + faculty : ''));
            window.history.pushState({}, '', newUrl);

            $.ajax({
                url: appRoute('admin/admission/list'), // POST: IIS drops GET params
                type: 'POST',
                data: {
                    year: year,
                    faculty: faculty
                },
                success: function(result) {
                    if (result.success) {
                        renderTable(result.forms);
                        updateStats(result.stats);
                    } else {
                        $('#forms-table-body').html('<tr><td colspan="8" class="text-center text-red-500 py-8">เกิดข้อผิดพลาด</td></tr>');
                    }
                },
                error: function() {
                    $('#forms-table-body').html('<tr><td colspan="8" class="text-center text-red-500 py-8">ไม่สามารถโหลดข้อมูลได้</td></tr>');
                }
            });
        }

        function renderTable(forms) {
            if (!forms || forms.length === 0) {
                $('#forms-table-body').html(`
                    <tr id="empty-row">
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <div class="text-4xl mb-2">📋</div>
                            <p>ไม่พบข้อมูลแบบฟอร์มสำหรับปีการศึกษานี้</p>
                            <button onclick="generateNewYear()" class="mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg">สร้างแบบฟอร์มใหม่</button>
                        </td>
                    </tr>
                `);
                return;
            }

            let html = '';
            forms.forEach((form, index) => {
                const updatedAt = new Date(form.updated_at).toLocaleString('th-TH');

                // Calculate completion percentage
                const fields = [
                    !!form.curriculum_name,
                    !!form.ministry_approval_date,
                    !!form.quality_assessment_result1 || !!form.quality_assessment_result2,
                    !!form.target_highschool || !!form.target_diploma,
                    !!form.qualification_highschool || !!form.qualification_diploma,
                    !!form.curriculum_head_name,
                    !!form.dean_name
                ];
                const completed = fields.filter(Boolean).length;
                const total = fields.length;
                const percent = Math.round((completed / total) * 100);

                let barColor, bgColor;
                if (percent >= 80) {
                    barColor = 'bg-green-500';
                    bgColor = 'bg-green-100';
                } else if (percent >= 50) {
                    barColor = 'bg-yellow-500';
                    bgColor = 'bg-yellow-100';
                } else {
                    barColor = 'bg-red-500';
                    bgColor = 'bg-red-100';
                }

                const status = form.status || 'draft';
                const statusInfo = statusMap[status] || ['ฉบับร่าง', 'status-draft'];
                const statusBadge = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusInfo[1]}">${statusInfo[0]}</span>`;

                html += `
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-3 py-3 text-sm text-gray-900 text-center">${index + 1}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap" title="${form.faculty_name || '-'}">${form.faculty_name || '-'}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 truncate" title="${form.curriculum_name_display || '-'}">${form.curriculum_name_display || '-'}</td>
                        <td class="px-3 py-3 text-sm text-gray-900 text-center">${form.academic_year}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2 ${bgColor} rounded-full overflow-hidden">
                                    <div class="h-full ${barColor} rounded-full transition-all" style="width: ${percent}%"></div>
                                </div>
                                <span class="text-xs font-medium text-gray-600 whitespace-nowrap">${percent}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">${statusBadge}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 text-center whitespace-nowrap">${updatedAt}</td>
                        <td class="px-3 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                <button onclick="openEditModal(${form.id})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="แก้ไข">✏️</button>
                                <button onclick="openViewModal(${form.id})" class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="ดูข้อมูล">👁️</button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            $('#forms-table-body').html(html);
        }

        function updateStats(stats) {
            $('#total-count').text(stats.total || 0);
            $('.bg-yellow-50 .text-2xl').text(stats.draft_count || 0);
            $('.bg-blue-50 .text-2xl').text(stats.submitted_count || 0);
            $('.bg-green-50 .text-2xl').text(stats.approved_count || 0);
        }

        function filterByYear(year) {
            document.getElementById('year-filter').value = year;
            applyFilters();
        }

        async function generateNewYear() {
            const currentYear = new Date().getFullYear() + 543;
            const {
                value: year
            } = await Swal.fire({
                title: 'สร้างแบบฟอร์มปีใหม่',
                input: 'number',
                inputLabel: 'ปีการศึกษา (พ.ศ.)',
                inputValue: currentYear + 1,
                showCancelButton: true,
                confirmButtonText: 'สร้าง',
                cancelButtonText: 'ยกเลิก',
                inputValidator: (value) => {
                    if (!value || value < 2500 || value > 2600) {
                        return 'กรุณากรอกปีการศึกษาที่ถูกต้อง';
                    }
                }
            });

            if (year) {
                $.ajax({
                    url: appRoute('admin/admission/generate'),
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        year: year
                    }),
                    success: function(result) {
                        if (result.success) {
                            Swal.fire('สำเร็จ!', `สร้างแบบฟอร์ม ${result.count} รายการ`, 'success').then(() => {
                                window.location.href = appRoute('admin/admission/' + year);
                            });
                        } else {
                            Swal.fire('ผิดพลาด', result.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('ผิดพลาด', 'ไม่สามารถสร้างแบบฟอร์มได้', 'error');
                    }
                });
            }
        }

        function openEditModal(id) {
            currentFormId = id;
            $('#editModal').addClass('active');
            $('#modalBody').html('<div class="flex justify-center py-8"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div></div>');

            $.ajax({
                url: appRoute('admin/admission/get/' + id),
                type: 'GET',
                success: function(result) {
                    if (result.success) {
                        renderEditForm(result.data);
                    } else {
                        $('#modalBody').html('<div class="text-center py-8 text-red-500">ไม่สามารถโหลดข้อมูลได้</div>');
                    }
                },
                error: function() {
                    $('#modalBody').html('<div class="text-center py-8 text-red-500">เกิดข้อผิดพลาด</div>');
                }
            });
        }

        function renderEditForm(form) {
            // Store form data globally for fillFromSystem function
            window.currentFormData = form;

            $('#modalTitle').text('แก้ไขแบบฟอร์ม - ' + (form.curriculum_name_display || form.curriculum_name || ''));

            // Parse qualifications from JSON or text
            let qualHighschool = [];
            let qualDiploma = [];
            try {
                qualHighschool = form.qualification_highschool ? JSON.parse(form.qualification_highschool) : [];
            } catch (e) {
                qualHighschool = form.qualification_highschool ? form.qualification_highschool.split('\n').filter(q => q.trim()) : [];
            }
            try {
                qualDiploma = form.qualification_diploma ? JSON.parse(form.qualification_diploma) : [];
            } catch (e) {
                qualDiploma = form.qualification_diploma ? form.qualification_diploma.split('\n').filter(q => q.trim()) : [];
            }

            const positionOptions = ['อาจารย์', 'ดร.', 'ผศ.', 'ผศ.ดร.', 'รศ.', 'รศ.ดร.', 'ศ.', 'ศ.ดร.'];

            let html = `
                <form id="admissionForm">
                    <input type="hidden" name="status" id="admission_form_status" value="${form.status || 'draft'}">
                    <!-- Section 1: Curriculum Info -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๑. ข้อมูลหลักสูตร</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อหลักสูตรสาขาวิชา</label>
                                <input type="text" name="curriculum_name" value="${form.curriculum_name || form.curriculum_name_display || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ฉบับปี พ.ศ.</label>
                                <select name="curriculum_version_year" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white">
                                    <option value="">-- เลือกปี --</option>
                                    ${(() => {
                                        const currentBE = new Date().getFullYear() + 543 + 1;
                                        let opts = '';
                                        for (let y = currentBE; y >= 2564; y--) {
                                            opts += `<option value="${y}" ${parseInt(form.curriculum_version_year) === y ? 'selected' : ''}>${y}</option>`;
                                        }
                                        return opts;
                                    })()}
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Ministry Approval -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๒. การได้รับการพิจารณาความสอดคล้องจาก สป.อว.</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">วันที่ได้รับการพิจารณา</label>
                                <input type="text" class="datetimepicker-be w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 cursor-pointer" 
                                       id="ministry_approval_date_picker"
                                       data-target="ministry_approval_date"
                                       value="${formatDateToBE(form.ministry_approval_date)}" 
                                       readonly>
                                <input type="hidden" name="ministry_approval_date" id="ministry_approval_date" value="${form.ministry_approval_date || ''}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">สภามหาวิทยาลัยเห็นชอบ</label>
                                <input type="text" class="datetimepicker-be w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 cursor-pointer" 
                                       id="university_approval_date_picker"
                                       data-target="university_approval_date"
                                       value="${formatDateToBE(form.university_approval_date)}" 
                                       readonly>
                                <input type="hidden" name="university_approval_date" id="university_approval_date" value="${form.university_approval_date || ''}">
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Quality Assessment -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๓. ผลการประเมินคุณภาพ ๒ ปีย้อนหลัง</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <label class="block text-sm font-medium text-gray-600 mb-2">ปี พ.ศ. ${(form.academic_year || 2568) - 1}</label>
                                <input type="hidden" name="quality_assessment_year1" value="${(form.academic_year || 2568) - 1}">
                                <input type="text" name="quality_assessment_result1" value="${form.quality_assessment_result1 || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="ผลประเมิน เช่น 3.50">
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <label class="block text-sm font-medium text-gray-600 mb-2">ปี พ.ศ. ${(form.academic_year || 2568) - 2}</label>
                                <input type="hidden" name="quality_assessment_year2" value="${(form.academic_year || 2568) - 2}">
                                <input type="text" name="quality_assessment_result2" value="${form.quality_assessment_result2 || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="ผลประเมิน เช่น 3.25">
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Teachers with Position Dropdown -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๔. อาจารย์ผู้รับผิดชอบหลักสูตร</h3>
                        <p class="text-sm text-gray-500 mb-2">* ข้อมูลดึงจากระบบ teacher_curriculum</p>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 border w-12">ที่</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 border w-32">ตำแหน่งวิชาการ</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 border">ชื่อ-นามสกุล</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${(form.teachers || []).map((t, i) => {
                                        const currentPos = (t.titleThai || t.position || '').trim();
                                        return `
                                        <tr>
                                            <td class="px-3 py-2 text-center border">${i + 1}</td>
                                            <td class="px-2 py-1 border">
                                                <select class="w-full px-2 py-1 border rounded text-sm bg-white" 
                                                        onchange="updateTeacherPosition('${t.user_id}', this.value)">
                                                    ${positionOptions.map(opt => `<option value="${opt}" ${currentPos === opt ? 'selected' : ''}>${opt}</option>`).join('')}
                                                </select>
                                            </td>
                                            <td class="px-3 py-2 border">${t.thai_name || ''} ${t.thai_lastname || ''}</td>
                                        </tr>
                                    `}).join('') || '<tr><td colspan="3" class="px-3 py-2 text-center text-gray-500">ไม่มีข้อมูลอาจารย์</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Section 4.1: Teachers Status -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๔.๑) การคงอยู่ของอาจารย์ในหลักสูตร</h3>
                        <div class="flex flex-wrap gap-6 items-center mb-3">
                            <label class="flex items-center gap-2">
                                <input type="radio" name="teachers_status" value="complete" ${(form.teachers_status || '') === 'complete' ? 'checked' : ''} onchange="toggleIncompleteFields()">
                                <span>ครบ</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="teachers_status" value="incomplete" ${(form.teachers_status || '') === 'incomplete' ? 'checked' : ''} onchange="toggleIncompleteFields()">
                                <span>ไม่ครบ</span>
                            </label>
                        </div>
                        <div id="incomplete-fields" style="display: ${(form.teachers_status || '') === 'incomplete' ? 'block' : 'none'};">
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">เลือกอาจารย์ที่ไม่ครบ (สามารถเลือกได้หลายคน)</label>
                                <div class="border rounded-lg p-3 max-h-48 overflow-y-auto bg-white">
                                    ${(() => {
                                        // Parse incomplete teachers from JSON or old field
                                        let incompleteTeachers = [];
                                        if (form.teachers_incomplete_teachers) {
                                            try {
                                                incompleteTeachers = JSON.parse(form.teachers_incomplete_teachers);
                                            } catch (e) {
                                                incompleteTeachers = [];
                                            }
                                        } else if (form.teachers_incomplete_order) {
                                            // Fallback to old field - convert to new format
                                            const teacher = (form.teachers || []).find(t => t.order_num == form.teachers_incomplete_order);
                                            if (teacher) {
                                                incompleteTeachers = [{
                                                    user_id: teacher.user_id,
                                                    name: teacher.full_name || (teacher.thai_name + ' ' + teacher.thai_lastname)
                                                }];
                                            }
                                        }
                                        const selectedIds = incompleteTeachers.map(t => t.user_id || t.id);
                                        return (form.teachers || []).map(function(teacher) {
                                            const teacherId = teacher.user_id || teacher.id;
                                            const isSelected = selectedIds.includes(teacherId);
                                            // full_name already includes titleThai, so use it directly
                                            const teacherName = teacher.full_name || ((teacher.titleThai || '') + ' ' + (teacher.thai_name || '') + ' ' + (teacher.thai_lastname || '')).trim();
                                            return '<label class="flex items-center gap-2 py-1 hover:bg-gray-50 cursor-pointer">' +
                                                '<input type="checkbox" name="teachers_incomplete_teachers[]" value="' + teacherId + '" ' + (isSelected ? 'checked' : '') + ' class="w-4 h-4">' +
                                                '<span class="text-sm">' + teacherName + '</span>' +
                                                '</label>';
                                        }).join('');
                                    })()}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">เนื่องจาก</label>
                                <input type="text" name="teachers_incomplete_reason" value="${form.teachers_incomplete_reason || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="ระบุเหตุผล">
                            </div>
                        </div>
                    </div>

                    <!-- Section 4.2: Retiring Teachers -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2">๔.๒) อาจารย์ที่เกษียณอายุราชการ</h3>
                            <button type="button" onclick="addRetiringTeacherModal()" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">+ เพิ่มรายการ</button>
                        </div>
                        <div id="retiring-teachers-list-modal" class="space-y-3">
                            ${(() => {
                                // Parse retiring teachers from JSON or old fields
                                let retiringTeachers = [];
                                if (form.retiring_teachers) {
                                    try {
                                        retiringTeachers = JSON.parse(form.retiring_teachers);
                                    } catch (e) {
                                        retiringTeachers = [];
                                    }
                                } else {
                                    // Fallback to old fields
                                    for (let i = 1; i <= 3; i++) {
                                        const year = form['retiring_year' + i];
                                        const count = form['retiring_count' + i];
                                        if (year || count) {
                                            retiringTeachers.push({year: year, count: count});
                                        }
                                    }
                                }
                                
                                if (retiringTeachers.length === 0) {
                                    return '<p class="text-gray-500 py-2">ไม่มี</p>';
                                }
                                
                                return retiringTeachers.map((item, index) => {
                                    return '<div class="flex items-center gap-2 retiring-teacher-item-modal">' +
                                        '<span class="text-sm">ในปี พ.ศ.</span>' +
                                        '<input type="number" name="retiring_teachers[' + index + '][year]" value="' + (item.year || '') + '" class="w-24 px-2 py-1 border rounded" placeholder="เช่น 2565">' +
                                        '<span class="text-sm">จำนวน</span>' +
                                        '<input type="number" name="retiring_teachers[' + index + '][count]" value="' + (item.count || '') + '" class="w-16 px-2 py-1 border rounded" min="0" placeholder="0">' +
                                        '<span class="text-sm">คน</span>' +
                                        '<button type="button" onclick="removeRetiringTeacherModal(this)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">🗑️</button>' +
                                        '</div>';
                                }).join('');
                            })()}
                        </div>
                    </div>

                    <!-- Section 4.3: Teachers Pursuing Further Studies -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2">๔.๓) อาจารย์ศึกษาต่อ</h3>
                            <button type="button" onclick="addStudyingTeacherModal()" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">+ เพิ่มรายการ</button>
                        </div>
                        <div id="studying-teachers-list-modal" class="space-y-3">
                            ${(() => {
                                // Parse studying teachers from JSON or old fields
                                let studyingTeachers = [];
                                if (form.studying_teachers) {
                                    try {
                                        studyingTeachers = JSON.parse(form.studying_teachers);
                                    } catch (e) {
                                        studyingTeachers = [];
                                    }
                                } else {
                                    // Fallback to old fields
                                    for (let i = 1; i <= 3; i++) {
                                        const year = form['studying_year' + i];
                                        const count = form['studying_count' + i];
                                        if (year || count) {
                                            studyingTeachers.push({year: year, count: count});
                                        }
                                    }
                                }
                                
                                if (studyingTeachers.length === 0) {
                                    return '<p class="text-gray-500 py-2">ไม่มี</p>';
                                }
                                
                                return studyingTeachers.map((item, index) => {
                                    return '<div class="flex items-center gap-2 studying-teacher-item-modal">' +
                                        '<span class="text-sm">ในปี พ.ศ.</span>' +
                                        '<input type="number" name="studying_teachers[' + index + '][year]" value="' + (item.year || '') + '" class="w-24 px-2 py-1 border rounded" placeholder="เช่น 2565">' +
                                        '<span class="text-sm">จำนวน</span>' +
                                        '<input type="number" name="studying_teachers[' + index + '][count]" value="' + (item.count || '') + '" class="w-16 px-2 py-1 border rounded" min="0" placeholder="0">' +
                                        '<span class="text-sm">คน</span>' +
                                        '<button type="button" onclick="removeStudyingTeacherModal(this)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">🗑️</button>' +
                                        '</div>';
                                }).join('');
                            })()}
                        </div>
                    </div>

                    <!-- Section 5: Publications (Read-only) -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๕. ผลงานทางวิชาการของอาจารย์</h3>
                        <p class="text-sm text-gray-500 mb-2">* ข้อมูลดึงจากระบบ publications</p>
                        ${(form.publications || []).length > 0 ? `
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 border">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-center border">ลำดับ</th>
                                            <th class="px-3 py-2 border">ชื่อ-สกุล</th>
                                            <th class="px-3 py-2 text-center border">ประเภท</th>
                                            <th class="px-3 py-2 border">ชื่อผลงาน</th>
                                            <th class="px-3 py-2 text-center border">ปี พ.ศ.</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${form.publications.map((p, i) => {
                                            // Use author_name_th or author_name (same as view page)
                                            const authorName = p.author_name_th || p.author_name || '-';
                                            
                                            // Get publication type in Thai
                                            const pubType = p.publication_type || '-';
                                            const pubTypeThai = {
                                                'journal': 'วารสาร',
                                                'book': 'หนังสือ',
                                                'proceedings': 'ประชุมวิชาการ',
                                                'thesis': 'วิทยานิพนธ์',
                                                'report': 'รายงาน',
                                                'other': 'อื่นๆ'
                                            }[pubType] || pubType;
                                            
                                            return '<tr>' +
                                                '<td class="px-3 py-2 text-center border">' + (i + 1) + '</td>' +
                                                '<td class="px-3 py-2 border">' + escapeHtml(authorName) + '</td>' +
                                                '<td class="px-3 py-2 text-center border">' + escapeHtml(pubTypeThai) + '</td>' +
                                                '<td class="px-3 py-2 border text-sm">' + escapeHtml(p.title || '-') + '</td>' +
                                                '<td class="px-3 py-2 text-center border">' + (parseInt(p.publication_year || 0) + 543) + '</td>' +
                                            '</tr>';
                                        }).join('')}
                                    </tbody>
                                </table>
                            </div>
                        ` : '<p class="text-gray-500 text-center py-4">ไม่มีข้อมูลผลงาน</p>'}
                    </div>

                    <!-- Section 6: Target Groups -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๖. ข้อมูลกลุ่มผู้เรียนและจำนวนรับ</h3>
                        
                        <!-- Major/Minor Selection -->
                        <div class="mb-4">
                            <div class="flex items-center gap-4 mb-2">
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="has_major_minor" value="0" ${String(form.has_major_minor) === '1' ? '' : 'checked'} onchange="toggleMajorSectionModal()"> หลักสูตรไม่มีวิชาเอก/แขนง
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="has_major_minor" value="1" ${String(form.has_major_minor) === '1' ? 'checked' : ''} onchange="toggleMajorSectionModal()"> หลักสูตรมีวิชาเอก/แขนง
                                </label>
                            </div>

                            <!-- Majors Detail -->
                            <div id="major-section-modal" class="p-4 bg-gray-50 rounded-lg mt-3" style="display: ${String(form.has_major_minor) === '1' ? 'block' : 'none'}">
                                <div class="flex justify-between items-center mb-3">
                                    <label class="font-medium text-gray-700">รายละเอียดวิชาเอกแต่ละตัว</label>
                                    <button type="button" onclick="addMajorModal()" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">+ เพิ่ม</button>
                                </div>
                                <div id="majors-list-modal" class="space-y-2"></div>
                            </div>
                        </div>
                        
                        <!-- Admission Plan -->
                        <div class="flex items-center gap-2 mb-4 p-3 bg-gray-50 rounded-lg">
                            <span class="font-medium">แผนรับนักศึกษาตามรายละเอียดหลักสูตร จำนวน</span>
                            <input type="number" name="admission_plan_count" value="${form.admission_plan_count || ''}" class="w-20 px-2 py-1 border rounded">
                            <span>คน</span>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-4 bg-blue-50 rounded-lg">
                                <label class="flex items-center gap-2 font-medium text-blue-700 mb-2">
                                    <input type="checkbox" name="target_highschool" value="1" ${form.target_highschool ? 'checked' : ''}>
                                    มัธยมศึกษาตอนปลาย
                                </label>
                                <input type="number" name="target_highschool_count" value="${form.target_highschool_count || ''}" class="w-full px-3 py-2 border rounded-lg" placeholder="จำนวน (คน)">
                            </div>
                            <div class="p-4 bg-purple-50 rounded-lg">
                                <label class="flex items-center gap-2 font-medium text-purple-700 mb-2">
                                    <input type="checkbox" name="target_diploma" value="1" ${form.target_diploma ? 'checked' : ''}>
                                    ปวส./อนุปริญญา
                                </label>
                                <input type="number" name="target_diploma_count" value="${form.target_diploma_count || ''}" class="w-full px-3 py-2 border rounded-lg" placeholder="จำนวน (คน)">
                            </div>
                        </div>
                    </div>

                    <!-- Section 7: Qualifications with Add/Remove -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๗. คุณสมบัติของผู้เรียน</h3>

                        <!-- Per-branch qualifications (shown when หลักสูตรมีวิชาเอก/แขนง) -->
                        <div id="qual-branch-section" style="display:none">
                            <p class="text-sm text-gray-500 mb-3">กำหนดคุณสมบัติผู้เรียนแยกตามวิชาเอก/แขนงที่เพิ่มในข้อ ๖</p>
                            <div id="qual-branch-list"></div>
                        </div>

                        <!-- Global qualifications (shown when หลักสูตรไม่มีวิชาเอก/แขนง) -->
                        <div id="qual-global-section" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Highschool Qualifications -->
                            <div class="p-4 bg-blue-50 rounded-lg">
                                <div class="flex justify-between items-center mb-3">
                                    <label class="font-medium text-blue-700">คุณสมบัติ (มัธยม)</label>
                                    <button type="button" onclick="addQualification('highschool')" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">+ เพิ่ม</button>
                                </div>
                                <div id="qual-highschool-list" class="space-y-2">
                                    ${qualHighschool.length > 0 ? qualHighschool.map((q, i) => `
                                        <div class="flex gap-2 qual-item">
                                            <span class="text-gray-500 mt-2">${i + 1}.</span>
                                            <input type="text" class="flex-1 px-3 py-2 border rounded-lg qual-highschool-input" value="${q}">
                                            <button type="button" onclick="removeQualification(this)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">🗑️</button>
                                        </div>
                                    `).join('') : `
                                        <div class="flex gap-2 qual-item">
                                            <span class="text-gray-500 mt-2">1.</span>
                                            <input type="text" class="flex-1 px-3 py-2 border rounded-lg qual-highschool-input" placeholder="ระบุคุณสมบัติ">
                                            <button type="button" onclick="removeQualification(this)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">🗑️</button>
                                        </div>
                                    `}
                                </div>
                            </div>
                            
                            <!-- Diploma Qualifications -->
                            <div class="p-4 bg-purple-50 rounded-lg">
                                <div class="flex justify-between items-center mb-3">
                                    <label class="font-medium text-purple-700">คุณสมบัติ (ปวส.)</label>
                                    <button type="button" onclick="addQualification('diploma')" class="px-3 py-1 bg-purple-600 text-white rounded text-sm hover:bg-purple-700">+ เพิ่ม</button>
                                </div>
                                <div id="qual-diploma-list" class="space-y-2">
                                    ${qualDiploma.length > 0 ? qualDiploma.map((q, i) => `
                                        <div class="flex gap-2 qual-item">
                                            <span class="text-gray-500 mt-2">${i + 1}.</span>
                                            <input type="text" class="flex-1 px-3 py-2 border rounded-lg qual-diploma-input" value="${q}">
                                            <button type="button" onclick="removeQualification(this)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">🗑️</button>
                                        </div>
                                    `).join('') : `
                                        <div class="flex gap-2 qual-item">
                                            <span class="text-gray-500 mt-2">1.</span>
                                            <input type="text" class="flex-1 px-3 py-2 border rounded-lg qual-diploma-input" placeholder="ระบุคุณสมบัติ">
                                            <button type="button" onclick="removeQualification(this)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">🗑️</button>
                                        </div>
                                    `}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 8: Current Students -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๘. ข้อมูลจำนวนนักศึกษาปัจจุบัน</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- High School Track -->
                            <div class="p-4 bg-blue-50 rounded-lg">
                                <h3 class="font-medium text-blue-700 mb-3">มัธยมศึกษาตอนปลายหรือเทียบเท่า</h3>
                                <div class="space-y-2">
                                    ${(() => {
                                        let html = '';
                                        for (let y = 1; y <= 4; y++) {
                                            html += `
                                                <div class="flex items-center gap-2">
                                                    <span class="w-24 text-sm">ชั้นปีที่ ${y}</span>
                                                    <input type="number" name="current_highschool_year${y}" value="${form['current_highschool_year' + y] || ''}" class="w-20 px-2 py-1 border rounded">
                                                    <span class="text-sm">คน</span>
                                                </div>
                                            `;
                                        }
                                        html += '<div class="flex items-center gap-2">' +
                                            '<span class="w-24 text-sm">สำเร็จการศึกษา</span>' +
                                            '<input type="number" name="current_highschool_graduated" value="' + (form.current_highschool_graduated || '') + '" class="w-20 px-2 py-1 border rounded">' +
                                            '<span class="text-sm">คน</span>' +
                                            '</div>' +
                                            '<div class="flex items-center gap-2">' +
                                            '<span class="w-24 text-sm">นักศึกษาค้างชั้น</span>' +
                                            '<input type="number" name="current_highschool_remain" value="' + (form.current_highschool_remain || '') + '" class="w-20 px-2 py-1 border rounded">' +
                                            '<span class="text-sm">คน</span>' +
                                            '</div>';
                                        return html;
                                    })()}
                                </div>
                            </div>
                            
                            <!-- Diploma Track -->
                            <div class="p-4 bg-purple-50 rounded-lg">
                                <h3 class="font-medium text-purple-700 mb-3">ปวส./อนุปริญญา</h3>
                                <div class="space-y-2">
                                    ${(() => {
                                        let html = '';
                                        for (let y = 1; y <= 4; y++) {
                                            html += `
                                                <div class="flex items-center gap-2">
                                                    <span class="w-24 text-sm">ชั้นปีที่ ${y}</span>
                                                    <input type="number" name="current_diploma_year${y}" value="${form['current_diploma_year' + y] || ''}" class="w-20 px-2 py-1 border rounded">
                                                    <span class="text-sm">คน</span>
                                                </div>
                                            `;
                                        }
                                        html += '<div class="flex items-center gap-2">' +
                                            '<span class="w-24 text-sm">สำเร็จการศึกษา</span>' +
                                            '<input type="number" name="current_diploma_graduated" value="' + (form.current_diploma_graduated || '') + '" class="w-20 px-2 py-1 border rounded">' +
                                            '<span class="text-sm">คน</span>' +
                                            '</div>' +
                                            '<div class="flex items-center gap-2">' +
                                            '<span class="w-24 text-sm">นักศึกษาค้างชั้น</span>' +
                                            '<input type="number" name="current_diploma_remain" value="' + (form.current_diploma_remain || '') + '" class="w-20 px-2 py-1 border rounded">' +
                                            '<span class="text-sm">คน</span>' +
                                            '</div>';
                                        return html;
                                    })()}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 9: Development Plan -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">๙. แผนพัฒนานักศึกษาค้างชั้น</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">วิธีดำเนินการ</label>
                                <textarea name="remaining_student_plan" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg">${form.remaining_student_plan || ''}</textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ตัวชี้วัดความสำเร็จ</label>
                                <textarea name="remaining_student_kpi" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg">${form.remaining_student_kpi || ''}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Approvals -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-blue-600 border-b-2 border-blue-500 pb-2 mb-4">ความเห็นชอบ</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-4 bg-green-50 rounded-lg">
                                <h4 class="font-medium text-green-700 mb-3">ประธานหลักสูตร</h4>
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">นำเสนอและผ่านความเห็นชอบของคณะกรรมการบริหารหลักสูตรแล้วเมื่อ</label>
                                    <input type="text" class="datetimepicker-be w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 cursor-pointer" 
                                           id="curriculum_head_approval_date_picker_modal"
                                           data-target="curriculum_head_approval_date_modal"
                                           value="${formatDateToBE(form.curriculum_head_approval_date)}" 
                                           readonly>
                                    <input type="hidden" name="curriculum_head_approval_date" id="curriculum_head_approval_date_modal" value="${form.curriculum_head_approval_date || ''}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อประธานหลักสูตร</label>
                                    <input type="text" name="curriculum_head_name" value="${form.curriculum_head_name || ''}" class="w-full px-3 py-2 border rounded-lg" placeholder="ชื่อ" id="curriculum_head_name_input">
                                    ${form.chair_id ? `
                                        <p class="text-xs text-gray-500 mt-1">
                                            <span class="text-green-600">✓</span> ดึงข้อมูลจากระบบ: 
                                            ${(() => {
                                                let name = '';
                                                if (form.chair_name && form.chair_lastname) {
                                                    const title = form.chair_title ? form.chair_title + ' ' : '';
                                                    name = title + form.chair_name + ' ' + form.chair_lastname;
                                                } else if (form.chair_gf_name && form.chair_gl_name) {
                                                    const title = form.chair_title_en ? form.chair_title_en + ' ' : '';
                                                    name = title + form.chair_gf_name + ' ' + form.chair_gl_name;
                                                }
                                                return name || '-';
                                            })()}
                                        </p>
                                    ` : '<p class="text-xs text-gray-500 mt-1">ยังไม่ได้ตั้งประธานหลักสูตรในระบบ</p>'}
                                </div>
                            </div>
                            <div class="p-4 bg-emerald-50 rounded-lg">
                                <h4 class="font-medium text-orange-700 mb-3">คณบดี</h4>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อคณบดี</label>
                                    <input type="text" name="dean_name" value="${form.dean_name || ''}" class="w-full px-3 py-2 border rounded-lg overflow-visible" placeholder="ชื่อ" id="dean_name_input">
                                    ${form.dean_id ? `
                                        <p class="text-xs text-gray-500 mt-1">
                                            <span class="text-orange-600">✓</span> ดึงข้อมูลจากระบบ: 
                                            ${(() => {
                                                let name = '';
                                                // Use dean_name_db and dean_lastname_db to avoid conflict with form.dean_name
                                                const deanName = form.dean_name_db || form.dean_name_from_db;
                                                const deanLastname = form.dean_lastname_db || form.dean_lastname_from_db;
                                                const deanGfName = form.dean_gf_name;
                                                const deanGlName = form.dean_gl_name;
                                                
                                                if (deanName && deanLastname) {
                                                    const title = form.dean_title ? form.dean_title + ' ' : '';
                                                    name = title + deanName + ' ' + deanLastname;
                                                } else if (deanGfName && deanGlName) {
                                                    const title = form.dean_title_en ? form.dean_title_en + ' ' : '';
                                                    name = title + deanGfName + ' ' + deanGlName;
                                                }
                                                return name || '-';
                                            })()}
                                        </p>
                                    ` : '<p class="text-xs text-gray-500 mt-1">ยังไม่ได้ตั้งคณบดีในระบบ</p>'}
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            `;

            $('#modalBody').html(html);

            // Auto-fill chair and dean names from system
            fillFromSystemAuto(form);

            // Initialize date picker  
            // ตรวจสอบว่ามี jQuery และ datetimepicker หรือไม่
            if (typeof $.fn.datetimepicker !== 'undefined') {
                // jQuery DateTimePicker is available
                $.datetimepicker.setLocale('th');

                // Function to convert CE year to BE year in calendar header
                function convertCalendarYearToBE() {
                    setTimeout(function() {
                        // Find the year display in datetimepicker and convert to พ.ศ.
                        $('.xdsoft_datetimepicker .xdsoft_label.xdsoft_year span, .xdsoft_datetimepicker .xdsoft_year span').each(function() {
                            var $span = $(this);
                            var yearText = $span.text().trim();
                            var yearNum = parseInt(yearText);
                            // Only convert if it looks like a CE year (not already converted)
                            if (yearNum > 1900 && yearNum < 2200) {
                                $span.text(yearNum + 543);
                            }
                        });
                        // Also convert year options in dropdown
                        $('.xdsoft_datetimepicker .xdsoft_yearselect .xdsoft_option').each(function() {
                            var $opt = $(this);
                            var val = $opt.attr('data-value');
                            var yearNum = parseInt(val);
                            if (yearNum > 1900 && yearNum < 2200) {
                                $opt.text(yearNum + 543);
                            }
                        });
                    }, 50);
                }

                $('.datetimepicker-be').each(function() {
                    var $input = $(this);
                    var target = $input.data('target'); // ID ของ input ที่จะรับค่า ค.ศ. (Hidden Input)

                    // Helper function: แปลง พ.ศ. string เป็น Date object (ค.ศ.)
                    function parseBEtoDate(beString) {
                        if (!beString) return null;
                        var arr = beString.split("/");
                        if (arr.length !== 3) return null;
                        var day = parseInt(arr[0]);
                        var month = parseInt(arr[1]) - 1; // JavaScript month is 0-indexed
                        var yearBE = parseInt(arr[2]);
                        // ถ้าปี > 2400 แสดงว่าเป็น พ.ศ.
                        var yearAD = yearBE > 2400 ? yearBE - 543 : yearBE;
                        return new Date(yearAD, month, day);
                    }

                    $input.datetimepicker({
                        timepicker: false,
                        format: 'd/m/Y', // Format พื้นฐานของ Plugin ใช้ ค.ศ.
                        lang: 'th',
                        scrollMonth: false,
                        scrollInput: false,
                        closeOnDateSelect: true,
                        onShow: function(ct) {
                            // เมื่อเปิด picker: set date จากค่า พ.ศ. ใน input โดยไม่เปลี่ยน input value
                            var beValue = $input.val();
                            if (beValue) {
                                var dateObj = parseBEtoDate(beValue);
                                if (dateObj) {
                                    // Set picker's current date โดยใช้ setOptions
                                    this.setOptions({
                                        value: dateObj
                                    });
                                }
                            }
                            // Convert year display to พ.ศ.
                            convertCalendarYearToBE();
                        },
                        onChangeMonth: function(ct, $el) {
                            // Convert year display when month changes
                            convertCalendarYearToBE();
                        },
                        onChangeYear: function(ct, $el) {
                            // Convert year display when year changes
                            convertCalendarYearToBE();
                        },
                        onGenerate: function(ct, $el) {
                            // Convert year display when calendar is generated
                            convertCalendarYearToBE();
                        },
                        onSelectDate: function(dp, $el) {
                            // dp คือ object Date ของวันที่เลือก (ค.ศ.)
                            var yearAD = dp.getFullYear();
                            var yearBE = yearAD + 543;

                            var day = String(dp.getDate()).padStart(2, '0');
                            var month = String(dp.getMonth() + 1).padStart(2, '0');

                            // 1. แสดงผลใน Input ให้เป็น "พ.ศ." เสมอ
                            var dateBE = day + '/' + month + '/' + yearBE;
                            $el.val(dateBE);

                            // 2. ส่งค่า "ค.ศ." ไปที่ Hidden Input (ถ้ามี data-target)
                            if (target) {
                                var dateAD_ISO = yearAD + '-' + month + '-' + day; // Format: YYYY-MM-DD
                                $('#' + target).val(dateAD_ISO);
                            }
                        }
                    });

                    // ไม่ต้องมี event focus/blur เพราะใช้ onShow แทน
                    // Input จะแสดง พ.ศ. ตลอดเวลา ไม่เปลี่ยนเป็น ค.ศ. เมื่อเปิด picker
                });

            } else {
                // Fallback: กรณีโหลด Library ไม่สำเร็จ
                console.log('DateTimePicker not loaded, using fallback');
                $('.datetimepicker-be').each(function() {
                    var $input = $(this);
                    $input.attr('readonly', false);
                    $input.attr('placeholder', 'วว/ดด/ปปปป (พ.ศ.)');
                });
            }

            // Load majors data after form is rendered
            toggleMajorSectionModal();
            loadMajorsDataModal(form);
            // Build per-branch qualification cards from saved majors_detail (Section 7)
            initBranchQualifications(form);

            // Build dynamic footer buttons based on status and userRole
            let footerHtml = `<button onclick="closeModal()" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg">ยกเลิก</button>`;
            const currentStatus = form.status || 'draft';
            
            if (userRole === 'superadmin' || userRole === 'chair' || userRole === 'teacher') {
                if (currentStatus === 'draft' || currentStatus === 'rejected') {
                    footerHtml += `<button onclick="saveForm()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">💾 บันทึกแบบร่าง</button>`;
                    footerHtml += `<button onclick="saveForm('submitted')" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">📤 ส่งแบบเสนอ</button>`;
                } else {
                    footerHtml += `<button onclick="saveForm()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">💾 บันทึก</button>`;
                }
            } else if (userRole === 'dean' || userRole === 'faculty_admin') {
                if (currentStatus === 'submitted') {
                    footerHtml += `<button onclick="saveForm('rejected')" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">❌ ส่งกลับเพื่อแก้ไข</button>`;
                    footerHtml += `<button onclick="saveForm('approved')" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">✅ อนุมัติ</button>`;
                    footerHtml += `<button onclick="saveForm()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">💾 บันทึก</button>`;
                } else {
                    footerHtml += `<button onclick="saveForm()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">💾 บันทึก</button>`;
                }
            } else {
                footerHtml += `<button onclick="saveForm()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">💾 บันทึก</button>`;
            }
            
            $('#modalFooterActions').html(footerHtml);
        }

        function updateTeacherPosition(userId, position) {
            if (!userId) return;

            $.ajax({
                url: appRoute('admin/admission/update-position'),
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    user_id: userId,
                    position: position
                }),
                success: function(result) {
                    if (result.success) {
                        // Show brief success indicator
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'บันทึกตำแหน่งแล้ว',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else {
                        Swal.fire('ผิดพลาด', result.message || 'ไม่สามารถบันทึกได้', 'error');
                    }
                },
                error: function() {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อได้', 'error');
                }
            });
        }

        function addQualification(type) {
            const list = $(`#qual-${type}-list`);
            const count = list.find('.qual-item').length + 1;
            const bgClass = type === 'highschool' ? 'qual-highschool-input' : 'qual-diploma-input';
            const newItem = `
                <div class="flex gap-2 qual-item">
                    <span class="text-gray-500 mt-2">${count}.</span>
                    <input type="text" class="flex-1 px-3 py-2 border rounded-lg ${bgClass}" placeholder="ระบุคุณสมบัติ">
                    <button type="button" onclick="removeQualification(this)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">🗑️</button>
                </div>
            `;
            list.append(newItem);
            renumberQualifications(type);
        }

        function removeQualification(btn) {
            const item = $(btn).closest('.qual-item');
            const list = item.parent();
            const type = list.attr('id').includes('highschool') ? 'highschool' : 'diploma';

            if (list.find('.qual-item').length > 1) {
                item.remove();
                renumberQualifications(type);
            } else {
                item.find('input').val('');
            }
        }

        // Major management functions for modal
        function toggleMajorSectionModal() {
            const hasMajor = $('input[name="has_major_minor"]:checked').val() === '1';
            $('#major-section-modal').toggle(hasMajor);
            if (hasMajor && $('#majors-list-modal .major-item').length === 0) {
                addMajorModal();
            }
            syncBranchQualifications();
        }

        function addMajorModal() {
            const list = $('#majors-list-modal');
            const index = list.find('.major-item').length;
            const html = `
                <div class="major-item flex gap-2 items-center">
                    <span class="text-gray-500">${index + 1}.</span>
                    <input type="text" class="major-name flex-1 px-3 py-2 border rounded" placeholder="ชื่อวิชาเอก" oninput="updateBranchLabels()">
                    <span class="text-gray-600">จำนวน</span>
                    <input type="number" class="major-count w-20 px-3 py-2 border rounded" placeholder="0">
                    <span class="text-gray-600">คน</span>
                    <button type="button" onclick="removeMajorModal(this)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">🗑️</button>
                </div>
            `;
            list.append(html);
            syncBranchQualifications();
        }

        function removeMajorModal(btn) {
            const list = $('#majors-list-modal');
            if (list.find('.major-item').length > 1) {
                $(btn).closest('.major-item').remove();
                updateMajorNumbersModal();
                syncBranchQualifications();
            } else {
                Swal.fire('แจ้งเตือน', 'ต้องมีอย่างน้อย 1 วิชาเอก', 'warning');
            }
        }

        function updateMajorNumbersModal() {
            $('#majors-list-modal .major-item').each(function(index) {
                $(this).find('span:first-child').text(`${index + 1}.`);
            });
        }

        function loadMajorsDataModal(form) {
            let majors = [];
            try {
                if (form.majors_detail) {
                    majors = typeof form.majors_detail === 'string' ? JSON.parse(form.majors_detail) : form.majors_detail;
                    if (!Array.isArray(majors)) majors = [];
                }
            } catch (e) {
                majors = [];
            }

            const list = $('#majors-list-modal');
            list.empty();

            if (majors.length > 0) {
                majors.forEach((major, index) => {
                    const html = `
                        <div class="major-item flex gap-2 items-center">
                            <span class="text-gray-500">${index + 1}.</span>
                            <input type="text" class="major-name flex-1 px-3 py-2 border rounded" placeholder="ชื่อวิชาเอก" value="${escapeHtml(major.major_name || '')}" oninput="updateBranchLabels()">
                            <span class="text-gray-600">จำนวน</span>
                            <input type="number" class="major-count w-20 px-3 py-2 border rounded" placeholder="0" value="${major.admission_count || ''}">
                            <span class="text-gray-600">คน</span>
                            <button type="button" onclick="removeMajorModal(this)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">🗑️</button>
                        </div>
                    `;
                    list.append(html);
                });
            } else if ($('input[name="has_major_minor"]:checked').val() === '1') {
                addMajorModal();
            }
        }

        function renumberQualifications(type) {
            $(`#qual-${type}-list .qual-item`).each(function(i) {
                $(this).find('span').first().text((i + 1) + '.');
            });
        }

        function getQualificationsArray(type) {
            const items = [];
            $(`.qual-${type}-input`).each(function() {
                const val = $(this).val().trim();
                if (val) items.push(val);
            });
            return items;
        }

        // ===== Per-branch qualifications (Section 7 when หลักสูตรมีวิชาเอก/แขนง) =====

        function branchQualRowHtml(value, idx) {
            return `
                <div class="flex gap-2 branch-qual-item">
                    <span class="text-gray-500 mt-2 text-sm">${idx + 1}.</span>
                    <input type="text" class="flex-1 px-3 py-2 border rounded-lg branch-qual-input text-sm" value="${escapeHtml(value || '')}" placeholder="ระบุคุณสมบัติ">
                    <button type="button" onclick="removeBranchQual(this)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">🗑️</button>
                </div>`;
        }

        // อ่านคุณสมบัติรายแขนงจาก DOM ปัจจุบัน (เรียงตามลำดับการ์ด) เพื่อกันข้อมูลหายตอน rebuild
        function readBranchQualsFromDOM() {
            const result = [];
            $('#qual-branch-list .branch-qual-card').each(function() {
                const quals = [];
                $(this).find('.branch-qual-input').each(function() {
                    const v = $(this).val().trim();
                    if (v) quals.push(v);
                });
                result.push(quals);
            });
            return result;
        }

        // สร้างการ์ดคุณสมบัติรายแขนงใหม่จากรายการวิชาเอกในข้อ ๖ (รักษาค่าที่พิมพ์ไว้ตาม index)
        function syncBranchQualifications(initialQualsByIndex) {
            const hasMajor = $('input[name="has_major_minor"]:checked').val() === '1';
            $('#qual-global-section').toggle(!hasMajor);
            $('#qual-branch-section').toggle(hasMajor);
            if (!hasMajor) return;

            const existing = initialQualsByIndex || readBranchQualsFromDOM();
            const list = $('#qual-branch-list');
            list.empty();

            const majorItems = $('#majors-list-modal .major-item');
            if (majorItems.length === 0) {
                list.html('<p class="text-gray-500 text-sm">เพิ่มวิชาเอก/แขนงในข้อ ๖ ก่อน เพื่อกำหนดคุณสมบัติรายแขนง</p>');
                return;
            }

            majorItems.each(function(index) {
                const name = $(this).find('.major-name').val().trim() || `แขนงที่ ${index + 1}`;
                const quals = (existing[index] && existing[index].length) ? existing[index] : [''];
                const rows = quals.map((q, qi) => branchQualRowHtml(q, qi)).join('');
                list.append(`
                    <div class="branch-qual-card p-3 mb-3 bg-blue-50 border border-blue-200 rounded-lg" data-branch-index="${index}">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-medium text-blue-700 branch-label">${index + 1}. ${escapeHtml(name)}</span>
                            <button type="button" onclick="addBranchQual(this)" class="px-2 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700">+ เพิ่มคุณสมบัติ</button>
                        </div>
                        <div class="branch-qual-list space-y-2">${rows}</div>
                    </div>`);
            });
        }

        // อัปเดตเฉพาะชื่อหัวการ์ด (ไม่ rebuild ทั้งหมด เพื่อไม่ให้ focus หลุดตอนพิมพ์)
        function updateBranchLabels() {
            if (!$('#qual-branch-section').is(':visible')) return;
            $('#majors-list-modal .major-item').each(function(index) {
                const name = $(this).find('.major-name').val().trim() || `แขนงที่ ${index + 1}`;
                $(`#qual-branch-list .branch-qual-card[data-branch-index="${index}"] .branch-label`).text(`${index + 1}. ${name}`);
            });
        }

        function addBranchQual(btn) {
            const listEl = $(btn).closest('.branch-qual-card').find('.branch-qual-list');
            listEl.append(branchQualRowHtml('', listEl.find('.branch-qual-item').length));
        }

        function removeBranchQual(btn) {
            const item = $(btn).closest('.branch-qual-item');
            const listEl = item.parent();
            if (listEl.find('.branch-qual-item').length > 1) {
                item.remove();
            } else {
                item.find('input').val('');
            }
            listEl.find('.branch-qual-item > span').each(function(i) { $(this).text((i + 1) + '.'); });
        }

        // เรียกหลังโหลดวิชาเอกเสร็จ: สร้างการ์ดรายแขนงจาก majors_detail.qualifications
        function initBranchQualifications(form) {
            let majors = [];
            try {
                majors = form.majors_detail
                    ? (typeof form.majors_detail === 'string' ? JSON.parse(form.majors_detail) : form.majors_detail)
                    : [];
            } catch (e) { majors = []; }
            if (!Array.isArray(majors)) majors = [];
            const qualsByIndex = majors.map(m => (Array.isArray(m.qualifications) ? m.qualifications : []));
            syncBranchQualifications(qualsByIndex);
        }

        let viewFormId = null;

        function openViewModal(id) {
            viewFormId = id;
            $('#viewModal').addClass('active');
            $('#viewModalBody').html('<div class="flex justify-center py-8"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div></div>');

            $.ajax({
                url: appRoute('admin/admission/get/' + id),
                type: 'GET',
                success: function(result) {
                    if (result.success) {
                        renderViewForm(result.data);
                    } else {
                        $('#viewModalBody').html('<div class="text-center py-8 text-red-500">ไม่สามารถโหลดข้อมูลได้</div>');
                    }
                },
                error: function() {
                    $('#viewModalBody').html('<div class="text-center py-8 text-red-500">เกิดข้อผิดพลาด</div>');
                }
            });
        }

        function renderViewForm(form) {
            $('#viewModalTitle').text('ดูแบบฟอร์ม - ' + (form.curriculum_name_display || form.curriculum_name || ''));

            const positionOptions = ['อาจารย์', 'ดร.', 'ผศ.', 'ผศ.ดร.', 'รศ.', 'รศ.ดร.', 'ศ.', 'ศ.ดร.'];

            // Parse qualifications
            let qualHighschool = [];
            let qualDiploma = [];
            try {
                qualHighschool = form.qualification_highschool ? JSON.parse(form.qualification_highschool) : [];
            } catch (e) {
                qualHighschool = form.qualification_highschool ? form.qualification_highschool.split('\n').filter(q => q.trim()) : [];
            }
            try {
                qualDiploma = form.qualification_diploma ? JSON.parse(form.qualification_diploma) : [];
            } catch (e) {
                qualDiploma = form.qualification_diploma ? form.qualification_diploma.split('\n').filter(q => q.trim()) : [];
            }

            // Build Section 4: Teachers table
            let section4Html = '';
            const pubCountByTeacher = {};
            const years = [];
            const currentYear = parseInt(form.academic_year) || 2568;
            for (let i = 0; i < 5; i++) {
                years.push(currentYear - i);
            }

            (form.publications || []).forEach(p => {
                const uid = p.author_uid || p.uid || p.user_id;
                const pubYear = parseInt(p.publication_year || 0) + 543;
                if (!pubCountByTeacher[uid]) {
                    pubCountByTeacher[uid] = {};
                }
                pubCountByTeacher[uid][pubYear] = (pubCountByTeacher[uid][pubYear] || 0) + 1;
            });

            if ((form.teachers || []).length === 0) {
                section4Html = '<p class="text-gray-500">ไม่มีข้อมูลอาจารย์</p>';
            } else {
                let yearsHeaders = years.map(y => `<th class="px-2 py-1 border text-center bg-blue-50">${y}</th>`).join('');
                let teacherRows = form.teachers.map((t, i) => {
                    const uid = t.user_id;
                    const teacherPubs = pubCountByTeacher[uid] || {};
                    const total = years.reduce((sum, y) => sum + (teacherPubs[y] || 0), 0);
                    let yearCells = years.map(y => `<td class="px-2 py-1 border text-center">${teacherPubs[y] || 0}</td>`).join('');
                    return `<tr>
                        <td class="px-2 py-1 border text-center">${i+1}</td>
                        <td class="px-2 py-1 border">${t.titleThai || '-'}</td>
                        <td class="px-2 py-1 border">${t.thai_name || ''} ${t.thai_lastname || ''}</td>
                        ${yearCells}
                        <td class="px-2 py-1 border text-center font-semibold text-green-600">${total}</td>
                    </tr>`;
                }).join('');

                section4Html = `<div class="overflow-x-auto">
                    <table class="min-w-full border text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-2 py-1 border">ที่</th>
                                <th class="px-2 py-1 border">ตำแหน่ง</th>
                                <th class="px-2 py-1 border">ชื่อ-สกุล</th>
                                ${yearsHeaders}
                                <th class="px-2 py-1 border text-center bg-green-50">รวม</th>
                            </tr>
                        </thead>
                        <tbody>${teacherRows}</tbody>
                    </table>
                </div>`;
            }

            // Build Section 5: Publications by Teacher
            let section5Html = '';
            const publications = form.publications || [];
            const teachers = form.teachers || [];

            console.log('=== Section 5 Debug ===');
            console.log('Total publications received:', publications.length);
            console.log('Total teachers:', teachers.length);
            console.log('Publications data:', publications);
            console.log('Teachers data:', teachers);

            if (publications.length === 0) {
                section5Html = '<p class="text-gray-500">ไม่มีข้อมูลผลงาน</p>';
            } else {
                const pubsByTeacher = {};
                publications.forEach(p => {
                    const uid = p.author_uid || p.uid || p.user_id;
                    console.log('Publication:', {
                        id: p.id,
                        title: p.title?.substring(0, 30),
                        author_uid: p.author_uid,
                        uid: p.uid,
                        user_id: p.user_id,
                        matched_uid: uid
                    });
                    if (!pubsByTeacher[uid]) {
                        pubsByTeacher[uid] = [];
                    }
                    pubsByTeacher[uid].push(p);
                });

                console.log('pubsByTeacher:', pubsByTeacher);

                section5Html = '<div class="space-y-4">';
                teachers.forEach((t, teacherIndex) => {
                    const uid = t.user_id;
                    const teacherPubs = pubsByTeacher[uid] || [];
                    const pubCount = teacherPubs.length;

                    let pubList = '';
                    if (pubCount > 0) {
                        // แสดงผลงานทั้งหมดพร้อมรายละเอียดด้วย div layout
                        pubList = `<div class="ml-4 space-y-2">
                                    ${teacherPubs.map((p, idx) => {
                                        const pubType = p.publication_type || '-';
                                        const pubTypeThai = {
                                            'journal': 'วารสาร',
                                            'book': 'หนังสือ',
                                            'proceedings': 'ประชุมวิชาการ',
                                            'thesis': 'วิทยานิพนธ์',
                                            'report': 'รายงาน',
                                            'other': 'อื่นๆ'
                                        }[pubType] || pubType;
                                        
                                        const source = p.source || '-';
                                        const year = p.publication_year ? parseInt(p.publication_year) + 543 : '-';
                                        const volume = p.volume || '-';
                                        const notes = p.notes || p.abstract ? (p.notes || p.abstract).substring(0, 50) + '...' : '-';
                                        
                                        const notesHtml = notes !== '-' ? '<div class="flex items-start gap-2"><span class="font-medium text-gray-700 min-w-[60px]">เพิ่มเติม:</span><span class="flex-1 text-gray-600">' + escapeHtml(notes) + '</span></div>' : '';
                                        
                                        return '<div class="border border-gray-300 rounded p-3 hover:bg-gray-50 transition-colors">' +
                                            '<div class="grid grid-cols-12 gap-2 text-xs">' +
                                                '<div class="col-span-1 text-center font-medium text-gray-600">' + (idx + 1) + '</div>' +
                                                '<div class="col-span-11 space-y-1">' +
                                                    '<div class="flex items-start gap-2">' +
                                                        '<span class="font-medium text-gray-700 min-w-[60px]">เรื่อง:</span>' +
                                                        '<span class="flex-1">' + escapeHtml(p.title || '(ไม่ระบุชื่อ)') + '</span>' +
                                                    '</div>' +
                                                    '<div class="flex items-start gap-2">' +
                                                        '<span class="font-medium text-gray-700 min-w-[60px]">ประเภท:</span>' +
                                                        '<span class="flex-1">' + escapeHtml(pubTypeThai) + '</span>' +
                                                    '</div>' +
                                                    '<div class="flex items-start gap-2">' +
                                                        '<span class="font-medium text-gray-700 min-w-[60px]">วารสาร/ประชุม:</span>' +
                                                        '<span class="flex-1">' + escapeHtml(source) + '</span>' +
                                                    '</div>' +
                                                    '<div class="flex items-center gap-4">' +
                                                        '<div class="flex items-center gap-1">' +
                                                            '<span class="font-medium text-gray-700">ปีที่พิมพ์:</span>' +
                                                            '<span>' + year + '</span>' +
                                                        '</div>' +
                                                        '<div class="flex items-center gap-1">' +
                                                            '<span class="font-medium text-gray-700">ฉบับที่:</span>' +
                                                            '<span>' + escapeHtml(volume) + '</span>' +
                                                        '</div>' +
                                                    '</div>' +
                                                    notesHtml +
                                                '</div>' +
                                            '</div>' +
                                        '</div>';
                                    }).join('')}
                                </div>`;
                    } else {
                        pubList = '<p class="text-gray-400 text-sm ml-4">ไม่มีผลงาน</p>';
                    }

                    section5Html += `<div class="bg-gray-50 p-3 rounded-lg">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-medium">${teacherIndex + 1}. ${t.titleThai || ''} ${t.thai_name || ''} ${t.thai_lastname || ''}</span>
                                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded-full">${pubCount} ผลงาน</span>
                                </div>
                                ${pubList}
                            </div>`;
                });
                section5Html += '</div>';
                section5Html += `<p class="mt-3 text-sm text-gray-500">รวมทั้งหมด ${publications.length} ผลงาน</p>`;
            }

            // Parse majors detail (วิชาเอก/แขนง + คุณสมบัติรายแขนง)
            let viewMajors = [];
            try {
                viewMajors = form.majors_detail
                    ? (typeof form.majors_detail === 'string' ? JSON.parse(form.majors_detail) : form.majors_detail)
                    : [];
            } catch (e) { viewMajors = []; }
            if (!Array.isArray(viewMajors)) viewMajors = [];
            const hasMajorMinor = String(form.has_major_minor) === '1' && viewMajors.length > 0;

            // Build Section 6: Target Groups
            let section6Html = '';
            if (hasMajorMinor) {
                section6Html += '<p class="font-medium text-gray-700 mb-1">วิชาเอก/แขนง:</p>';
                section6Html += viewMajors.map((m, i) =>
                    `<p class="text-sm">${i+1}. ${escapeHtml(m.major_name || '-')} — รับ ${m.admission_count || 0} คน</p>`
                ).join('');
                section6Html += '<div class="mt-2"></div>';
            }
            if (form.target_highschool) {
                section6Html += `<p>✅ มัธยมศึกษาตอนปลาย: ${form.target_highschool_count || '-'} คน</p>`;
            }
            if (form.target_diploma) {
                section6Html += `<p>✅ ปวส./อนุปริญญา: ${form.target_diploma_count || '-'} คน</p>`;
            }
            if (!hasMajorMinor && !form.target_highschool && !form.target_diploma) {
                section6Html = '<p class="text-gray-500">ไม่ได้ระบุ</p>';
            }

            // Build Section 7: Qualifications
            let qualHighschoolHtml = qualHighschool.length > 0 ?
                qualHighschool.map((q, i) => `<p class="text-sm">${i+1}. ${q}</p>`).join('') :
                '<p class="text-gray-500 text-sm">ไม่ได้ระบุ</p>';
            let qualDiplomaHtml = qualDiploma.length > 0 ?
                qualDiploma.map((q, i) => `<p class="text-sm">${i+1}. ${q}</p>`).join('') :
                '<p class="text-gray-500 text-sm">ไม่ได้ระบุ</p>';

            // Section 7 body: per-branch (when มีวิชาเอก/แขนง) or global มัธยม/ปวส.
            let section7Html;
            if (hasMajorMinor) {
                section7Html = viewMajors.map((m, i) => {
                    const quals = Array.isArray(m.qualifications) ? m.qualifications.filter(q => q && q.trim()) : [];
                    const qualsHtml = quals.length > 0
                        ? quals.map((q, qi) => `<p class="text-sm ml-3">${qi+1}. ${escapeHtml(q)}</p>`).join('')
                        : '<p class="text-gray-500 text-sm ml-3">ไม่ได้ระบุ</p>';
                    return `<div class="mb-3">
                        <p class="font-medium text-blue-700 mb-1">${i+1}. ${escapeHtml(m.major_name || '-')}</p>
                        ${qualsHtml}
                    </div>`;
                }).join('');
            } else {
                section7Html = `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="font-medium text-blue-700 mb-1">มัธยม:</p>
                        ${qualHighschoolHtml}
                    </div>
                    <div>
                        <p class="font-medium text-purple-700 mb-1">ปวส.:</p>
                        ${qualDiplomaHtml}
                    </div>
                </div>`;
            }

            let html = `
                <div class="space-y-6">
                    <!-- Section 1 -->
                    <div class="border-l-4 border-blue-500 pl-4">
                        <h3 class="text-lg font-semibold text-blue-600 mb-2">๑. ข้อมูลหลักสูตร</h3>
                        <p><strong>ชื่อหลักสูตร:</strong> ${form.curriculum_name || form.curriculum_name_display || '-'}</p>
                        <p><strong>ฉบับปี พ.ศ.:</strong> ${form.curriculum_version_year || '-'}</p>
                    </div>
                    
                    <!-- Section 2 -->
                    <div class="border-l-4 border-blue-500 pl-4">
                        <h3 class="text-lg font-semibold text-blue-600 mb-2">๒. การพิจารณาจาก สป.อว.</h3>
                        <p><strong>วันที่ได้รับการพิจารณา:</strong> ${formatDateForDisplay(form.ministry_approval_date) || '-'}</p>
                        <p><strong>สภามหาวิทยาลัยเห็นชอบ:</strong> ${formatDateForDisplay(form.university_approval_date) || '-'}</p>
                    </div>
                    
                    <!-- Section 3 -->
                    <div class="border-l-4 border-blue-500 pl-4">
                        <h3 class="text-lg font-semibold text-blue-600 mb-2">๓. ผลการประเมินคุณภาพ ๒ ปีย้อนหลัง</h3>
                        <p><strong>ปี ${(form.academic_year || 2568) - 1}:</strong> ${form.quality_assessment_result1 || '-'}</p>
                        <p><strong>ปี ${(form.academic_year || 2568) - 2}:</strong> ${form.quality_assessment_result2 || '-'}</p>
                    </div>
                    
                    <!-- Section 4: Teachers with Publication Counts -->
                    <div class="border-l-4 border-blue-500 pl-4">
                        <h3 class="text-lg font-semibold text-blue-600 mb-2">๔. อาจารย์ผู้รับผิดชอบหลักสูตร</h3>
                        ${section4Html}
                    </div>
                    
                    <!-- Section 4.1: Teachers Status -->
                    <div class="border-l-4 border-blue-500 pl-4">
                        <h3 class="text-lg font-semibold text-blue-600 mb-2">๔.๑) การคงอยู่ของอาจารย์ในหลักสูตร</h3>
                        <div class="flex gap-6 items-center mb-2">
                            <span>${(form.teachers_status || '') === 'complete' ? '✓' : '○'} ครบ</span>
                            <span>${(form.teachers_status || '') === 'incomplete' ? '✓' : '○'} ไม่ครบ</span>
                        </div>
                        ${(form.teachers_status || '') === 'incomplete' ? (() => {
                            let incompleteTeachers = [];
                            if (form.teachers_incomplete_teachers) {
                                try {
                                    incompleteTeachers = JSON.parse(form.teachers_incomplete_teachers);
                                } catch (e) {
                                    incompleteTeachers = [];
                                }
                            } else if (form.teachers_incomplete_order) {
                                // Fallback to old field
                                const teacher = (form.teachers || []).find(t => t.order_num == form.teachers_incomplete_order);
                                if (teacher) {
                                    incompleteTeachers = [{
                                        user_id: teacher.user_id,
                                        name: teacher.full_name || (teacher.thai_name + ' ' + teacher.thai_lastname)
                                    }];
                                }
                            }
                            const teacherNames = incompleteTeachers.map(function(t) {
                                return t.name || (t.thai_name + ' ' + t.thai_lastname);
                            }).join(', ');
                            return '<div class="mt-2 text-sm">' +
                                '<p><strong>อาจารย์ที่ไม่ครบ:</strong> ' + (teacherNames || '-') + '</p>' +
                                '<p><strong>เนื่องจาก:</strong> ' + (form.teachers_incomplete_reason || '-') + '</p>' +
                                '</div>';
                        })() : ''}
                    </div>
                    
                    <!-- Section 4.2: Retiring Teachers -->
                    <div class="border-l-4 border-blue-500 pl-4">
                        <h3 class="text-lg font-semibold text-blue-600 mb-2">๔.๒) อาจารย์ที่เกษียณอายุราชการ</h3>
                        <div class="space-y-1">
                            ${(() => {
                                let retiringTeachers = [];
                                if (form.retiring_teachers) {
                                    try {
                                        retiringTeachers = JSON.parse(form.retiring_teachers);
                                    } catch (e) {
                                        retiringTeachers = [];
                                    }
                                } else {
                                    // Fallback to old fields
                                    for (let i = 1; i <= 3; i++) {
                                        const year = form['retiring_year' + i];
                                        const count = form['retiring_count' + i];
                                        if (year || count) {
                                            retiringTeachers.push({year: year, count: count});
                                        }
                                    }
                                }
                                return retiringTeachers.length > 0 
                                    ? retiringTeachers.map(item => '<p>ในปี พ.ศ. ' + (item.year || '-') + ' จำนวน ' + (item.count || '-') + ' คน</p>').join('')
                                    : '<p class="text-gray-500">-</p>';
                            })()}
                        </div>
                    </div>

                    <!-- Section 4.3: Teachers Pursuing Further Studies -->
                    <div class="border-l-4 border-blue-500 pl-4">
                        <h3 class="text-lg font-semibold text-blue-600 mb-2">๔.๓) อาจารย์ศึกษาต่อ</h3>
                        <div class="space-y-1">
                            ${(() => {
                                let studyingTeachers = [];
                                if (form.studying_teachers) {
                                    try {
                                        studyingTeachers = JSON.parse(form.studying_teachers);
                                    } catch (e) {
                                        studyingTeachers = [];
                                    }
                                } else {
                                    // Fallback to old fields
                                    for (let i = 1; i <= 3; i++) {
                                        const year = form['studying_year' + i];
                                        const count = form['studying_count' + i];
                                        if (year || count) {
                                            studyingTeachers.push({
                                                year: year,
                                                count: count
                                            });
                                        }
                                    }
                                }
                                return studyingTeachers.length > 0 ?
                                    studyingTeachers.map(function(item) {
                                        return '<p>ในปี พ.ศ. ' + (item.year || '-') + ' จำนวน ' + (item.count || '-') + ' คน</p>';
                                    }).join('') :
                                    '<p class="text-gray-500">-</p>';
                            })()}
                        </div>
                    </div>

                    <!-- Section 5: Publications by Teacher -->
                    <div class="border-l-4 border-blue-500 pl-4">
                        <h3 class="text-lg font-semibold text-blue-600 mb-2">๕. ผลงานทางวิชาการ</h3>
                        ${section5Html}
                    </div>

                    <!-- Section 6: Target Groups -->
                    <div class="border-l-4 border-blue-500 pl-4">
                        <h3 class="text-lg font-semibold text-blue-600 mb-2">๖. กลุ่มผู้เรียน</h3>
                        ${section6Html}
                    </div>

                    <!-- Section 7: Qualifications -->
                    <div class="border-l-4 border-blue-500 pl-4">
                        <h3 class="text-lg font-semibold text-blue-600 mb-2">๗. คุณสมบัติของผู้เรียน</h3>
                        ${section7Html}
                    </div>

                    <!-- Section 8: Current Students -->
                    <div class="border-l-4 border-blue-500 pl-4">
                        <h3 class="text-lg font-semibold text-blue-600 mb-2">๘. ข้อมูลจำนวนนักศึกษาปัจจุบัน</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-3 bg-blue-50 rounded">
                                <p class="font-medium text-blue-700 mb-2">มัธยมศึกษาตอนปลายหรือเทียบเท่า</p>
                                ${(() => {
                                    let html = '';
                                    for (let y = 1; y <= 4; y++) {
                                        html += '<p class="text-sm">ชั้นปีที่ ' + y + ': ' + (form['current_highschool_year' + y] || '-') + ' คน</p>';
                                    }
                                    html += '<p class="text-sm">สำเร็จการศึกษา: ' + (form.current_highschool_graduated || '-') + ' คน</p>';
                                    html += '<p class="text-sm">นักศึกษาค้างชั้น: ' + (form.current_highschool_remain || '-') + ' คน</p>';
                                    return html;
                                })()}
                            </div>
                            <div class="p-3 bg-purple-50 rounded">
                                <p class="font-medium text-purple-700 mb-2">ปวส. / อนุปริญญา</p>
                                ${(() => {
                                    let html = '';
                                    for (let y = 1; y <= 4; y++) {
                                        html += '<p class="text-sm">ชั้นปีที่ ' + y + ': ' + (form['current_diploma_year' + y] || '-') + ' คน</p>';
                                    }
                                    html += '<p class="text-sm">สำเร็จการศึกษา: ' + (form.current_diploma_graduated || '-') + ' คน</p>';
                                    html += '<p class="text-sm">นักศึกษาค้างชั้น: ' + (form.current_diploma_remain || '-') + ' คน</p>';
                                    return html;
                                })()}
                            </div>
                        </div>
                    </div>

                    <!-- Approvals -->
                    <div class="border-l-4 border-green-500 pl-4">
                        <h3 class="text-lg font-semibold text-green-600 mb-2">ความเห็นชอบ</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-3 bg-green-50 rounded">
                                <p class="font-medium">ประธานหลักสูตร:</p>
                                <p>${form.curriculum_head_name || '-'}</p>
                                <p class="text-sm text-gray-500">${formatDateForDisplay(form.curriculum_head_approval_date) || '-'}</p>
                            </div>
                            <div class="p-3 bg-orange-50 rounded">
                                <p class="font-medium">คณบดี:</p>
                                <p>${form.dean_name || '-'}</p>
                                <p class="text-sm text-gray-500">${formatDateForDisplay(form.dean_approval_date) || '-'}</p>
                            </div>
                        </div>
                    </div>
            `;

            $('#viewModalBody').html(html);
        }

        function closeViewModal() {
            $('#viewModal').removeClass('active');
            viewFormId = null;
        }

        function generatePDFForCurrentForm() {
            if (viewFormId) {
                const pdfUrl = appRoute('admin/admission/pdf-summary/' + viewFormId);
                window.open(pdfUrl, '_blank');
            }
        }

        function closeModal() {
            $('#editModal').removeClass('active');
            currentFormId = null;
        }

        // Add/Remove functions for retiring teachers (modal)
        function addRetiringTeacherModal() {
            const list = $('#retiring-teachers-list-modal');

            // Remove "ไม่มี" text if exists
            const noDataText = list.find('p');
            if (noDataText.length > 0) {
                noDataText.remove();
            }

            const index = list.find('.retiring-teacher-item-modal').length;
            const html = '<div class="flex items-center gap-2 retiring-teacher-item-modal">' +
                '<span class="text-sm">ในปี พ.ศ.</span>' +
                '<input type="number" name="retiring_teachers[' + index + '][year]" class="w-24 px-2 py-1 border rounded" placeholder="เช่น 2565">' +
                '<span class="text-sm">จำนวน</span>' +
                '<input type="number" name="retiring_teachers[' + index + '][count]" class="w-16 px-2 py-1 border rounded" min="0" placeholder="0">' +
                '<span class="text-sm">คน</span>' +
                '<button type="button" onclick="removeRetiringTeacherModal(this)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">🗑️</button>' +
                '</div>';
            list.append($(html));
        }

        function removeRetiringTeacherModal(button) {
            const list = $('#retiring-teachers-list-modal');
            const items = list.find('.retiring-teacher-item-modal');

            if (items.length > 1) {
                $(button).closest('.retiring-teacher-item-modal').remove();
            } else {
                // Remove the last item and show "ไม่มี"
                $(button).closest('.retiring-teacher-item-modal').remove();
                list.append('<p class="text-gray-500 py-2">ไม่มี</p>');
            }
        }

        // Add/Remove functions for studying teachers (modal)
        function addStudyingTeacherModal() {
            const list = $('#studying-teachers-list-modal');

            // Remove "ไม่มี" text if exists
            const noDataText = list.find('p');
            if (noDataText.length > 0) {
                noDataText.remove();
            }

            const index = list.find('.studying-teacher-item-modal').length;
            const html = '<div class="flex items-center gap-2 studying-teacher-item-modal">' +
                '<span class="text-sm">ในปี พ.ศ.</span>' +
                '<input type="number" name="studying_teachers[' + index + '][year]" class="w-24 px-2 py-1 border rounded" placeholder="เช่น 2565">' +
                '<span class="text-sm">จำนวน</span>' +
                '<input type="number" name="studying_teachers[' + index + '][count]" class="w-16 px-2 py-1 border rounded" min="0" placeholder="0">' +
                '<span class="text-sm">คน</span>' +
                '<button type="button" onclick="removeStudyingTeacherModal(this)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">🗑️</button>' +
                '</div>';
            list.append($(html));
        }

        function removeStudyingTeacherModal(button) {
            const list = $('#studying-teachers-list-modal');
            const items = list.find('.studying-teacher-item-modal');

            if (items.length > 1) {
                $(button).closest('.studying-teacher-item-modal').remove();
            } else {
                // Remove the last item and show "ไม่มี"
                $(button).closest('.studying-teacher-item-modal').remove();
                list.append('<p class="text-gray-500 py-2">ไม่มี</p>');
            }
        }

        // Toggle incomplete fields visibility
        function toggleIncompleteFields() {
            const incompleteFields = $('#incomplete-fields');
            const isIncomplete = $('input[name="teachers_status"]:checked').val() === 'incomplete';
            incompleteFields.toggle(isIncomplete);
        }

        function saveForm(status = null) {
            if (!currentFormId) return;

            const formData = $('#admissionForm').serializeArray();
            const data = {};
            formData.forEach(item => data[item.name] = item.value);

            // Handle checkboxes
            data.target_highschool = $('input[name="target_highschool"]').is(':checked') ? 1 : 0;
            data.target_diploma = $('input[name="target_diploma"]').is(':checked') ? 1 : 0;

            // Collect qualifications as JSON arrays
            data.qualification_highschool = JSON.stringify(getQualificationsArray('highschool'));
            data.qualification_diploma = JSON.stringify(getQualificationsArray('diploma'));

            // Handle majors detail - convert array to JSON (รวมคุณสมบัติรายแขนงจากข้อ ๗)
            const majors = [];
            $('#majors-list-modal .major-item').each(function(index) {
                const majorName = $(this).find('.major-name').val().trim();
                const admissionCount = $(this).find('.major-count').val();
                if (majorName) {
                    const quals = [];
                    $(`#qual-branch-list .branch-qual-card[data-branch-index="${index}"] .branch-qual-input`).each(function() {
                        const v = $(this).val().trim();
                        if (v) quals.push(v);
                    });
                    majors.push({
                        major_name: majorName,
                        admission_count: admissionCount ? parseInt(admissionCount) : 0,
                        qualifications: quals
                    });
                }
            });
            data.majors_detail = majors.length > 0 ? JSON.stringify(majors) : null;
            data.major_count = majors.length;

            // Handle incomplete teachers - convert checkbox array to JSON
            const incompleteTeacherIds = [];
            $('input[name="teachers_incomplete_teachers[]"]:checked').each(function() {
                incompleteTeacherIds.push($(this).val());
            });

            if (incompleteTeacherIds.length > 0) {
                // Get teacher details from form.teachers
                const teachers = window.currentFormData?.teachers || [];
                const incompleteTeachers = incompleteTeacherIds.map(function(userId) {
                    const teacher = teachers.find(function(t) {
                        return (t.user_id || t.id) == userId;
                    });
                    if (teacher) {
                        return {
                            user_id: teacher.user_id || teacher.id,
                            name: teacher.full_name || (teacher.thai_name + ' ' + teacher.thai_lastname)
                        };
                    }
                    return {
                        user_id: userId,
                        name: ''
                    };
                });
                data.teachers_incomplete_teachers = JSON.stringify(incompleteTeachers);
            } else {
                data.teachers_incomplete_teachers = null;
            }

            // Handle retiring teachers - convert array to JSON
            const retiringTeachers = [];
            $('input[name^="retiring_teachers["]').each(function() {
                const name = $(this).attr('name');
                const match = name.match(/retiring_teachers\[(\d+)\]\[(year|count)\]/);
                if (match) {
                    const index = parseInt(match[1]);
                    const field = match[2];
                    if (!retiringTeachers[index]) {
                        retiringTeachers[index] = {};
                    }
                    retiringTeachers[index][field] = $(this).val();
                }
            });
            // Filter out empty entries and convert to JSON
            const validRetiring = retiringTeachers.filter(item => item && (item.year || item.count));
            data.retiring_teachers = validRetiring.length > 0 ? JSON.stringify(validRetiring) : null;

            // Handle studying teachers - convert array to JSON
            const studyingTeachers = [];
            $('input[name^="studying_teachers["]').each(function() {
                const name = $(this).attr('name');
                const match = name.match(/studying_teachers\[(\d+)\]\[(year|count)\]/);
                if (match) {
                    const index = parseInt(match[1]);
                    const field = match[2];
                    if (!studyingTeachers[index]) {
                        studyingTeachers[index] = {};
                    }
                    studyingTeachers[index][field] = $(this).val();
                }
            });
            // Filter out empty entries and convert to JSON
            const validStudying = studyingTeachers.filter(item => item && (item.year || item.count));
            data.studying_teachers = validStudying.length > 0 ? JSON.stringify(validStudying) : null;

            if (status) data.status = status;

            $.ajax({
                url: appRoute('admin/admission/save/' + currentFormId),
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                success: function(result) {
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: result.message || 'บันทึกข้อมูลเรียบร้อยแล้ว',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            closeModal();
                            location.reload();
                        });
                    } else {
                        Swal.fire('ผิดพลาด', result.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถบันทึกได้', 'error');
                }
            });
        }

        /**
         * Generate and download PDF for admission form
         * Opens PDF generation page in new window
         */
        function generatePDF(id) {
            if (!id) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่พบรหัสแบบฟอร์ม',
                    confirmButtonText: 'ตกลง'
                });
                return;
            }

            // Open PDF generation page in new window
            const pdfUrl = appRoute('admin/admission/pdf-summary/' + id);
            window.open(pdfUrl, '_blank');
        }

        // Make generatePDF available globally
        window.generatePDF = generatePDF;

        // Auto-fill function (called automatically when form loads)
        function fillFromSystemAuto(form) {
            // Fill curriculum head name
            if (form.chair_id) {
                let chairName = '';
                if (form.chair_name && form.chair_lastname) {
                    const title = form.chair_title ? form.chair_title + ' ' : '';
                    chairName = title + form.chair_name + ' ' + form.chair_lastname;
                } else if (form.chair_gf_name && form.chair_gl_name) {
                    const title = form.chair_title_en ? form.chair_title_en + ' ' : '';
                    chairName = title + form.chair_gf_name + ' ' + form.chair_gl_name;
                }
                // Auto-fill only if field is empty
                if (chairName && !form.curriculum_head_name) {
                    $('#curriculum_head_name_input').val(chairName);
                }
            }

            // Fill dean name
            if (form.dean_id) {
                let deanName = '';
                // Use dean_name_from_db to avoid conflict with form.dean_name (saved value)
                const deanNameDb = form.dean_name_from_db || '';
                const deanLastnameDb = form.dean_lastname_from_db || '';
                const deanGfName = form.dean_gf_name || '';
                const deanGlName = form.dean_gl_name || '';

                // Try Thai name first
                if (deanNameDb && deanLastnameDb) {
                    const title = form.dean_title ? form.dean_title + ' ' : '';
                    deanName = title + deanNameDb + ' ' + deanLastnameDb;
                }
                // Fallback to English name
                else if (deanGfName && deanGlName) {
                    const title = form.dean_title_en ? form.dean_title_en + ' ' : '';
                    deanName = title + deanGfName + ' ' + deanGlName;
                }

                // Auto-fill with full name from system (always, to ensure complete name)
                // This fixes the issue where saved value might be incomplete
                if (deanName) {
                    $('#dean_name_input').val(deanName);
                }
            }
        }

        // Manual fill function (called when button is clicked)
        function fillFromSystem() {
            // Get data from the form object (passed from renderEditForm)
            const form = window.currentFormData || {};

            // Fill curriculum head name (always fill, even if already has value)
            if (form.chair_id) {
                let chairName = '';
                if (form.chair_name && form.chair_lastname) {
                    const title = form.chair_title ? form.chair_title + ' ' : '';
                    chairName = title + form.chair_name + ' ' + form.chair_lastname;
                } else if (form.chair_gf_name && form.chair_gl_name) {
                    const title = form.chair_title_en ? form.chair_title_en + ' ' : '';
                    chairName = title + form.chair_gf_name + ' ' + form.chair_gl_name;
                }
                if (chairName) {
                    $('#curriculum_head_name_input').val(chairName);
                }
            }

            // Fill dean name (always fill, even if already has value)
            if (form.dean_id) {
                let deanName = '';
                // Use dean_name_from_db to avoid conflict
                const deanNameDb = form.dean_name_from_db;
                const deanLastnameDb = form.dean_lastname_from_db;

                if (deanNameDb && deanLastnameDb) {
                    const title = form.dean_title ? form.dean_title + ' ' : '';
                    deanName = title + deanNameDb + ' ' + deanLastnameDb;
                } else if (form.dean_gf_name && form.dean_gl_name) {
                    const title = form.dean_title_en ? form.dean_title_en + ' ' : '';
                    deanName = title + form.dean_gf_name + ' ' + form.dean_gl_name;
                }
                if (deanName) {
                    $('#dean_name_input').val(deanName);
                }
            }

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'ดึงข้อมูลจากระบบแล้ว',
                showConfirmButton: false,
                timer: 1500
            });
        }

        // Close modal on escape key
        $(document).keydown(function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
</body>

</html>