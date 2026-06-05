<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างเอกสารแบบฟอร์มขอเปิดรับนักศึกษาใหม่</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- pdfMake Local Files (with Thai fonts in vfs_fonts.js) -->
    <script src="<?= pdfmake_url('build/pdfmake.min.js') ?>"></script>
    <script src="<?= pdfmake_url('build/vfs_fonts.js') ?>"></script>

    <!-- Initialize fonts immediately after pdfMake loads -->
    <script>
        // Ensure pdfMake fonts are set up correctly for Thai
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
            console.log('pdfMake fonts initialized:', Object.keys(pdfMake.fonts));
        }
    </script>

    <!-- Admission Form PDF Script (cache-bust ด้วย filemtime เพื่อให้ได้โค้ดล่าสุดเสมอ) -->
    <script src="<?= base_url('assets/js/admission-form-pdf.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/admission-form-pdf.js') ?: time() ?>"></script>

    <style>
        .loader {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3b82f6;
            border-radius: 50%;
            width: 50px;
            height: 50px;
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

<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full text-center">
            <div class="flex justify-center mb-4">
                <div class="loader"></div>
            </div>

            <h1 class="text-2xl font-bold text-gray-800 mb-2">
                กำลังสร้างเอกสารแบบฟอร์มขอเปิดรับนักศึกษาใหม่
            </h1>

            <p class="text-gray-600 mb-4" id="status-text">
                กำลังโหลดข้อมูลแบบฟอร์ม...
            </p>

            <div class="text-sm text-gray-500" id="progress-detail">
                <div class="flex items-center justify-center space-x-2">
                    <span>•</span>
                    <span id="step-text">เตรียมการ</span>
                </div>
            </div>

            <button onclick="window.close()"
                class="mt-6 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors">
                ยกเลิก
            </button>
        </div>
    </div>

    <script>
        const BASE_URL = '<?= rtrim(base_url(), '/') ?>';
    </script>
    <script src="<?= base_url('assets/js/app-routes.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/app-routes.js') ?: time() ?>"></script>
    <script>
        // Get form ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const FORM_ID = urlParams.get('form_id');

        // ส่ง log การสร้าง PDF ไปเก็บที่ server (CI log) เพื่อตรวจสอบ error ย้อนหลัง
        function pdfLog(level, message, context) {
            try {
                console[(level === 'error' || level === 'warning') ? 'error' : 'log']('[PDF]', message, context || '');
            } catch (e) {}
            try {
                const body = JSON.stringify({ level: level, message: String(message), form_id: FORM_ID, context: context || null });
                fetch(appRoute('admin/admission/log-client'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: body,
                    keepalive: true
                }).catch(function () {});
            } catch (e) {}
        }
        window.__pdfLog = pdfLog;

        // จับ error ที่หลุดจาก try/catch (เช่น error ภายใน pdfMake worker)
        window.addEventListener('error', function (e) {
            pdfLog('error', 'window.onerror: ' + (e.message || '') + ' @' + (e.filename || '') + ':' + (e.lineno || ''));
        });
        window.addEventListener('unhandledrejection', function (e) {
            const r = e && e.reason;
            pdfLog('error', 'unhandledrejection: ' + ((r && r.message) || r || 'unknown'));
        });

        // Update status function
        function updateStatus(message, step) {
            document.getElementById('status-text').textContent = message;
            if (step) {
                document.getElementById('step-text').textContent = step;
            }
        }

        // Auto-generate PDF when page loads
        async function autoGeneratePDF() {
            try {
                if (!FORM_ID) {
                    throw new Error('ไม่พบรหัสแบบฟอร์ม');
                }

                console.log('Starting auto PDF generation for form:', FORM_ID);
                pdfLog('info', 'เริ่มสร้าง PDF', { ua: navigator.userAgent });
                updateStatus('กำลังโหลดข้อมูลแบบฟอร์ม...', 'ขั้นตอนที่ 1/3');

                // Fetch form data
                const response = await fetch(appRoute('admin/admission/get/' + FORM_ID), {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    pdfLog('error', 'โหลดข้อมูลแบบฟอร์มไม่สำเร็จ (HTTP ' + response.status + ')');
                    throw new Error('ไม่สามารถโหลดข้อมูลได้');
                }

                const result = await response.json();

                if (!result.success || !result.data) {
                    pdfLog('error', 'ข้อมูลแบบฟอร์มไม่ถูกต้อง: ' + (result.message || 'ไม่พบข้อมูล'));
                    throw new Error(result.message || 'ไม่พบข้อมูลแบบฟอร์ม');
                }

                console.log('Form data loaded:', result.data);
                pdfLog('info', 'โหลดข้อมูลสำเร็จ', {
                    has_major_minor: result.data.has_major_minor,
                    major_count: result.data.major_count,
                    has_majors_detail: !!result.data.majors_detail
                });
                updateStatus('กำลังโหลดฟอนต์ภาษาไทย...', 'ขั้นตอนที่ 2/3');

                // Wait a moment for fonts to load
                await new Promise(resolve => setTimeout(resolve, 500));

                // Load Thai font - check if function exists, otherwise use fallback
                let fontLoaded = false;
                if (typeof window.loadThaiFont === 'function') {
                    fontLoaded = await window.loadThaiFont();
                } else {
                    // Fallback: check if fonts are already available
                    console.warn('loadThaiFont function not found, checking fonts directly...');
                    if (typeof pdfMake !== 'undefined' && pdfMake.vfs && pdfMake.vfs['THSarabunNew.ttf']) {
                        // Ensure fonts are registered
                        if (!pdfMake.fonts || !pdfMake.fonts.Sarabun) {
                            pdfMake.fonts = pdfMake.fonts || {};
                            pdfMake.fonts.Sarabun = {
                                normal: 'THSarabunNew.ttf',
                                bold: pdfMake.vfs['THSarabunNew Bold.ttf'] ? 'THSarabunNew Bold.ttf' : 'THSarabunNew.ttf',
                                italics: pdfMake.vfs['THSarabunNew Italic.ttf'] ? 'THSarabunNew Italic.ttf' : 'THSarabunNew.ttf',
                                bolditalics: pdfMake.vfs['THSarabunNew BoldItalic.ttf'] ? 'THSarabunNew BoldItalic.ttf' : 'THSarabunNew.ttf'
                            };
                        }
                        fontLoaded = true;
                        console.log('Thai fonts found and registered');
                    } else {
                        console.warn('Thai fonts not available, will use default font');
                        fontLoaded = false;
                    }
                }
                console.log('Font loaded:', fontLoaded);
                pdfLog('info', 'โหลดฟอนต์เสร็จ fontLoaded=' + fontLoaded);

                updateStatus('กำลังสร้างเอกสาร PDF...', 'ขั้นตอนที่ 3/3');

                // Generate PDF using the global function
                if (typeof window.generateAdmissionFormPDF === 'function') {
                    // ปิดหน้าต่างนี้เฉพาะหลัง PDF ถูกเรนเดอร์เสร็จจริง (กัน race ตอนฟอนต์ไทยใหญ่/เครื่องช้า)
                    window.__onPdfReady = function (blobUrl, fileName) {
                        pdfLog('info', 'PDF พร้อม/ดาวน์โหลดแล้ว: ' + (fileName || ''));
                        updateStatus('ดาวน์โหลดเอกสารเรียบร้อยแล้ว', 'เสร็จสิ้น');
                        const detail = document.getElementById('progress-detail');
                        if (detail) {
                            const openLink = blobUrl
                                ? `<a href="${blobUrl}" target="_blank" class="text-blue-600 underline">เปิดดู PDF</a> &nbsp;`
                                : '';
                            detail.innerHTML = `
                                <div class="text-green-600 text-sm mt-2">
                                    เอกสาร "${fileName || 'PDF'}" ถูกสร้าง/ดาวน์โหลดแล้ว
                                </div>
                                <div class="mt-3">${openLink}
                                    <button onclick="window.close()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors">ปิดหน้าต่าง</button>
                                </div>`;
                        }
                        // ปิดอัตโนมัติหลังจากแน่ใจว่าไฟล์ถูกส่งแล้ว
                        setTimeout(() => { try { window.close(); } catch (e) {} }, 4000);
                    };
                    window.__onPdfError = function (err) {
                        throw new Error((err && err.message) || 'สร้าง PDF ไม่สำเร็จ');
                    };
                    window.generateAdmissionFormPDF(result.data, fontLoaded);
                } else {
                    throw new Error('ไม่พบฟังก์ชันสร้าง PDF');
                }

            } catch (error) {
                console.error('PDF generation error:', error);
                pdfLog('error', 'PDF generation error: ' + ((error && error.message) || error), {
                    stack: (error && error.stack) ? String(error.stack).substring(0, 800) : null
                });
                document.querySelector('.loader').style.display = 'none';
                updateStatus('เกิดข้อผิดพลาด!', '');
                document.getElementById('progress-detail').innerHTML = `
                    <div class="text-red-600 text-sm mt-2">
                        ${error.message || 'ไม่สามารถสร้างเอกสารได้'}
                    </div>
                    <button onclick="window.history.back()" 
                            class="mt-4 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors">
                        กลับ
                    </button>
                `;
            }
        }

        // Wait for scripts to load before starting PDF generation
        let __waitTries = 0;
        function waitForScripts() {
            __waitTries++;
            // หลังรอ ~10 วินาที (100 ครั้ง) ยังโหลดไม่ครบ → log error เพื่อตรวจสอบ asset
            if (__waitTries > 100) {
                pdfLog('error', 'สคริปต์โหลดไม่ครบใน 10 วินาที: pdfMake=' + (typeof pdfMake) + ' generateAdmissionFormPDF=' + (typeof window.generateAdmissionFormPDF));
                updateStatus('โหลดสคริปต์สร้าง PDF ไม่สำเร็จ', '');
                return;
            }

            // Check if both pdfMake and admission-form-pdf.js are loaded
            if (typeof pdfMake === 'undefined') {
                console.log('Waiting for pdfMake to load...');
                setTimeout(waitForScripts, 100);
                return;
            }

            // Check if generateAdmissionFormPDF is available (indicates admission-form-pdf.js is loaded)
            if (typeof window.generateAdmissionFormPDF === 'undefined') {
                console.log('Waiting for admission-form-pdf.js to load...');
                setTimeout(waitForScripts, 100);
                return;
            }

            console.log('All scripts loaded, starting PDF generation...');
            autoGeneratePDF();
        }

        // Start generation when page loads
        window.addEventListener('load', () => {
            console.log('Page loaded, checking scripts...');
            // Wait a bit for scripts to initialize
            setTimeout(waitForScripts, 300);
        });
    </script>
</body>

</html>