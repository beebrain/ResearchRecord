<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างเอกสารสรุปผลงาน</title>
    
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
    
    <!-- Publication Summary PDF Script -->
    <script src="<?= base_url('assets/js/publication-summary-pdf.js') ?>"></script>
    
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
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
                กำลังสร้างเอกสารสรุปผลงาน
            </h1>
            
            <p class="text-gray-600 mb-4" id="status-text">
                กำลังโหลดข้อมูลหลักสูตร...
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
        // Get curriculum ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const CURRICULUM_ID = urlParams.get('curriculum_id');
        const API_ENDPOINTS = {
            curriculumReport: '<?= site_url('admin/publications/curriculum-report') ?>'
        };
        
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
                if (!CURRICULUM_ID) {
                    throw new Error('ไม่พบรหัสหลักสูตร');
                }
                
                console.log('Starting auto PDF generation for curriculum:', CURRICULUM_ID);
                updateStatus('กำลังโหลดข้อมูลหลักสูตร...', 'ขั้นตอนที่ 1/3');
                
                // Fetch curriculum data
                const response = await fetch(`${API_ENDPOINTS.curriculumReport}?curriculum_id=${CURRICULUM_ID}`);
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message || 'ไม่สามารถโหลดข้อมูลได้');
                }
                
                console.log('Curriculum data loaded:', result.data);
                updateStatus('กำลังโหลดฟอนต์ภาษาไทย...', 'ขั้นตอนที่ 2/3');
                
                // Wait a moment for fonts to load
                await new Promise(resolve => setTimeout(resolve, 500));
                
                const fontLoaded = await window.loadThaiFont();
                console.log('Font loaded:', fontLoaded);
                
                updateStatus('กำลังสร้างเอกสาร PDF...', 'ขั้นตอนที่ 3/3');
                
                // Generate PDF using the global function
                if (typeof window.generateCurriculumPDF === 'function') {
                    window.generateCurriculumPDF(result.data, fontLoaded);
                    
                    // PDF should open in new tab, close this window after a delay
                    setTimeout(() => {
                        updateStatus('เอกสารถูกสร้างเรียบร้อยแล้ว', 'เสร็จสิ้น');
                        // Auto close this window
                        setTimeout(() => {
                            window.close();
                        }, 1000);
                    }, 1000);
                } else {
                    throw new Error('ไม่พบฟังก์ชันสร้าง PDF');
                }
                
            } catch (error) {
                console.error('PDF generation error:', error);
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
        
        // Start generation when page loads
        window.addEventListener('load', () => {
            console.log('Page loaded, starting PDF generation...');
            // Wait for all scripts to be ready
            setTimeout(autoGeneratePDF, 500);
        });
    </script>
</body>
</html>
