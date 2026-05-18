<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'จัดการประวัติการศึกษา' ?></title>
    <link rel="stylesheet" href="<?= base_url('/public/assets/css/tailwind.min.css') ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin-common.css') ?>">

    <!-- pdfMake for PDF generation -->
    <script src="<?= base_url(); ?>pdfmake/build/pdfmake.min.js"></script>
    <script src="<?= base_url(); ?>pdfmake/build/vfs_fonts.js"></script>
    <script>
        if (typeof pdfMake !== 'undefined') {
            pdfMake.fonts = {
                Sarabun: {
                    normal: 'THSarabunNew.ttf',
                    bold: 'THSarabunNew Bold.ttf',
                    italics: 'THSarabunNew Italic.ttf',
                    bolditalics: 'THSarabunNew BoldItalic.ttf'
                },
                Roboto: {
                    normal: 'Roboto-Regular.ttf',
                    bold: 'Roboto-Medium.ttf',
                    italics: 'Roboto-Italic.ttf',
                    bolditalics: 'Roboto-MediumItalic.ttf'
                }
            };
        }
    </script>

    <style>
        .education-card {
            transition: all 0.2s ease;
        }
        .education-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .modal-overlay {
            backdrop-filter: blur(4px);
        }
    </style>
</head>

<body class="min-h-full">
    <div class="min-h-full bg-gray-50">
        <!-- Top Navigation Bar -->
        <?php
        $pageTitle = $title ?? 'จัดการประวัติการศึกษา';
        $pageSubtitle = 'Education History Management';
        ?>
        <?= view('admin/partials/header', ['pageTitle' => $pageTitle, 'pageSubtitle' => $pageSubtitle]) ?>

        <div class="flex">
            <!-- Sidebar Navigation -->
            <?= view('admin/partials/navigation') ?>

            <!-- Main Content -->
            <main class="flex-1 p-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">จัดการประวัติการศึกษา</h2>
                                <p class="text-sm text-gray-600 mt-1">เพิ่ม/แก้ไขประวัติการศึกษาของผู้ใช้ในระบบ</p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <!-- Faculty Filter -->
                                <select id="facultyFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                                    <?php if (empty($isFacultyAdmin)): ?>
                                        <option value="">ทุกคณะ</option>
                                    <?php endif; ?>
                                    <?php foreach ($faculties as $faculty): ?>
                                        <option value="<?= $faculty['id'] ?>"><?= esc($faculty['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <!-- Search -->
                                <div class="relative">
                                    <input type="text" id="searchInput" placeholder="ค้นหาชื่อ/อีเมล..."
                                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 w-64">
                                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>

                                <!-- PDF Report Button -->
                                <button onclick="generatePDFReport()"
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span>สร้างรายงาน PDF</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <!-- Users Table -->
                        <table id="usersTable" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ผู้ใช้</th>
                                    <th>อีเมล</th>
                                    <th>คณะ</th>
                                    <th>จำนวนประวัติการศึกษา</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Education Modal -->
    <div id="educationModal" class="hidden fixed inset-0 z-[9999] flex items-start justify-center bg-gray-900 bg-opacity-70 modal-overlay overflow-y-auto py-10 px-4">
        <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl">
            <!-- Modal Header -->
            <div class="flex justify-between items-center px-8 py-6 border-b border-gray-100">
                <div>
                    <p class="text-sm text-blue-600 font-semibold uppercase tracking-wide">Education History</p>
                    <h3 class="text-2xl font-bold text-gray-900">ประวัติการศึกษา</h3>
                    <p id="modalUserName" class="text-sm text-gray-600 mt-1"></p>
                </div>
                <button onclick="closeEducationModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="px-8 py-6 max-h-[60vh] overflow-y-auto">
                <input type="hidden" id="currentUserUid">

                <!-- Education Entries List -->
                <div id="educationEntriesList" class="space-y-4 mb-6">
                    <!-- Entries will be loaded here -->
                </div>

                <!-- Add New Button -->
                <button onclick="showEntryForm()" class="w-full py-3 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-blue-500 hover:text-blue-500 transition-colors">
                    + เพิ่มประวัติการศึกษาใหม่
                </button>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end space-x-3 px-8 py-6 border-t border-gray-100 rounded-b-2xl">
                <button type="button" onclick="closeEducationModal()"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    ปิด
                </button>
            </div>
        </div>
    </div>

    <!-- Entry Form Modal -->
    <div id="entryFormModal" class="hidden fixed inset-0 z-[10000] flex items-start justify-center bg-gray-900 bg-opacity-70 modal-overlay overflow-y-auto py-10 px-4">
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl">
            <!-- Form Header -->
            <div class="flex justify-between items-center px-8 py-6 border-b border-gray-100">
                <div>
                    <p class="text-sm text-green-600 font-semibold uppercase tracking-wide" id="entryFormTitle">Add Education</p>
                    <h3 class="text-xl font-bold text-gray-900" id="entryFormSubtitle">เพิ่มประวัติการศึกษา</h3>
                </div>
                <button onclick="closeEntryForm()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Form Body -->
            <form id="entryForm" class="px-8 py-6 space-y-5">
                <input type="hidden" id="entryId" name="entry_id">
                <input type="hidden" id="entryUserUid" name="user_uid">

                <!-- Degree/Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ปริญญา/วุฒิการศึกษา <span class="text-red-500">*</span></label>
                    <input type="text" id="entryTitle" name="title" required
                        placeholder="เช่น ปริญญาตรี วิทยาศาสตรบัณฑิต, Ph.D. Computer Science"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Institution -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">สถาบันการศึกษา</label>
                    <input type="text" id="entryOrganization" name="organization"
                        placeholder="เช่น มหาวิทยาลัยเชียงใหม่, MIT"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Location -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">สถานที่/ประเทศ</label>
                    <input type="text" id="entryLocation" name="location"
                        placeholder="เช่น เชียงใหม่, ประเทศไทย"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Date Range -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ปีที่เริ่ม</label>
                        <input type="date" id="entryStartDate" name="start_date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ปีที่จบ</label>
                        <input type="date" id="entryEndDate" name="end_date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Currently Studying -->
                <div class="flex items-center">
                    <input type="checkbox" id="entryIsCurrent" name="is_current" value="1"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="entryIsCurrent" class="ml-2 text-sm text-gray-700">กำลังศึกษาอยู่</label>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">รายละเอียดเพิ่มเติม</label>
                    <textarea id="entryDescription" name="description" rows="3"
                        placeholder="เช่น สาขาวิชา, เกียรตินิยม, หัวข้อวิทยานิพนธ์"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
            </form>

            <!-- Form Footer -->
            <div class="flex justify-end space-x-3 px-8 py-6 border-t border-gray-100 rounded-b-2xl">
                <button type="button" onclick="closeEntryForm()"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    ยกเลิก
                </button>
                <button type="submit" form="entryForm"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    บันทึก
                </button>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?= rtrim(base_url(), '/') ?>';
        let usersTable;
        let currentEducationData = [];
        const IS_FACULTY_ADMIN = <?= !empty($isFacultyAdmin) ? 'true' : 'false' ?>;
        const DEFAULT_FACULTY_ID = <?= !empty($isFacultyAdmin) && !empty($faculties) ? (int)($faculties[0]['id'] ?? 0) : 0 ?>;

        $(document).ready(function() {
            // Faculty admin: lock filter to their faculty (first managed faculty)
            if (IS_FACULTY_ADMIN) {
                if (DEFAULT_FACULTY_ID) {
                    $('#facultyFilter').val(String(DEFAULT_FACULTY_ID));
                }
                // Prevent changing to other values (dropdown already limited)
                $('#facultyFilter').prop('disabled', true).addClass('bg-gray-100 cursor-not-allowed');
            }

            // Initialize DataTable
            usersTable = $('#usersTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: `${BASE_URL}/index.php/admin/education/users`,
                    dataSrc: function(json) {
                        return json.data || [];
                    }
                },
                columns: [
                    {
                        data: null,
                        render: function(data) {
                            const thaiName = (data.thai_name || '') + ' ' + (data.thai_lastname || '');
                            const engName = (data.gf_name || '') + ' ' + (data.gl_name || '');
                            const displayName = thaiName.trim() || engName.trim() || 'ไม่ระบุชื่อ';

                            return `
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-semibold">
                                        ${displayName.charAt(0).toUpperCase()}
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">${displayName}</div>
                                        ${engName.trim() && thaiName.trim() ? `<div class="text-xs text-gray-500">${engName.trim()}</div>` : ''}
                                    </div>
                                </div>
                            `;
                        }
                    },
                    { data: 'email' },
                    {
                        data: 'faculty_name',
                        render: function(data) {
                            return data || '<span class="text-gray-400">ไม่ระบุ</span>';
                        }
                    },
                    {
                        data: 'education_count',
                        render: function(data) {
                            const count = parseInt(data) || 0;
                            if (count === 0) {
                                return '<span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded">ยังไม่มี</span>';
                            }
                            return `<span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded">${count} รายการ</span>`;
                        },
                        className: 'text-center'
                    },
                    {
                        data: null,
                        render: function(data) {
                            return `
                                <button onclick="openEducationModal('${data.uid}')"
                                    class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                                    จัดการ
                                </button>
                            `;
                        },
                        className: 'text-center'
                    }
                ],
                language: {
                    search: "ค้นหา:",
                    lengthMenu: "แสดง _MENU_ รายการ",
                    info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                    infoEmpty: "ไม่มีข้อมูล",
                    infoFiltered: "(กรองจาก _MAX_ รายการ)",
                    paginate: {
                        first: "หน้าแรก",
                        last: "หน้าสุดท้าย",
                        next: "ถัดไป",
                        previous: "ก่อนหน้า"
                    },
                    processing: "กำลังโหลด..."
                },
                pageLength: 25,
                order: [[0, 'asc']]
            });

            // Faculty Filter
            $('#facultyFilter').on('change', function() {
                reloadUsers();
            });

            // Search with debounce
            let searchTimeout;
            $('#searchInput').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    reloadUsers();
                }, 300);
            });

            // Entry Form Submit
            $('#entryForm').on('submit', function(e) {
                e.preventDefault();
                saveEntry();
            });

            // Close modals on overlay click
            $('#educationModal').on('click', function(e) {
                if (e.target === this) closeEducationModal();
            });
            $('#entryFormModal').on('click', function(e) {
                if (e.target === this) closeEntryForm();
            });
        });

        function reloadUsers() {
            const faculty = $('#facultyFilter').val();
            const search = $('#searchInput').val();

            usersTable.ajax.url(`${BASE_URL}/index.php/admin/education/users?faculty_id=${faculty}&search=${encodeURIComponent(search)}`).load();
        }

        function openEducationModal(userUid) {
            $('#currentUserUid').val(userUid);
            $('#educationModal').removeClass('hidden');
            loadEducationEntries(userUid);
        }

        function closeEducationModal() {
            $('#educationModal').addClass('hidden');
            $('#currentUserUid').val('');
            $('#educationEntriesList').empty();
            // Reload users table to update count
            reloadUsers();
        }

        function loadEducationEntries(userUid) {
            $.get(`${BASE_URL}/index.php/admin/education/get/${userUid}`, function(response) {
                if (response.success) {
                    const user = response.user;
                    const displayName = user.thai_name || user.name || user.email;
                    $('#modalUserName').text(`${displayName} (${user.email})`);

                    currentEducationData = response.entries || [];
                    renderEducationEntries();
                } else {
                    Swal.fire('Error', response.message || 'ไม่สามารถโหลดข้อมูลได้', 'error');
                }
            }).fail(function() {
                Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            });
        }

        function renderEducationEntries() {
            const container = $('#educationEntriesList');
            container.empty();

            if (currentEducationData.length === 0) {
                container.html(`
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <p>ยังไม่มีประวัติการศึกษา</p>
                    </div>
                `);
                return;
            }

            currentEducationData.forEach((entry, index) => {
                const dateRange = formatDateRange(entry.start_date, entry.end_date, entry.is_current);
                const card = `
                    <div class="education-card bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900">${escapeHtml(entry.title)}</h4>
                                ${entry.organization ? `<p class="text-sm text-gray-600 mt-1">${escapeHtml(entry.organization)}</p>` : ''}
                                ${entry.location ? `<p class="text-xs text-gray-500">${escapeHtml(entry.location)}</p>` : ''}
                                <p class="text-xs text-gray-400 mt-2">${dateRange}</p>
                                ${entry.description ? `<p class="text-sm text-gray-600 mt-2 border-t border-gray-200 pt-2">${escapeHtml(entry.description)}</p>` : ''}
                            </div>
                            <div class="flex space-x-2 ml-4">
                                <button onclick="editEntry(${entry.id})"
                                    class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors" title="แก้ไข">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button onclick="deleteEntry(${entry.id})"
                                    class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors" title="ลบ">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                container.append(card);
            });
        }

        function formatDateRange(startDate, endDate, isCurrent) {
            const formatYear = (dateStr) => {
                if (!dateStr) return '';
                const date = new Date(dateStr);
                return date.getFullYear() + 543; // Convert to Buddhist year
            };

            const start = formatYear(startDate);
            const end = isCurrent == 1 ? 'ปัจจุบัน' : formatYear(endDate);

            if (start && end) {
                return `${start} - ${end}`;
            } else if (start) {
                return `ตั้งแต่ ${start}`;
            } else if (end) {
                return `จนถึง ${end}`;
            }
            return 'ไม่ระบุช่วงเวลา';
        }

        function showEntryForm(entryId = null) {
            const entry = entryId ? currentEducationData.find(e => e.id == entryId) : null;

            if (entry) {
                $('#entryFormTitle').text('Edit Education');
                $('#entryFormSubtitle').text('แก้ไขประวัติการศึกษา');
                $('#entryId').val(entry.id);
                $('#entryTitle').val(entry.title || '');
                $('#entryOrganization').val(entry.organization || '');
                $('#entryLocation').val(entry.location || '');
                $('#entryStartDate').val(entry.start_date || '');
                $('#entryEndDate').val(entry.end_date || '');
                $('#entryIsCurrent').prop('checked', entry.is_current == 1);
                $('#entryDescription').val(entry.description || '');
            } else {
                $('#entryFormTitle').text('Add Education');
                $('#entryFormSubtitle').text('เพิ่มประวัติการศึกษา');
                $('#entryForm')[0].reset();
                $('#entryId').val('');
            }

            $('#entryUserUid').val($('#currentUserUid').val());
            $('#entryFormModal').removeClass('hidden');
        }

        function closeEntryForm() {
            $('#entryFormModal').addClass('hidden');
            $('#entryForm')[0].reset();
        }

        function editEntry(entryId) {
            showEntryForm(entryId);
        }

        function saveEntry() {
            const formData = {
                entry_id: $('#entryId').val() || null,
                user_uid: $('#entryUserUid').val(),
                title: $('#entryTitle').val(),
                organization: $('#entryOrganization').val(),
                location: $('#entryLocation').val(),
                start_date: $('#entryStartDate').val(),
                end_date: $('#entryEndDate').val(),
                is_current: $('#entryIsCurrent').is(':checked') ? 1 : 0,
                description: $('#entryDescription').val()
            };

            $.ajax({
                url: `${BASE_URL}/index.php/admin/education/saveEntry`,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: response.message || 'บันทึกข้อมูลสำเร็จ',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        closeEntryForm();
                        loadEducationEntries($('#currentUserUid').val());
                    } else {
                        Swal.fire('Error', response.message || 'เกิดข้อผิดพลาด', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
                }
            });
        }

        function deleteEntry(entryId) {
            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: 'คุณต้องการลบประวัติการศึกษานี้หรือไม่?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'ลบ',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(`${BASE_URL}/index.php/admin/education/deleteEntry/${entryId}`, function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'ลบสำเร็จ',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            loadEducationEntries($('#currentUserUid').val());
                        } else {
                            Swal.fire('Error', response.message || 'ไม่สามารถลบได้', 'error');
                        }
                    }).fail(function() {
                        Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
                    });
                }
            });
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ============== PDF Report Functions ==============
        async function generatePDFReport() {
            const facultyId = $('#facultyFilter').val();
            const facultyName = $('#facultyFilter option:selected').text() || 'ทุกคณะ';

            Swal.fire({
                title: 'กำลังสร้างรายงาน PDF...',
                html: 'กรุณารอสักครู่',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const response = await $.get(`${BASE_URL}/index.php/admin/education/report-data`, { faculty_id: facultyId });

                if (!response.success || !response.data || response.data.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'ไม่พบข้อมูล',
                        text: 'ไม่มีข้อมูลประวัติการศึกษาในคณะที่เลือก'
                    });
                    return;
                }

                const docDefinition = createPDFDocument(response.data, facultyName);
                pdfMake.createPdf(docDefinition).open();
                Swal.close();

            } catch (error) {
                console.error('PDF generation error:', error);
                Swal.fire('Error', 'เกิดข้อผิดพลาดในการสร้างรายงาน', 'error');
            }
        }

        function createPDFDocument(data, facultyName) {
            const content = [];
            const currentDate = new Date();
            const thaiMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                               'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
            const formattedDate = `${currentDate.getDate()} ${thaiMonths[currentDate.getMonth()]} ${currentDate.getFullYear() + 543}`;

            // Title
            content.push({
                text: 'รายงานประวัติการศึกษาของบุคลากร',
                style: 'title',
                alignment: 'center',
                margin: [0, 0, 0, 5]
            });

            content.push({
                text: facultyName === 'ทุกคณะ' ? 'ทุกคณะ' : facultyName,
                style: 'subtitle',
                alignment: 'center',
                margin: [0, 0, 0, 5]
            });

            content.push({
                text: `ข้อมูล ณ วันที่ ${formattedDate}`,
                style: 'date',
                alignment: 'center',
                margin: [0, 0, 0, 20]
            });

            // Faculty sections
            data.forEach((faculty, fIndex) => {
                // Faculty header
                content.push({
                    text: faculty.faculty_name,
                    style: 'facultyHeader',
                    margin: [0, fIndex > 0 ? 15 : 0, 0, 10]
                });

                // Users table
                const tableBody = [
                    [
                        { text: 'ลำดับ', style: 'tableHeader', alignment: 'center' },
                        { text: 'ชื่อ-นามสกุล', style: 'tableHeader' },
                        { text: 'ประวัติการศึกษา', style: 'tableHeader' }
                    ]
                ];

                faculty.users.forEach((user, uIndex) => {
                    const displayName = user.thai_name || user.eng_name || user.email;
                    const title = user.title || '';

                    // Check if user has education
                    if (!user.has_education || !user.education || user.education.length === 0) {
                        tableBody.push([
                            { text: (uIndex + 1).toString(), alignment: 'center' },
                            { text: `${title} ${displayName}`.trim() },
                            { text: 'ยังไม่ได้เพิ่มประวัติการศึกษา', italics: true, color: '#999999' }
                        ]);
                        return;
                    }

                    // Format education entries
                    const educationList = user.education.map(edu => {
                        let eduText = edu.title;
                        if (edu.organization) eduText += `\n${edu.organization}`;
                        if (edu.location) eduText += `, ${edu.location}`;

                        // Format date range
                        const startYear = edu.start_date ? new Date(edu.start_date).getFullYear() + 543 : '';
                        const endYear = edu.is_current == 1 ? 'ปัจจุบัน' : (edu.end_date ? new Date(edu.end_date).getFullYear() + 543 : '');
                        if (startYear || endYear) {
                            eduText += ` (${startYear}${startYear && endYear ? ' - ' : ''}${endYear})`;
                        }

                        return eduText;
                    });

                    tableBody.push([
                        { text: (uIndex + 1).toString(), alignment: 'center' },
                        { text: `${title} ${displayName}`.trim() },
                        {
                            ul: educationList.map(edu => ({ text: edu, margin: [0, 0, 0, 3] }))
                        }
                    ]);
                });

                content.push({
                    table: {
                        headerRows: 1,
                        widths: [30, 120, '*'],
                        body: tableBody
                    },
                    layout: {
                        hLineWidth: function(i, node) { return 0.5; },
                        vLineWidth: function(i, node) { return 0.5; },
                        hLineColor: function(i, node) { return '#CCCCCC'; },
                        vLineColor: function(i, node) { return '#CCCCCC'; },
                        fillColor: function(i, node) {
                            return (i === 0) ? '#E3F2FD' : null;
                        },
                        paddingLeft: function(i, node) { return 8; },
                        paddingRight: function(i, node) { return 8; },
                        paddingTop: function(i, node) { return 6; },
                        paddingBottom: function(i, node) { return 6; }
                    }
                });

                // Summary for this faculty
                content.push({
                    text: `รวม ${faculty.users.length} คน`,
                    style: 'facultySummary',
                    alignment: 'right',
                    margin: [0, 5, 0, 0]
                });
            });

            return {
                pageSize: 'A4',
                pageMargins: [40, 40, 40, 60],
                defaultStyle: {
                    font: 'Sarabun',
                    fontSize: 14,
                    lineHeight: 1.3
                },
                styles: {
                    title: {
                        fontSize: 20,
                        bold: true
                    },
                    subtitle: {
                        fontSize: 16,
                        bold: true,
                        color: '#1565C0'
                    },
                    date: {
                        fontSize: 12,
                        color: '#666666'
                    },
                    facultyHeader: {
                        fontSize: 16,
                        bold: true,
                        color: '#1565C0',
                        decoration: 'underline'
                    },
                    tableHeader: {
                        bold: true,
                        fontSize: 13
                    },
                    facultySummary: {
                        fontSize: 12,
                        italics: true,
                        color: '#666666'
                    }
                },
                footer: function(currentPage, pageCount) {
                    return {
                        columns: [
                            {
                                text: 'รายงานประวัติการศึกษา',
                                alignment: 'left',
                                fontSize: 10,
                                color: '#999999',
                                margin: [40, 20, 0, 0]
                            },
                            {
                                text: `หน้า ${currentPage} / ${pageCount}`,
                                alignment: 'right',
                                fontSize: 10,
                                color: '#999999',
                                margin: [0, 20, 40, 0]
                            }
                        ]
                    };
                },
                content: content
            };
        }
    </script>
</body>

</html>
