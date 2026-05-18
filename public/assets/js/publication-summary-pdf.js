/**
 * Publication Summary PDF Generation
 * Uses PDFMake library to generate PDF reports for curriculum publication summaries
 */

// ============================================================================
// PDF GENERATION FUNCTIONS FOR SUMMARY PAGE
// ============================================================================

/**
 * Check if Thai font is available in PDFMake
 * The fonts are pre-loaded via vfs_fonts.js from pdfmake/build/
 * @returns {Promise<boolean>} Whether the font is available
 */
async function loadThaiFont() {
    try {
        // Check if PDFMake is available
        if (typeof pdfMake === 'undefined') {
            console.error('PDFMake is not loaded');
            return false;
        }

        // Check if Thai fonts are in VFS (pre-loaded from vfs_fonts.js)
        if (pdfMake.vfs && pdfMake.vfs['THSarabunNew.ttf']) {
            console.log('Thai fonts (THSarabunNew) found in VFS');

            // Ensure fonts are registered
            if (!pdfMake.fonts || !pdfMake.fonts.Sarabun) {
                pdfMake.fonts = pdfMake.fonts || {};

                // Check which font files are available in VFS and use fallbacks
                const boldFile = pdfMake.vfs['THSarabunNew Bold.ttf'] ? 'THSarabunNew Bold.ttf' : 'THSarabunNew.ttf';
                const italicFile = pdfMake.vfs['THSarabunNew Italic.ttf'] ? 'THSarabunNew Italic.ttf' : 'THSarabunNew.ttf';
                const boldItalicFile = pdfMake.vfs['THSarabunNew BoldItalic.ttf'] ? 'THSarabunNew BoldItalic.ttf' : 'THSarabunNew.ttf';

                pdfMake.fonts.Sarabun = {
                    normal: 'THSarabunNew.ttf',
                    bold: boldFile,
                    italics: italicFile,
                    bolditalics: boldItalicFile
                };
                console.log('Registered Sarabun font family with fallbacks:', pdfMake.fonts.Sarabun);
            }

            console.log('Available fonts:', Object.keys(pdfMake.fonts));
            return true;
        }

        console.warn('Thai fonts not found in VFS');
        return false;
    } catch (error) {
        console.warn('Could not check Thai font availability:', error);
        return false;
    }
}

/**
 * Convert ArrayBuffer to base64 string (handles large files)
 */
function arrayBufferToBase64Chunked(buffer) {
    const bytes = new Uint8Array(buffer);
    const chunkSize = 8192; // Process in chunks to avoid call stack issues
    let binary = '';

    for (let i = 0; i < bytes.length; i += chunkSize) {
        const chunk = bytes.subarray(i, Math.min(i + chunkSize, bytes.length));
        binary += String.fromCharCode.apply(null, chunk);
    }

    return btoa(binary);
}

/**
 * Fallback method for loading Thai fonts
 * @returns {Promise<boolean>} Whether the font was loaded successfully
 */
async function loadThaiFontFallback() {
    try {
        // Ensure clean base URL without trailing slash
        const cleanBaseUrl = BASE_URL.replace(/\/+$/, '');
        const fontPaths = {
            normal: cleanBaseUrl + '/assets/fonts/sarabun-400.ttf',
            bold: cleanBaseUrl + '/assets/fonts/sarabun-700.ttf'
        };
        console.log('Loading fonts from:', fontPaths.normal);

        // Load normal font
        const normalResponse = await fetch(fontPaths.normal);
        if (!normalResponse.ok) {
            throw new Error('Failed to load normal font');
        }

        const normalBuffer = await normalResponse.arrayBuffer();
        const normalBase64 = arrayBufferToBase64Chunked(normalBuffer);
        console.log('Normal font loaded, base64 length:', normalBase64.length);

        // Load bold font
        let boldBase64 = normalBase64; // Fallback to normal if bold fails
        try {
            const boldResponse = await fetch(fontPaths.bold);
            if (boldResponse.ok) {
                const boldBuffer = await boldResponse.arrayBuffer();
                boldBase64 = arrayBufferToBase64Chunked(boldBuffer);
                console.log('Bold font loaded, base64 length:', boldBase64.length);
            }
        } catch (e) {
            console.warn('Bold font not available, using normal font for bold');
        }

        // Register fonts with PDFMake VFS (Virtual File System)
        pdfMake.vfs = pdfMake.vfs || {};
        pdfMake.vfs['Sarabun-Regular.ttf'] = normalBase64;
        pdfMake.vfs['Sarabun-Bold.ttf'] = boldBase64;
        pdfMake.vfs['Sarabun-Italic.ttf'] = normalBase64;
        pdfMake.vfs['Sarabun-BoldItalic.ttf'] = boldBase64;

        // Register font family with PDFMake
        pdfMake.fonts = pdfMake.fonts || {};
        pdfMake.fonts.THSarabunNew = {
            normal: 'Sarabun-Regular.ttf',
            bold: 'Sarabun-Bold.ttf',
            italics: 'Sarabun-Italic.ttf',
            bolditalics: 'Sarabun-BoldItalic.ttf'
        };

        console.log('Thai fonts loaded successfully (fallback method)');
        return true;
    } catch (error) {
        console.warn('Fallback font loading failed:', error);
        return false;
    }
}

/**
 * Generate curriculum report PDF
 * Redirects to dedicated PDF view page for curriculum selection and generation
 */
async function generateCurriculumReport() {
    try {
        // Show curriculum selection modal
        const {
            value: curriculumId
        } = await Swal.fire({
            title: 'เลือกหลักสูตร',
            html: `
                <div class="text-left">
                    <p class="mb-3 text-gray-700">กรุณาเลือกหลักสูตรที่ต้องการสร้างเอกสารสรุปผลงาน</p>
                    <select id="curriculum-select" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">กำลังโหลด...</option>
                    </select>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'สร้างเอกสาร',
            cancelButtonText: 'ยกเลิก',
            didOpen: async () => {
                // Load curriculums
                const select = document.getElementById('curriculum-select');
                try {
                    console.log('Fetching curriculums from:', `${BASE_URL}/index.php/admin/getCurricula`);
                    const response = await fetch(`${BASE_URL}/index.php/admin/getCurricula`);

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();
                    console.log('Curriculum API response:', result);

                    select.innerHTML = '<option value="">-- เลือกหลักสูตร --</option>';

                    if (result.success && result.data && result.data.length > 0) {
                        result.data.forEach(curr => {
                            const option = document.createElement('option');
                            option.value = curr.id;
                            option.textContent = `${curr.name} (${curr.faculty_name || ''})`;
                            select.appendChild(option);
                        });
                        console.log(`Loaded ${result.data.length} curriculums`);
                    } else if (result.success && (!result.data || result.data.length === 0)) {
                        select.innerHTML = '<option value="">ไม่พบหลักสูตร (อาจไม่มีสิทธิ์เข้าถึง)</option>';
                        console.warn('No curriculums returned from API');
                    } else {
                        select.innerHTML = `<option value="">เกิดข้อผิดพลาด: ${result.message || 'Unknown error'}</option>`;
                        console.error('API returned error:', result);
                    }
                } catch (error) {
                    console.error('Error loading curriculums:', error);
                    select.innerHTML = `<option value="">เกิดข้อผิดพลาด: ${error.message}</option>`;
                }
            },
            preConfirm: () => {
                const select = document.getElementById('curriculum-select');
                if (!select.value) {
                    Swal.showValidationMessage('กรุณาเลือกหลักสูตร');
                    return false;
                }
                return select.value;
            }
        });

        if (!curriculumId) return;

        // Redirect to PDF generation view page
        console.log('Redirecting to PDF view page for curriculum:', curriculumId);
        window.open(`${BASE_URL}/index.php/admin/publications/pdf-summary?curriculum_id=${curriculumId}`, '_blank');

    } catch (error) {
        console.error('Curriculum selection error:', error);
        Swal.fire({
            title: 'เกิดข้อผิดพลาด!',
            text: error.message || 'ไม่สามารถแสดงรายการหลักสูตรได้',
            icon: 'error',
            confirmButtonText: 'ตกลง'
        });
    }
}

/**
 * Generate PDF document from curriculum data
 * @param {Object} data - Curriculum data containing curriculum, faculty, users, and years info
 * @param {boolean} useThaiFont - Whether to use Thai font (THSarabunNew)
 */
function generateCurriculumPDF(data, useThaiFont = false) {
    const curriculum = data.curriculum;
    const faculty = data.faculty;
    const users = data.users || [];
    const years = data.years || [];

    // Debug: Log received data
    console.log('PDF Generation Data:');
    console.log('- Curriculum:', curriculum);
    console.log('- Faculty:', faculty);
    console.log('- Users count:', users.length);
    console.log('- Years:', years);
    if (users.length > 0) {
        console.log('- First user:', users[0]);
        console.log('- First user publications:', users[0].publications);
    }

    // Map degree level to Thai
    const degreeMap = {
        'bachelor': 'ปริญญาตรี',
        'master': 'ปริญญาโท',
        'doctoral': 'ปริญญาเอก'
    };
    const degreeText = degreeMap[curriculum.degree_level] || curriculum.degree_level;

    // Current year (B.E.)
    const currentYear = new Date().getFullYear() + 543;

    // Verify if Thai font is actually available before using it
    let actuallyUseThaiFont = false;
    if (useThaiFont) {
        // Double-check that both VFS has the file AND fonts are registered
        const fontInVFS = pdfMake.vfs && pdfMake.vfs['THSarabunNew.ttf'];
        const fontRegistered = pdfMake.fonts && pdfMake.fonts.Sarabun;

        if (fontInVFS && fontRegistered) {
            actuallyUseThaiFont = true;
            console.log('Thai font verified and will be used');
        } else {
            console.warn('Thai font requested but not available (VFS:', !!fontInVFS, ', Registered:', !!fontRegistered, ')');
            console.warn('Falling back to Roboto font');
        }
    }

    // Build PDF document definition
    const docDefinition = {
        pageSize: 'A4',
        pageMargins: [50, 40, 50, 40],
        defaultStyle: {
            font: actuallyUseThaiFont ? 'Sarabun' : 'Roboto',
            fontSize: 14,
            lineHeight: 1.1
        },
        content: [
            // Header box at top right
            {
                columns: [
                    { width: '*', text: '' },
                    {
                        width: 'auto',
                        table: {
                            body: [
                                [{
                                    text: 'เอกสารแนบหมายเลข ๑.๑',
                                    fontSize: 14,
                                    bold: true,
                                    alignment: 'center',
                                    margin: [8, 3, 8, 3]
                                }]
                            ]
                        },
                        layout: {
                            hLineWidth: function () { return 1; },
                            vLineWidth: function () { return 1; },
                            hLineColor: function () { return '#cc0000'; },
                            vLineColor: function () { return '#cc0000'; }
                        }
                    }
                ],
                margin: [0, 0, 0, 5]
            },

            // Subtitle
            {
                text: '(ระดับหลักสูตร)',
                alignment: 'right',
                fontSize: 12,
                margin: [0, 0, 0, 8]
            },

            // Main Title
            {
                text: 'แบบเสนอขอเปิดรับนักศึกษาใหม่ ประจำปีการศึกษา ..........................................',
                style: 'header',
                alignment: 'center',
                margin: [0, 0, 0, 5]
            },

            // Faculty Name
            {
                text: `คณะ ${faculty.name || '..................................................'}`,
                alignment: 'center',
                fontSize: 10,
                margin: [0, 0, 0, 10]
            },

            // Section 1: Curriculum Information
            {
                text: [
                    { text: '๑. ชื่อหลักสูตรสาขาวิชา ', style: 'sectionTitle' },
                    { text: curriculum.name || '', fontSize: 10 },
                    { text: ` ฉบับปี พ.ศ. ${curriculum.version_year || '............'}`, fontSize: 10 }
                ],
                margin: [0, 3, 0, 3]
            },

            // Section 2: Approval Status
            {
                text: '๒. หลักสูตรได้รับการรับทราบจากสำนักปลัดกระทรวงอุดมศึกษา วิทยาศาสตร์ วิจัย และนวัตกรรม',
                style: 'sectionTitle',
                margin: [0, 5, 0, 2]
            },
            {
                text: '     เมื่อวันที่ .......................................................................',
                fontSize: 10,
                margin: [0, 0, 0, 1]
            },
            {
                text: '     กรณีหลักสูตรอยู่ในระหว่างการพัฒนาหรือปรับปรุง สภามหาวิทยาลัยเห็นชอบแล้วเมื่อวันที่ .......................................',
                fontSize: 10,
                margin: [0, 0, 0, 5]
            },

            // Section 3: Quality Assessment
            {
                text: '๓. ผลการประเมินคุณภาพการศึกษาระดับหลักสูตร ๒ ปี ย้อนหลัง',
                style: 'sectionTitle',
                margin: [0, 5, 0, 2]
            },
            {
                text: `     ปีการศึกษา ${currentYear - 2} ผลการประเมินอยู่ในเกณฑ์ ............................`,
                fontSize: 14,
                margin: [0, 0, 0, 1]
            },
            {
                text: `     ปีการศึกษา ${currentYear - 1} ผลการประเมินอยู่ในเกณฑ์ ............................`,
                fontSize: 14,
                margin: [0, 0, 0, 5]
            },

            // Section 4: Faculty Information
            {
                text: '๔. ข้อมูลอาจารย์ผู้รับผิดชอบหลักสูตร',
                style: 'sectionTitle',
                margin: [0, 5, 0, 5]
            },

            // Table: Faculty members with publications by year
            {
                table: {
                    headerRows: 2,
                    widths: [20, 45, '*', ...years.map(() => 38), 40],
                    body: buildFacultyTableWithHeader(users, years, currentYear)
                },
                layout: {
                    hLineWidth: function () { return 0.5; },
                    vLineWidth: function () { return 0.5; },
                    hLineColor: function () { return 'black'; },
                    vLineColor: function () { return 'black'; }
                },
                margin: [0, 0, 0, 8]
            },

            // Sub-section 1: Faculty Status
            {
                text: '     (๑) การครบอยู่ของอาจารย์ตรงอยู่ในหลักสูตร',
                fontSize: 14,
                margin: [0, 3, 0, 1]
            },
            {
                columns: [
                    { width: 40, text: '' },
                    { width: 'auto', text: 'O ครบ', fontSize: 14 },
                    { width: 20, text: '' },
                    { width: '*', text: 'O ไม่ครบ ลำดับที่ .......... เนื่องจาก .....................................................................', fontSize: 14 }
                ],
                margin: [0, 0, 0, 3]
            },

            // Sub-section 2: Retired Faculty
            {
                text: '     (๒) อาจารย์ที่เกษียณอายุราชการ',
                fontSize: 14,
                margin: [0, 3, 0, 1]
            },
            {
                columns: [
                    { width: 60, text: '' },
                    { width: '*', text: 'ในปี พ.ศ. ................  จำนวน................คน', fontSize: 14 }
                ],
                margin: [0, 0, 0, 0]
            },
            {
                columns: [
                    { width: 60, text: '' },
                    { width: '*', text: 'ในปี พ.ศ. ................  จำนวน................คน', fontSize: 14 }
                ],
                margin: [0, 0, 0, 0]
            },
            {
                columns: [
                    { width: 60, text: '' },
                    { width: '*', text: 'ในปี พ.ศ. ................  จำนวน................คน', fontSize: 14 }
                ],
                margin: [0, 0, 0, 3]
            },

            // Sub-section 3: Faculty on Study Leave
            {
                text: '     (๓) อาจารย์ศึกษาต่อ',
                fontSize: 14,
                margin: [0, 3, 0, 1]
            },
            {
                columns: [
                    { width: 60, text: '' },
                    { width: '*', text: 'ในปี พ.ศ. ................  จำนวน................คน', fontSize: 14 }
                ],
                margin: [0, 0, 0, 0]
            },
            {
                columns: [
                    { width: 60, text: '' },
                    { width: '*', text: 'ในปี พ.ศ. ................  จำนวน................คน', fontSize: 14 }
                ],
                margin: [0, 0, 0, 0]
            },
            {
                columns: [
                    { width: 60, text: '' },
                    { width: '*', text: 'ในปี พ.ศ. ................  จำนวน................คน', fontSize: 14 }
                ],
                margin: [0, 0, 0, 8]
            },

            // Section 5: Publication Details for Each Faculty Member
            {
                text: '๕. รายละเอียดผลงานของอาจารย์ผู้รับผิดชอบหลักสูตร',
                style: 'sectionTitle',
                margin: [0, 10, 0, 5],
                pageBreak: 'before'
            },

            // Build publication details for each user
            ...buildPublicationDetails(users, years)
        ],
        styles: {
            header: {
                fontSize: 14,
                bold: true,
                alignment: 'center',
                font: 'Sarabun'
            },
            subheader: {
                fontSize: 10,
                alignment: 'center',
                font: 'Sarabun'
            },
            sectionTitle: {
                fontSize: 10,
                bold: false,
                font: 'Sarabun'
            },
            subsectionTitle: {
                fontSize: 10,
                bold: false,
                font: 'Sarabun'
            },
            tableHeader: {
                bold: true,
                fontSize: 8,
                alignment: 'center',
                font: 'Sarabun'
            },
            normal: {
                fontSize: 12,
                font: 'Sarabun'
            }
        }
    };

    // Verify font is available before generating
    if (useThaiFont) {
        const fontAvailable = pdfMake.vfs && pdfMake.vfs['THSarabunNew.ttf'] &&
            pdfMake.fonts && pdfMake.fonts.Sarabun;
        if (!fontAvailable) {
            console.warn('Thai font not available in VFS, falling back to Roboto');
            docDefinition.defaultStyle.font = 'Roboto';
        } else {
            console.log('Using Thai font (Sarabun) for PDF generation');
        }
    }

    // Generate and download PDF (avoids popup blocker)
    try {
        console.log('Creating PDF with font:', docDefinition.defaultStyle.font);
        console.log('Available fonts:', Object.keys(pdfMake.fonts || {}));

        const fileName = `curriculum_report_${data.curriculum.name}_${new Date().getTime()}.pdf`;
        pdfMake.createPdf(docDefinition).download(fileName);

        console.log('PDF download initiated:', fileName);
    } catch (error) {
        console.error('PDF generation error:', error);
        throw error; // Re-throw to be caught by the caller
    }
}

// Export function for use in PDF view page
window.generateCurriculumPDF = generateCurriculumPDF;
// Also export as generatePDFDocument for compatibility with manage page
window.generatePDFDocument = generateCurriculumPDF;

/**
 * Build faculty table with publications by year
 * @param {Array} users - List of users with publication data
 * @param {Array} years - List of years to display
 * @returns {Array} Table body array for PDFMake
 */
/**
 * Build faculty table with proper header structure (2-row header)
 * @param {Array} users - List of users with publication data
 * @param {Array} years - List of years to display
 * @param {number} currentYear - Current year in B.E.
 * @returns {Array} Table body array for PDFMake
 */
function buildFacultyTableWithHeader(users, years, currentYear) {
    console.log('Building faculty table with:');
    console.log('- Users:', users.length);
    console.log('- Years:', years);

    // Count total publications across all users
    let totalPubCount = 0;
    users.forEach(u => {
        totalPubCount += (u.publication_count || 0);
        console.log(`User: ${u.name}, Publications: ${u.publication_count || 0}`);
    });
    console.log('Total publications:', totalPubCount);

    // First header row with merged cells
    const headerRow1 = [
        { text: 'ที่', rowSpan: 2, alignment: 'center', fontSize: 11, bold: true },
        { text: 'ตำแหน่ง', rowSpan: 2, alignment: 'center', fontSize: 11, bold: true },
        { text: 'ชื่อ-นามสกุล', rowSpan: 2, alignment: 'center', fontSize: 11, bold: true },
        { text: 'มีผลงานทางวิชาการที่เผยแพร่แล้วอยู่ในปี', colSpan: years.length, alignment: 'center', fontSize: 10, bold: true },
        ...Array(years.length - 1).fill({}),
        { text: 'ปี พ.ศ.\nปัจจุบัน', rowSpan: 2, alignment: 'center', fontSize: 10, bold: true }
    ];

    // Second header row with year columns
    const headerRow2 = [
        {}, // rowSpan placeholder
        {}, // rowSpan placeholder
        {}, // rowSpan placeholder
        ...years.map(year => ({
            text: year.toString(),
            alignment: 'center',
            fontSize: 10,
            bold: true
        })),
        {} // rowSpan placeholder
    ];

    // Data rows
    const dataRows = users.map((user, index) => {
        const pubsByYear = user.publications_by_year || {};

        // Log publications for debugging
        console.log(`User ${index + 1}: ${user.name}`);
        console.log(`  - Total publications: ${user.publication_count || 0}`);
        years.forEach(year => {
            const pubs = pubsByYear[year] || [];
            if (pubs.length > 0) {
                console.log(`  - Year ${year}: ${pubs.length} publications`);
            }
        });

        return [
            { text: (index + 1).toString(), alignment: 'center', fontSize: 11 },
            { text: user.position || '', alignment: 'center', fontSize: 10 },
            { text: user.name || '', alignment: 'left', fontSize: 11 },
            ...years.map(year => {
                const pubs = pubsByYear[year] || [];
                // Show checkmark if has publications in that year
                const hasWork = pubs.length > 0 ? '✓' : '';
                return { text: hasWork, alignment: 'center', fontSize: 12 };
            }),
            { text: currentYear.toString(), alignment: 'center', fontSize: 10 }
        ];
    });

    // Add empty rows to fill minimum 5 rows
    const minRows = 5;
    while (dataRows.length < minRows) {
        const rowNum = dataRows.length + 1;
        dataRows.push([
            { text: rowNum.toString(), alignment: 'center', fontSize: 11 },
            { text: '', alignment: 'center', fontSize: 10 },
            { text: '', alignment: 'left', fontSize: 11 },
            ...years.map(() => ({ text: '', alignment: 'center', fontSize: 12 })),
            { text: '', alignment: 'center', fontSize: 10 }
        ]);
    }

    return [headerRow1, headerRow2, ...dataRows];
}

function buildFacultyTable(users, years) {
    console.log('Building faculty table with:');
    console.log('- Users:', users.length);
    console.log('- Years:', years);

    const header = [{
        text: 'ที่',
        style: 'tableHeader',
        alignment: 'center'
    },
    {
        text: 'ตำแหน่ง',
        style: 'tableHeader',
        alignment: 'center'
    },
    {
        text: 'ชื่อ-นามสกุล',
        style: 'tableHeader',
        alignment: 'center'
    },
    ...years.map(year => ({
        text: `มีผลงานทางวิชาการที่เผยแพร่แล้วอยู่ในปี\n${year}`,
        style: 'tableHeader',
        alignment: 'center'
    })),
    {
        text: 'ปี พ.ศ.\nปัจจุบัน',
        style: 'tableHeader',
        alignment: 'center'
    }
    ];

    const rows = users.map((user, index) => {
        console.log(`User ${index + 1}: ${user.name}, publications_by_year:`, user.publications_by_year);

        const row = [{
            text: (index + 1).toString(),
            alignment: 'center'
        },
        {
            text: user.position || '',
            alignment: 'center'
        },
        {
            text: user.name || '',
            alignment: 'left'
        },
        ...years.map(year => {
            const pubsByYear = user.publications_by_year || {};
            const pubs = pubsByYear[year] || [];
            const hasWork = pubs.length > 0 ? '✓' : '';
            if (pubs.length > 0) {
                console.log(`  Year ${year}: ${pubs.length} publications`);
            }
            return {
                text: hasWork,
                alignment: 'center'
            };
        }),
        {
            text: (new Date().getFullYear() + 543).toString(),
            alignment: 'center'
        }
        ];
        return row;
    });

    // Add empty rows if less than 6
    while (rows.length < 6) {
        rows.push([{
            text: (rows.length + 1).toString(),
            alignment: 'center'
        },
        {
            text: '',
            alignment: 'center'
        },
        {
            text: '',
            alignment: 'left'
        },
        ...years.map(() => ({
            text: '',
            alignment: 'center'
        })),
        {
            text: '',
            alignment: 'center'
        }
        ]);
    }

    return [header, ...rows];
}

/**
 * Build academic work development plan table
 * @param {Array} users - List of users with publication data
 * @returns {Array} Table body array for PDFMake
 */
function buildAcademicWorkTable(users) {
    console.log('Building academic work table:');
    let totalPubs = 0;
    users.forEach(u => {
        if (u.publications) totalPubs += u.publications.length;
    });
    console.log('- Total publications:', totalPubs);

    const header = [{
        text: 'ลำดับที่',
        style: 'tableHeader',
        alignment: 'center'
    },
    {
        text: 'ชื่อ-สกุล',
        style: 'tableHeader',
        alignment: 'center'
    },
    {
        text: 'ประเภทผลงาน',
        style: 'tableHeader',
        alignment: 'center'
    },
    {
        text: 'ชื่อผลงาน',
        style: 'tableHeader',
        alignment: 'center'
    },
    {
        text: 'ปีที่แล้วเสร็จ',
        style: 'tableHeader',
        alignment: 'center'
    }
    ];

    const rows = [];
    let seq = 1;

    users.forEach(user => {
        if (user.publications && user.publications.length > 0) {
            user.publications.forEach(pub => {
                rows.push([{
                    text: seq.toString(),
                    alignment: 'center'
                },
                {
                    text: user.name || '',
                    alignment: 'left'
                },
                {
                    text: pub.publication_type || '',
                    alignment: 'center'
                },
                {
                    text: pub.title || '',
                    alignment: 'left',
                    fontSize: 11
                },
                {
                    text: pub.publication_year ? pub.publication_year.toString() : '',
                    alignment: 'center'
                }
                ]);
                seq++;
            });
        }
    });

    // Add empty rows if needed
    while (rows.length < 15) {
        rows.push([{
            text: seq.toString(),
            alignment: 'center'
        },
        {
            text: '',
            alignment: 'left'
        },
        {
            text: '',
            alignment: 'center'
        },
        {
            text: '',
            alignment: 'left'
        },
        {
            text: '',
            alignment: 'center'
        }
        ]);
        seq++;
    }

    return [header, ...rows];
}

/**
 * Format publication in APA style
 * @param {Object} pub - Publication object
 * @returns {string} APA formatted citation
 */
function formatPublicationAPA(pub) {
    let citation = '';

    // Publication type mapping
    const typeMap = {
        'งานวิจัย': 'journal',
        'บทความวิชาการ': 'proceedings',
        'หนังสือ': 'book'
    };

    const pubType = pub.publication_type || 'journal';

    // Title (italicized for journals/books)
    if (pub.title) {
        citation += pub.title;
    }

    // Source/Journal name
    if (pub.source) {
        citation += '. ' + pub.source;
    }

    // Volume and Issue
    if (pub.volume) {
        citation += `, ${pub.volume}`;
        if (pub.issue) {
            citation += `(${pub.issue})`;
        }
    }

    // Pages
    if (pub.pages) {
        citation += `, ${pub.pages}`;
    }

    // Year
    if (pub.publication_year) {
        citation += `. (${pub.publication_year})`;
    }

    return citation;
}

/**
 * Build detailed publication list for each faculty member
 * @param {Array} users - List of users with publications
 * @param {Array} years - List of years
 * @returns {Array} Content array for PDF
 */
function buildPublicationDetails(users) {
    const content = [];

    users.forEach((user, userIndex) => {
        // Skip if user has no publications
        if (!user.publications || user.publications.length === 0) {
            return;
        }

        // User name header
        content.push({
            text: `${userIndex + 1}. ${user.name || 'ไม่ระบุชื่อ'}`,
            fontSize: 12,
            bold: true,
            margin: [0, userIndex > 0 ? 10 : 0, 0, 3]
        });

        // Position
        if (user.position) {
            content.push({
                text: `ตำแหน่ง: ${user.position}`,
                fontSize: 10,
                margin: [15, 0, 0, 5]
            });
        }

        // Group publications by year
        const pubsByYear = {};
        user.publications.forEach(pub => {
            const year = pub.publication_year || 'ไม่ระบุปี';
            if (!pubsByYear[year]) {
                pubsByYear[year] = [];
            }
            pubsByYear[year].push(pub);
        });

        // Sort years in descending order
        const sortedYears = Object.keys(pubsByYear).sort((a, b) => b - a);

        // Display publications by year
        sortedYears.forEach(year => {
            content.push({
                text: `ปี พ.ศ. ${year}`,
                fontSize: 10,
                bold: true,
                margin: [15, 3, 0, 2]
            });

            pubsByYear[year].forEach((pub, pubIndex) => {
                const apaCitation = formatPublicationAPA(pub);

                content.push({
                    text: [
                        { text: `   ${pubIndex + 1}. `, fontSize: 10 },
                        { text: apaCitation, fontSize: 10 }
                    ],
                    margin: [15, 1, 0, 1],
                    alignment: 'left'
                });
            });
        });
    });

    // If no users have publications, show message
    if (content.length === 0) {
        content.push({
            text: 'ไม่พบข้อมูลผลงานของอาจารย์ผู้รับผิดชอบหลักสูตร',
            fontSize: 10,
            italics: true,
            margin: [0, 5, 0, 0]
        });
    }

    return content;
}

// Export functions to global scope for external access
window.generateCurriculumReport = generateCurriculumReport;
window.loadThaiFont = loadThaiFont;

// Pre-load Thai fonts when page loads for faster PDF generation
document.addEventListener('DOMContentLoaded', function () {
    // Wait a bit for pdfMake to be fully loaded
    setTimeout(async function () {
        if (typeof pdfMake !== 'undefined') {
            console.log('Pre-loading Thai fonts for PDF generation...');
            try {
                const loaded = await loadThaiFont();
                if (loaded) {
                    console.log('Thai fonts pre-loaded successfully!');
                } else {
                    console.warn('Thai fonts pre-loading failed, will try again when generating PDF');
                }
            } catch (e) {
                console.warn('Font pre-loading error:', e);
            }
        }
    }, 500);
});

