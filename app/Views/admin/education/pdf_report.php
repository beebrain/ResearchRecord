<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'รายงานประวัติการศึกษา' ?></title>
    <link rel="stylesheet" href="<?= base_url('/public/assets/css/tailwind.min.css') ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('public/assets/css/admin-common.css') ?>">

    <!-- pdfMake -->
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
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .preview-card {
            transition: all 0.2s ease;
        }
        .preview-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body class="min-h-full">
    <div class="min-h-full bg-gray-50">
        <!-- Top Navigation Bar -->
        <?php
        $pageTitle = $title ?? 'รายงานประวัติการศึกษา';
        $pageSubtitle = 'Education Report PDF Generator';
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
                                <h2 class="text-xl font-bold text-gray-900">สร้างรายงานประวัติการศึกษา</h2>
                                <p class="text-sm text-gray-600 mt-1">เลือกคณะและสร้างรายงาน PDF ประวัติการศึกษาของบุคลากร</p>
                            </div>
                            <a href="<?= base_url('index.php/admin/education') ?>"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                                ← กลับไปหน้าจัดการ
                            </a>
                        </div>
                    </div>

                    <div class="p-6">
                        <!-- Filter Section -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <div class="flex flex-wrap items-end gap-4">
                                <div class="flex-1 min-w-[200px]">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">เลือกคณะ</label>
                                    <select id="facultyFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">ทุกคณะ</option>
                                        <?php foreach ($faculties as $faculty): ?>
                                            <option value="<?= $faculty['id'] ?>"><?= esc($faculty['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <button onclick="loadPreview()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        โหลดข้อมูล
                                    </button>
                                </div>
                                <div>
                                    <button onclick="generatePDF()" id="btnGeneratePDF" disabled
                                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                        สร้าง PDF
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics -->
                        <div id="statisticsSection" class="hidden mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-blue-50 rounded-lg p-4 text-center">
                                    <div class="text-3xl font-bold text-blue-600" id="statTotalUsers">0</div>
                                    <div class="text-sm text-gray-600">บุคลากรทั้งหมด</div>
                                </div>
                                <div class="bg-green-50 rounded-lg p-4 text-center">
                                    <div class="text-3xl font-bold text-green-600" id="statWithEducation">0</div>
                                    <div class="text-sm text-gray-600">มีประวัติการศึกษา</div>
                                </div>
                                <div class="bg-purple-50 rounded-lg p-4 text-center">
                                    <div class="text-3xl font-bold text-purple-600" id="statFaculties">0</div>
                                    <div class="text-sm text-gray-600">คณะ</div>
                                </div>
                            </div>
                        </div>

                        <!-- Loading -->
                        <div id="loadingSection" class="hidden text-center py-12">
                            <div class="loader mx-auto mb-4"></div>
                            <p class="text-gray-600">กำลังโหลดข้อมูล...</p>
                        </div>

                        <!-- Preview Section -->
                        <div id="previewSection" class="hidden">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">ตัวอย่างข้อมูลที่จะอยู่ในรายงาน</h3>
                            <div id="previewContent" class="space-y-6">
                                <!-- Preview will be rendered here -->
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div id="emptySection" class="text-center py-12">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-gray-500">เลือกคณะและกด "โหลดข้อมูล" เพื่อดูตัวอย่างรายงาน</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        const BASE_URL = '<?= rtrim(base_url(), '/') ?>';
        let reportData = null;

        function loadPreview() {
            const facultyId = $('#facultyFilter').val();

            $('#emptySection').addClass('hidden');
            $('#previewSection').addClass('hidden');
            $('#statisticsSection').addClass('hidden');
            $('#loadingSection').removeClass('hidden');
            $('#btnGeneratePDF').prop('disabled', true);

            $.get(`${BASE_URL}/index.php/admin/education/report-data`, { faculty_id: facultyId })
                .done(function(response) {
                    $('#loadingSection').addClass('hidden');

                    if (response.success && response.data && response.data.length > 0) {
                        reportData = response;

                        // Update statistics
                        $('#statTotalUsers').text(response.statistics.total_users);
                        $('#statWithEducation').text(response.statistics.users_with_education);
                        $('#statFaculties').text(response.statistics.total_faculties);
                        $('#statisticsSection').removeClass('hidden');

                        // Render preview
                        renderPreview(response.data);
                        $('#previewSection').removeClass('hidden');
                        $('#btnGeneratePDF').prop('disabled', false);
                    } else {
                        $('#emptySection').removeClass('hidden');
                        $('#emptySection').html(`
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <p class="text-gray-500">ไม่พบข้อมูลประวัติการศึกษาในคณะที่เลือก</p>
                        `);
                    }
                })
                .fail(function() {
                    $('#loadingSection').addClass('hidden');
                    Swal.fire('Error', 'เกิดข้อผิดพลาดในการโหลดข้อมูล', 'error');
                });
        }

        function renderPreview(data) {
            let html = '';

            data.forEach(faculty => {
                html += `
                    <div class="preview-card bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-blue-600 text-white px-4 py-3">
                            <h4 class="font-semibold">${escapeHtml(faculty.faculty_name)}</h4>
                            <p class="text-sm text-blue-100">${faculty.users.length} คน</p>
                        </div>
                        <div class="divide-y divide-gray-100">
                `;

                faculty.users.slice(0, 5).forEach(user => {
                    const displayName = user.thai_name || user.eng_name || user.email;
                    const title = user.title || '';

                    html += `
                        <div class="px-4 py-3">
                            <div class="font-medium text-gray-900">${escapeHtml(title)} ${escapeHtml(displayName)}</div>
                            <div class="text-sm text-gray-500 mt-1">
                    `;

                    user.education.slice(0, 2).forEach(edu => {
                        html += `<div>• ${escapeHtml(edu.title)} ${edu.organization ? '- ' + escapeHtml(edu.organization) : ''}</div>`;
                    });

                    if (user.education.length > 2) {
                        html += `<div class="text-gray-400">และอีก ${user.education.length - 2} รายการ</div>`;
                    }

                    html += `
                            </div>
                        </div>
                    `;
                });

                if (faculty.users.length > 5) {
                    html += `
                        <div class="px-4 py-3 bg-gray-50 text-center text-sm text-gray-500">
                            และอีก ${faculty.users.length - 5} คน...
                        </div>
                    `;
                }

                html += `
                        </div>
                    </div>
                `;
            });

            $('#previewContent').html(html);
        }

        function generatePDF() {
            if (!reportData || !reportData.data || reportData.data.length === 0) {
                Swal.fire('Error', 'ไม่มีข้อมูลสำหรับสร้างรายงาน', 'error');
                return;
            }

            const facultyName = $('#facultyFilter option:selected').text();
            const docDefinition = createPDFDocument(reportData.data, facultyName);

            Swal.fire({
                title: 'กำลังสร้าง PDF...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                pdfMake.createPdf(docDefinition).open();
                Swal.close();
            } catch (error) {
                console.error('PDF generation error:', error);
                Swal.fire('Error', 'เกิดข้อผิดพลาดในการสร้าง PDF', 'error');
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

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Load preview on page load if faculty is pre-selected
        $(document).ready(function() {
            // Auto-load all data on page load
            // loadPreview();
        });
    </script>
</body>

</html>
