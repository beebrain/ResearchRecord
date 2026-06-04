/**
 * Generate PDF document for Student Admission Form
 * Uses pdfMake library to create PDF matching the official form format
 * 
 * @param {Object} formData - Admission form data from database
 * @param {boolean} useThaiFont - Whether to use Thai font (THSarabunNew)
 */
function generateAdmissionFormPDF(formData, useThaiFont = false) {
    const form = formData;
    
    // Verify if Thai font is available
    let actuallyUseThaiFont = false;
    if (useThaiFont) {
        const fontInVFS = pdfMake.vfs && pdfMake.vfs['THSarabunNew.ttf'];
        const fontRegistered = pdfMake.fonts && pdfMake.fonts.Sarabun;
        
        if (fontInVFS && fontRegistered) {
            actuallyUseThaiFont = true;
            console.log('Thai font verified and will be used');
        } else {
            console.warn('Thai font requested but not available, falling back to Roboto');
        }
    }
    
    const fontFamily = actuallyUseThaiFont ? 'Sarabun' : 'Roboto';
    
    // Helper function to create underlined text field
    function createUnderlineField(value, width = 200) {
        return {
            canvas: [
                {
                    type: 'line',
                    x1: 0,
                    y1: 0,
                    x2: width,
                    y2: 0,
                    lineWidth: 0.5,
                    lineColor: '#000000'
                }
            ],
            relativePosition: { x: 0, y: -2 }
        };
    }
    
    // Helper function to format date (convert to พ.ศ.)
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const year = date.getFullYear() + 543; // Convert to พ.ศ.
        return `${day}/${month}/${year}`;
    }
    
    // Helper function to format date in full Thai format (e.g., 30 พฤศจิกายน พ.ศ. 2568)
    function formatDateFullThai(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const day = date.getDate();
        const monthNames = [
            'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
        ];
        const month = monthNames[date.getMonth()];
        const year = date.getFullYear() + 543; // Convert to พ.ศ.
        return `${day} ${month} พ.ศ. ${year}`;
    }
    
    // Helper function to convert number to Thai numeral
    function toThaiNumeral(num) {
        const thaiNums = ['', '๑', '๒', '๓', '๔', '๕', '๖', '๗', '๘', '๙', '๑๐'];
        if (num <= 10) return thaiNums[num] || num.toString();
        return num.toString();
    }
    
    // Helper function to convert year to Thai numerals (e.g., 2569 -> ๒๕๖๙)
    function toThaiYear(year) {
        if (!year) return '';
        const thaiDigits = ['๐', '๑', '๒', '๓', '๔', '๕', '๖', '๗', '๘', '๙'];
        return year.toString().split('').map(d => thaiDigits[parseInt(d)]).join('');
    }
    
    // Helper function to convert publication type to Thai
    function getPublicationTypeThai(type) {
        if (!type) return '-';
        const typeMap = {
            'journal': 'วารสาร',
            'book': 'หนังสือ',
            'proceedings': 'ประชุมวิชาการ',
            'thesis': 'วิทยานิพนธ์',
            'report': 'รายงาน',
            'other': 'อื่นๆ'
        };
        return typeMap[type] || type || '-';
    }
    
    // Build content array
    const content = [];
    
    // Header: Title - ดึงปีการศึกษาจากฐานข้อมูล
    const academicYear = form.academic_year || 2569;
    const academicYearThai = toThaiYear(academicYear);
    content.push({
        text: `แบบเสนอขอเปิดรับนักศึกษาใหม่ ประจำปีการศึกษา ${academicYearThai}`,
        fontSize: 16,
        bold: true,
        alignment: 'center',
        margin: [0, 0, 0, 5]
    });
    
    // Subtitle (right aligned)
    content.push({
        text: '(ระดับหลักสูตร)',
        fontSize: 12,
        alignment: 'right',
        margin: [0, 0, 0, 5]
    });
    
    // Faculty field (centered)
    content.push({
        text: `คณะ ${form.faculty_name || '...............................................................'}`,
        fontSize: 14,
        alignment: 'center',
        bold: true,
        margin: [0, 0, 0, 5]
    });
    
    // Section 1: Program Name
    content.push({
        text: '๑. ชื่อหลักสูตรสาขาวิชา',
        fontSize: 14,
        bold: true,
        margin: [0, 0, 0, 5]
    });
    
    content.push({
        text: `${form.curriculum_name || '-'} ฉบับปี พ.ศ. ${form.curriculum_version_year || '-'}`,
        fontSize: 14,
        margin: [30, 0, 0, 15]
    });
    
    // Section 2: Ministry Approval
    const ministryDate = form.ministry_approval_date ? formatDateFullThai(form.ministry_approval_date) : '-';
    const universityDate = form.university_approval_date ? formatDateFullThai(form.university_approval_date) : '-';
    
    content.push({
        text: '๒. หลักสูตรได้รับการพิจารณาความสอดคล้องจากสำนักปลัดกระทรวงอุดมศึกษา วิทยาศาสตร์ วิจัยและนวัตกรรม',
        fontSize: 14,
        bold: true,
        margin: [0, 0, 0, 5]
    });
    
    content.push({
        text: `เมื่อวันที่ ${ministryDate} \n กรณีหลักสูตรอยู่ในระหว่างการพัฒนาหรือปรับปรุง สภามหาวิทยาลัยเห็นชอบแล้วเมื่อวันที่ ${universityDate}`,
        fontSize: 14,
        margin: [30, 0, 0, 5]
    });
    
    
    // Section 3: Quality Assessment
    content.push({
        text: '๓. ผลการประเมินคุณภาพการศึกษาระดับหลักสูตร ๒ ปี ย้อนหลัง',
        fontSize: 14,
        bold: true,
        margin: [0, 0, 0, 5]
    });
    
    // Year 1
    content.push({
        text: `ปีการศึกษา ${form.quality_assessment_year1 || '-'} ผลการประเมินอยู่ในเกณฑ์ ${form.quality_assessment_result1 || '-'}`,
        fontSize: 14,
        margin: [30, 0, 0, 5]
    });
    
    // Year 2
    content.push({
        text: `ปีการศึกษา ${form.quality_assessment_year2 || '-'} ผลการประเมินอยู่ในเกณฑ์ ${form.quality_assessment_result2 || '-'}`,
        fontSize: 14,
        margin: [30, 0, 0, 5]
    });
    
    // Section 4: Teacher Information Table
    content.push({
        text: '๔. ข้อมูลอาจารย์ผู้รับผิดชอบหลักสูตร',
        fontSize: 14,
        bold: true,
        margin: [0, 0, 0, 5]
    });
    
    // Calculate years for table header (5 years: currentYear-4 to currentYear)
    const currentYear = form.academic_year || 2569;
    const yearHeaders = [];
    for (let y = 4; y >= 0; y--) {
        yearHeaders.push((currentYear - y).toString());
    }
    
    // Build publication data by teacher and year from database
    const publications = form.publications || [];
    const pubByTeacherYear = {};
    publications.forEach(pub => {
        const uid = pub.author_uid || pub.uid || pub.user_id;
        if (!uid) return;
        const pubYear = parseInt(pub.publication_year || 0);
        const pubYearBE = pubYear + 543; // Convert to พ.ศ.
        if (!pubByTeacherYear[uid]) {
            pubByTeacherYear[uid] = {};
        }
        if (!pubByTeacherYear[uid][pubYearBE]) {
            pubByTeacherYear[uid][pubYearBE] = 0;
        }
        pubByTeacherYear[uid][pubYearBE]++;
    });
    
    // Build teacher table
    const teacherTableBody = [];
    
    // Header row 1 - ตามรูปแบบในภาพ
    teacherTableBody.push([
        { text: 'ที่', rowSpan: 2, alignment: 'center', fontSize: 10, bold: true },
        { text: 'ตำแหน่ง', rowSpan: 2, alignment: 'center', fontSize: 10, bold: true },
        { text: 'ชื่อ-นามสกุล', rowSpan: 2, alignment: 'center', fontSize: 10, bold: true },
        {
            text: 'มีผลงานทางวิชาการที่เผยแพร่แล้วอยู่ในปี',
            colSpan: 5,
            alignment: 'center',
            fontSize: 10,
            bold: true
        },
        {}, {}, {}, {}
    ]);
    
    // Header row 2 - ปีการศึกษา (ปีสุดท้ายมีดอกจัน *)
    const headerRow2 = [
        {}, {}, {}, // Empty cells for rowSpan columns
    ];
    
    // Add year columns (2565, 2566, 2567, 2568, 2569*) - ปีสุดท้ายมีดอกจัน
    yearHeaders.forEach((year, index) => {
        const isLastYear = index === yearHeaders.length - 1;
        headerRow2.push({ 
            text: isLastYear ? year + '*' : year, 
            alignment: 'center', 
            fontSize: 10, 
            bold: true 
        });
    });
    
    teacherTableBody.push(headerRow2);
    
    // Data rows - ใช้ตามจำนวนอาจารย์ผู้รับผิดชอบหลักสูตรจริงๆ
    const teachers = form.teachers || [];
    const teacherCount = teachers.length;
    
    // แสดงตามจำนวนอาจารย์จริง (ไม่มีแถวว่าง)
    for (let i = 0; i < teacherCount; i++) {
        const teacher = teachers[i] || {};
        // Get position from titleThai, fallback to 'อาจารย์'
        const position = teacher.titleThai || teacher.position || 'อาจารย์';
        // Get name without title - prioritize thai_name + thai_lastname
        const nameOnly = (teacher.thai_name && teacher.thai_lastname) ? 
            (teacher.thai_name + ' ' + teacher.thai_lastname) : 
            (teacher.full_name ? teacher.full_name.replace(/^(อาจารย์|ดร\.|ผศ\.|ผศ\.ดร\.|รศ\.|รศ\.ดร\.|ศ\.|ศ\.ดร\.)\s*/g, '') : '-');
        const row = [
            { text: toThaiNumeral(i + 1), alignment: 'center', fontSize: 11 },
            { text: position, fontSize: 11 },
            { text: nameOnly, fontSize: 11 }
        ];
        
        // Add publication years - ดึงจากฐานข้อมูล
        const uid = teacher.user_id;
        const teacherPubs = pubByTeacherYear[uid] || {};
        yearHeaders.forEach((year, index) => {
            const count = teacherPubs[year] || 0;
            row.push({ 
                text: count > 0 ? 'X' : '', 
                alignment: 'center', 
                fontSize: 11 
            });
        });
        
        teacherTableBody.push(row);
    }
    
    content.push({
        table: {
            headerRows: 2,
            widths: [25, 65, 110, 35, 35, 35, 35, 35], // ที่, ตำแหน่ง, ชื่อ-นามสกุล, 5 ปี
            body: teacherTableBody
        },
        layout: {
            hLineWidth: function(i, node) { return 1; },
            vLineWidth: function(i, node) { return 1; },
            hLineColor: function(i, node) { return '#000000'; },
            vLineColor: function(i, node) { return '#000000'; },
            paddingLeft: function(i, node) { return 3; },
            paddingRight: function(i, node) { return 3; },
            paddingTop: function(i, node) { return 2; },
            paddingBottom: function(i, node) { return 2; }
        },
        fontSize: 11, // Slightly smaller font
        margin: [0, 0, 0, 5]
    });
    
    // Footnote: อธิบายดอกจัน
    content.push({
        text: '*ปี พ.ศ. ที่รับนักศึกษา',
        fontSize: 10,
        italics: true,
        margin: [0, 0, 0, 5]
    });
    
    // Section 4.1: Teacher Retention
    content.push({
        text: '๔.๑) การคงอยู่ของอาจารย์ในหลักสูตร',
        fontSize: 14,
        bold: true,
        margin: [0, 0, 0, 5]
    });
    
    const teachersStatus = form.teachers_status || '';
    
    // แสดงผลตามสถานะ: อาจารย์ทุกท่านอยู่ครบ หรือแสดงชื่ออาจารย์ที่ไม่ครบ
    if (teachersStatus === 'complete') {
        content.push({
            text: 'อาจารย์ทุกท่านอยู่ครบ',
            fontSize: 14,
            margin: [30, 0, 0, 5]
        });
    } else if (teachersStatus === 'incomplete') {
        // ดึงข้อมูลอาจารย์ที่ไม่ครบจากฐานข้อมูล
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
        
        // สร้างข้อความแสดงชื่ออาจารย์ที่ไม่ครบ
        let incompleteText = 'อาจารย์ที่ไม่ครบ: ';
        if (incompleteTeachers.length > 0) {
            const teacherNames = incompleteTeachers.map(t => {
                const name = t.name || (t.thai_name && t.thai_lastname ? (t.thai_name + ' ' + t.thai_lastname) : '-');
                return name;
            }).filter(n => n !== '-').join(', ');
            incompleteText += teacherNames || '-';
        } else {
            incompleteText += '-';
        }
        
        // เพิ่มเหตุผลถ้ามี
        const reason = form.teachers_incomplete_reason || '';
        if (reason && reason.trim() !== '') {
            incompleteText += ' เนื่องจาก' + reason;
        }
        
        content.push({
            text: incompleteText,
            fontSize: 14,
            margin: [30, 0, 0, 5]
        });
    } else {
        // Default: ถ้าไม่มีข้อมูลให้แสดงว่าครบ
        content.push({
            text: 'อาจารย์ทุกท่านอยู่ครบ',
            fontSize: 14,
            margin: [30, 0, 0, 5]
        });
    }
    
    // Section 4.2: Retired Teachers
    content.push({
        text: '๔.๒) อาจารย์ที่เกษียณอายุราชการ',
        fontSize: 14,
        bold: true,
        margin: [0, 0, 0, 5]
    });
    
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
            const year = form[`retiring_year${i}`];
            const count = form[`retiring_count${i}`];
            if (year || count) {
                retiringTeachers.push({year: year, count: count});
            }
        }
    }
    
    // Display all retiring teachers
    if (retiringTeachers.length > 0) {
        retiringTeachers.forEach(item => {
            content.push({
                text: `ในปี พ.ศ. ${item.year || '-'} จำนวน ${item.count || '-'} คน`,
                fontSize: 14,
                margin: [30, 0, 0, 3]
            });
        });
    } else {
        // ถ้าไม่มีข้อมูล ให้แสดง "ไม่มี"
        content.push({
            text: 'ไม่มี',
            fontSize: 14,
            margin: [30, 0, 0, 5]
        });
    }
    
    content.push({ text: '', margin: [0, 0, 0, 5] });
    
    // Section 4.3: Teachers Pursuing Further Studies
    content.push({
        text: '๔.๓) อาจารย์ศึกษาต่อ',
        fontSize: 14,
        bold: true,
        margin: [0, 0, 0, 5]
    });
    
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
            const year = form[`studying_year${i}`];
            const count = form[`studying_count${i}`];
            if (year || count) {
                studyingTeachers.push({year: year, count: count});
            }
        }
    }
    
    // Display all studying teachers
    if (studyingTeachers.length > 0) {
        studyingTeachers.forEach(item => {
            content.push({
                text: `ในปี พ.ศ. ${item.year || '-'} จำนวน ${item.count || '-'} คน`,
                fontSize: 14,
                margin: [30, 0, 0, 3]
            });
        });
    } else {
        // ถ้าไม่มีข้อมูล ให้แสดง "ไม่มี"
        content.push({
            text: 'ไม่มี',
            fontSize: 14,
            margin: [30, 0, 0, 5]
        });
    }
    
    content.push({ text: '', margin: [0, 0, 0, 5] });
    
    // Section 5: Academic Works Table (landscape page)
    // Add landscape page break
    content.push({
        text: '',
        pageBreak: 'before',
        pageOrientation: 'landscape'
    });
    
    content.push({
        text: '๕. ผลงานทางวิชาการของอาจารย์ผู้รับผิดชอบหลักสูตร',
        fontSize: 14,
        bold: true,
        margin: [0, 0, 0, 5]
    });
    
    // Use publications already declared above (line 263)
    // const publications = form.publications || []; // Already declared
    const publicationTableBody = [];
    
    // Header row
    publicationTableBody.push([
        { text: 'ลำดับที่', alignment: 'center', fontSize: 12, bold: true },
        { text: 'ชื่อ-สกุล', alignment: 'center', fontSize: 12, bold: true },
        { text: 'ประเภทผลงาน', alignment: 'center', fontSize: 12, bold: true },
        { text: 'ชื่อผลงาน', alignment: 'center', fontSize: 12, bold: true },
        { text: 'ปีที่เผยแพร่', alignment: 'center', fontSize: 12, bold: true }
    ]);
    
    // Data rows - แสดงตามจำนวนผลงานจริง (อย่างน้อย 5 แถว)
    const minRows = 5;
    const actualCount = publications.length;
    const rowCount = Math.max(minRows, actualCount);
    
    for (let i = 0; i < rowCount; i++) {
        const pub = publications[i] || {};
        const thaiNum = i < 10 ? toThaiNumeral(i + 1) : (i + 1).toString();
        
        // Get author name from publication_view (author_name_th or author_name)
        const authorName = pub.author_name_th || pub.author_name || '-';
        
        // Get publication type in Thai
        const pubTypeThai = getPublicationTypeThai(pub.publication_type);
        
        // ถ้ามีข้อมูลจริง แสดงข้อมูล ถ้าไม่มีแสดงแถวว่าง
        publicationTableBody.push([
            { text: thaiNum, alignment: 'center', fontSize: 12 },
            { text: authorName, fontSize: 12 },
            { text: pubTypeThai, fontSize: 12 },
            { text: pub.title || '-', fontSize: 12 },
            { text: pub.publication_year ? (parseInt(pub.publication_year) + 543).toString() : '-', alignment: 'center', fontSize: 12 }
        ]);
    }
    
    content.push({
        table: {
            headerRows: 1,
            widths: [50, 120, 100, '*', 80],
            body: publicationTableBody
        },
        layout: {
            hLineWidth: function(i, node) { return 1; },
            vLineWidth: function(i, node) { return 1; },
            hLineColor: function(i, node) { return '#000000'; },
            vLineColor: function(i, node) { return '#000000'; },
            paddingLeft: function(i, node) { return 5; },
            paddingRight: function(i, node) { return 5; },
            paddingTop: function(i, node) { return 3; },
            paddingBottom: function(i, node) { return 3; }
        },
        margin: [0, 0, 0, 15]
    });
    
    // Section 6: Student Group Information (return to portrait orientation)
    content.push({
        text: '๖. ข้อมูล กลุ่มผู้เรียน จำนวนรับ และคุณสมบัติของนักศึกษาที่ขอรับเข้าศึกษาในหลักสูตร',
        fontSize: 14,
        bold: true,
        margin: [0, 0, 0, 5],
        pageBreak: 'before',
        pageOrientation: 'portrait'
    });
    
    // Program type - display only selected type
    const hasMajorMinor = form.has_major_minor || 0;
    
    if (hasMajorMinor === 1) {
        content.push({
            text: 'หลักสูตรมีวิชาเอก/แขนง',
            fontSize: 14,
            margin: [0, 0, 0, 5]
        });
        // Show major count
        content.push({
            columns: [
                {
                    text: 'จำนวน',
                    fontSize: 14,
                    width: 250,
                    margin: [30, 0, 0, 0]
                },
                {
                    text: form.major_count || '-',
                    fontSize: 14,
                    width: 50,
                    alignment: 'right'
                },
                {
                    text: 'วิชาเอก',
                    fontSize: 14,
                    width: '*'
                }
            ],
            margin: [0, 0, 0, 5]
        });
        
        // Parse and display majors detail from JSON
        let majorsDetail = [];
        if (form.majors_detail) {
            try {
                majorsDetail = JSON.parse(form.majors_detail);
                if (!Array.isArray(majorsDetail)) {
                    majorsDetail = [];
                }
            } catch (e) {
                console.warn('Failed to parse majors_detail:', e);
                majorsDetail = [];
            }
        }
        
        // Display each major with its admission count
        if (majorsDetail.length > 0) {
            majorsDetail.forEach((major, index) => {
                content.push({
                    columns: [
                        {
                            text: `${index + 1}. ${major.major_name || '-'}`,
                            fontSize: 14,
                            width: 350,
                            margin: [50, 0, 0, 0]
                        },
                        {
                            text: 'จำนวน',
                            fontSize: 14,
                            width: 'auto'
                        },
                        {
                            text: major.admission_count || '-',
                            fontSize: 14,
                            width: 50,
                            alignment: 'right'
                        },
                        {
                            text: 'คน',
                            fontSize: 14,
                            width: '*'
                        }
                    ],
                    margin: [0, 0, 0, 3]
                });
            });
        }
        
        content.push({ text: '', margin: [0, 0, 0, 5] });
    } else {
        content.push({
            text: 'หลักสูตรไม่มีวิชาเอก/แขนง',
            fontSize: 14,
            margin: [30, 0, 0, 5]
        });
    }
    
    // Admission plan
    content.push({
        columns: [
            {
                text: 'แผนรับนักศึกษาตามเล่มรายละเอียดหลักสูตร จำนวน',
                fontSize: 14,
                width: 290,
                margin: [30, 0, 0, 0]
            },
            {
                text: form.admission_plan_count || '-',
                fontSize: 14,
                width: 50,
                alignment: 'right'
            },
            {
                text: ' คน',
                fontSize: 14,
                width: '*'
            }
        ],
        margin: [0, 0, 0, 5]
    });
    
    // Student group subsection - display based on data
    content.push({
        text: 'กลุ่มผู้เรียนที่ขอเปิดรับนักศึกษา',
        fontSize: 14,
        bold: true,
        margin: [30, 0, 0, 5]
    });
    
    const targetHighschool = form.target_highschool || 0;
    const targetDiploma = form.target_diploma || 0;
    const hasHighschoolCount = form.target_highschool_count && form.target_highschool_count > 0;
    const hasDiplomaCount = form.target_diploma_count && form.target_diploma_count > 0;
    
    // Display groups based on actual data
    if (hasHighschoolCount || targetHighschool === 1) {
        content.push({
            columns: [
                {
                    text: 'มัธยมศึกษาตอนปลายหรือเทียบเท่า',
                    fontSize: 14,
                    width: 250,
                    margin: [40, 0, 0, 0]
                },
                {
                    text: form.target_highschool_count || '-',
                    fontSize: 14,
                    width: 50,
                    alignment: 'right'
                },
                {
                    text: ' คน',
                    fontSize: 14,
                    width: '*'
                }
            ],
            margin: [0, 0, 0, 5]
        });
    }
    
    if (hasDiplomaCount || targetDiploma === 1) {
        content.push({
            columns: [
                {
                    text: 'ปวส./อนุปริญญา',
                    fontSize: 14,
                    width: 250,
                    margin: [40, 0, 0, 0]
                },
                {
                    text: form.target_diploma_count || '-',
                    fontSize: 14,
                    width: 50,
                    alignment: 'right'
                },
                {
                    text: ' คน',
                    fontSize: 14,
                    width: '*'
                }
            ],
            margin: [0, 0, 0, 5]
        });
    }
    
    // If no data at all
    if (!hasHighschoolCount && !hasDiplomaCount && targetHighschool === 0 && targetDiploma === 0) {
        content.push({
            text: 'ไม่ได้ระบุ',
            fontSize: 14,
            margin: [0, 0, 0, 5],
            color: '#999999'
        });
    }
    
    content.push({ text: '', margin: [0, 0, 0, 10] });
    
    // Section 7: Student Qualifications
    content.push({
        text: '๗. คุณสมบัติของผู้เรียน',
        fontSize: 14,
        bold: true,
        margin: [0, 0, 0, 5]
    });
    
    // Parse qualifications from JSON or fallback to split('\n') for old data
    let highschoolQuals = [];
    if (form.qualification_highschool) {
        try {
            highschoolQuals = JSON.parse(form.qualification_highschool);
            if (!Array.isArray(highschoolQuals)) {
                highschoolQuals = [highschoolQuals];
            }
            highschoolQuals = highschoolQuals.filter(q => q && q.trim());
        } catch (e) {
            highschoolQuals = form.qualification_highschool.split('\n').filter(q => q.trim());
        }
    }
    
    let diplomaQuals = [];
    if (form.qualification_diploma) {
        try {
            diplomaQuals = JSON.parse(form.qualification_diploma);
            if (!Array.isArray(diplomaQuals)) {
                diplomaQuals = [diplomaQuals];
            }
            diplomaQuals = diplomaQuals.filter(q => q && q.trim());
        } catch (e) {
            diplomaQuals = form.qualification_diploma.split('\n').filter(q => q.trim());
        }
    }
    
    // Display qualifications - show based on selected target groups
    let hasAnyQualification = false;
    
    // มัธยมศึกษาตอนปลาย
    if (targetHighschool === 1 || highschoolQuals.length > 0) {
        hasAnyQualification = true;
        content.push({
            text: 'มัธยมศึกษาตอนปลายหรือเทียบเท่า',
            fontSize: 14,
            bold: true,
            margin: [30, 0, 0, 5]
        });
        
        if (highschoolQuals.length > 0) {
            highschoolQuals.forEach((qual, i) => {
                content.push({
                    text: `${i + 1}. ${qual}`,
                    fontSize: 14,
                    margin: [40, 0, 0, 3]
                });
            });
        } else {
            content.push({
                text: 'เกรดเฉลี่ยสะสมไม่ต่ำกว่า 3.5',
                fontSize: 14,
                margin: [40, 0, 0, 5]
            });
        }
        
        content.push({ text: '', margin: [0, 0, 0, 8] });
    }
    
    // ปวส./อนุปริญญา
    if (targetDiploma === 1 || diplomaQuals.length > 0) {
        hasAnyQualification = true;
        content.push({
            text: 'ปวส./อนุปริญญา',
            fontSize: 14,
            bold: true,
            margin: [30, 0, 0, 5]
        });
        
        if (diplomaQuals.length > 0) {
            diplomaQuals.forEach((qual, i) => {
                content.push({
                    text: `${i + 1}. ${qual}`,
                    fontSize: 14,
                    margin: [40, 0, 0, 3]
                });
            });
        } else {
            content.push({
                text: 'เกรดเฉลี่ยสะสมไม่ต่ำกว่า 2.0',
                fontSize: 14,
                margin: [40, 0, 0, 5]
            });
        }
        
        content.push({ text: '', margin: [0, 0, 0, 8] });
    }
    
    // If no qualifications at all
    if (!hasAnyQualification) {
        content.push({
            text: 'ไม่ได้ระบุ',
            fontSize: 14,
            margin: [0, 0, 0, 5],
            color: '#999999'
        });
    }
    
    content.push({ text: '', margin: [0, 0, 0, 15] });
    
    // Section 8: Current Student Count
    content.push({
        text: '๘. ข้อมูลจำนวนนักศึกษาปัจจุบัน',
        fontSize: 14,
        bold: true,
        margin: [0, 0, 0, 5]
    });
    
    // Build table for current student count
    const yearLabels = ['ชั้นปีที่ ๑', 'ชั้นปีที่ ๒', 'ชั้นปีที่ ๓', 'ชั้นปีที่ ๔'];
    const studentTableBody = [];
    
    // Header row
    studentTableBody.push([
        { 
            text: 'รายการ', 
            alignment: 'center', 
            fontSize: 12, 
            bold: true,
            fillColor: '#f0f0f0'
        },
        { 
            text: 'มัธยมศึกษาตอนปลายหรือเทียบเท่า', 
            alignment: 'center', 
            fontSize: 12, 
            bold: true,
            fillColor: '#f0f0f0'
        },
        { 
            text: 'ปวส./อนุปริญญา', 
            alignment: 'center', 
            fontSize: 12, 
            bold: true,
            fillColor: '#f0f0f0'
        }
    ]);
    
    // Year rows
    for (let i = 0; i < 4; i++) {
        const highschoolCount = form[`current_highschool_year${i + 1}`];
        const diplomaCount = form[`current_diploma_year${i + 1}`];
        studentTableBody.push([
            { 
                text: yearLabels[i], 
                fontSize: 12,
                margin: [5, 2, 5, 2]
            },
            { 
                text: highschoolCount ? highschoolCount.toString() + ' คน' : '-', 
                alignment: 'center', 
                fontSize: 12,
                margin: [5, 2, 5, 2]
            },
            { 
                text: diplomaCount ? diplomaCount.toString() + ' คน' : '-', 
                alignment: 'center', 
                fontSize: 12,
                margin: [5, 2, 5, 2]
            }
        ]);
    }
    
    // Graduated row
    studentTableBody.push([
        { 
            text: 'สำเร็จการศึกษา', 
            fontSize: 12,
            margin: [5, 2, 5, 2]
        },
        { 
            text: form.current_highschool_graduated ? form.current_highschool_graduated.toString() + ' คน' : '-', 
            alignment: 'center', 
            fontSize: 12,
            margin: [5, 2, 5, 2]
        },
        { 
            text: form.current_diploma_graduated ? form.current_diploma_graduated.toString() + ' คน' : '-', 
            alignment: 'center', 
            fontSize: 12,
            margin: [5, 2, 5, 2]
        }
    ]);
    
    // Remaining students row
    studentTableBody.push([
        { 
            text: 'นักศึกษาค้างชั้น', 
            fontSize: 12,
            margin: [5, 2, 5, 2]
        },
        { 
            text: form.current_highschool_remain ? form.current_highschool_remain.toString() + ' คน' : '-', 
            alignment: 'center', 
            fontSize: 12,
            margin: [5, 2, 5, 2]
        },
        { 
            text: form.current_diploma_remain ? form.current_diploma_remain.toString() + ' คน' : '-', 
            alignment: 'center', 
            fontSize: 12,
            margin: [5, 2, 5, 2]
        }
    ]);
    
    content.push({
        table: {
            headerRows: 1,
            widths: ['*', '*', '*'],
            body: studentTableBody
        },
        layout: {
            hLineWidth: function(i, node) { return 1; },
            vLineWidth: function(i, node) { return 1; },
            hLineColor: function(i, node) { return '#000000'; },
            vLineColor: function(i, node) { return '#000000'; },
            paddingLeft: function(i, node) { return 3; },
            paddingRight: function(i, node) { return 3; },
            paddingTop: function(i, node) { return 2; },
            paddingBottom: function(i, node) { return 2; }
        },
        margin: [0, 0, 0, 5]
    });
    
    // Section 14: Student Development Plan
    content.push({
        text: '๙. แผนพัฒนานักศึกษาค้างชั้น',
        fontSize: 14,
        bold: true,
        margin: [0, 0, 0, 5]
    });
    
    content.push({
        text: 'วิธีดำเนินการ',
        fontSize: 14,
        margin: [0, 0, 0, 5]
    });
    
    content.push({
        text: form.remaining_student_plan || '-',
        fontSize: 14,
        margin: [30, 0, 0, 5],
        alignment: 'justify'
    });
    
    content.push({
        text: 'ตัวชี้วัดความสำเร็จ',
        fontSize: 14,
        margin: [0, 0, 0, 5]
    });
    
    content.push({
        text: form.remaining_student_kpi || '-',
        fontSize: 14,
        margin: [30, 0, 0, 5],
        alignment: 'justify'
    });
    
    // Approval Section: 2 Columns (Curriculum Head & Dean)
    const headDate = form.curriculum_head_approval_date ? formatDateFullThai(form.curriculum_head_approval_date) : '';
    const deanDate = form.dean_approval_date ? formatDateFullThai(form.dean_approval_date) : '';
    
    content.push({
        columns: [
            // Left Column: Curriculum Head
            {
                stack: [
                    {
                        text: 'ความเห็นชอบของประธานหลักสูตร',
                        fontSize: 14,
                        bold: true,
                        color: '#0066cc',
                        margin: [0, 0, 0, 5]
                    },
                    {
                        text: `นำเสนอและผ่านความเห็นชอบของคณะกรรมการบริหารหลักสูตรแล้วเมื่อ ${headDate || '.........'}`,
                        fontSize: 14,
                        margin: [0, 0, 0, 15]
                    },
                    {
                        text: 'ลงชื่อ .................................................................................',
                        fontSize: 14,
                        margin: [0, 0, 0, 5]
                    },
                    {
                        text: `(${form.curriculum_head_name || '-'})`,
                        fontSize: 14,
                        alignment: 'center',
                        margin: [0, 0, 0, 3]
                    },
                    {
                        text: 'ประธานหลักสูตร',
                        fontSize: 14,
                        alignment: 'center',
                        margin: [0, 0, 0, 3]
                    },
                    {
                        text: headDate ? `${headDate}` : 'วัน เดือน ปี: ......../......../........',
                        fontSize: 14,
                        alignment: 'center',
                        margin: [0, 0, 0, 0]
                    }
                ],
                width: '*'
            },
            // Right Column: Dean
            {
                stack: [
                    {
                        text: 'ความเห็นชอบของคณบดี',
                        fontSize: 14,
                        bold: true,
                        color: '#0066cc',
                        margin: [0, 0, 0, 5]
                    },
                    {
                        text: `รับทราบและเห็นควรนำเสนอคณะกรรมการ (${form.faculty_name || 'ของคณะ'})`,
                        fontSize: 14,
                        margin: [0, 0, 0, 3]
                    },
                    {
                        text: '',
                        fontSize: 14,
                        margin: [0, 0, 0, 15]
                    },
                    {
                        text: 'ลงชื่อ ................................................................................',
                        fontSize: 14,
                        margin: [0, 0, 0, 5]
                    },
                    {
                        text: `(${form.dean_name || '-'})`,
                        fontSize: 14,
                        alignment: 'center',
                        margin: [0, 0, 0, 3]
                    },
                    {
                        text: 'คณบดี',
                        fontSize: 14,
                        alignment: 'center',
                        margin: [0, 0, 0, 3]
                    },
                    {
                        text: deanDate ? `${deanDate}` : 'วัน เดือน ปี: ......../......../........',
                        fontSize: 14,
                        alignment: 'center',
                        margin: [0, 0, 0, 0]
                    }
                ],
                width: '*'
            }
        ],
        columnGap: 20,
        margin: [0, 10, 10, 15]
    });
    
    // Build PDF document definition
    const docDefinition = {
        pageSize: 'A4',
        pageOrientation: 'portrait',
        pageMargins: [50, 40, 50, 40],
        defaultStyle: {
            font: fontFamily,
            fontSize: 14,
            lineHeight: 1.0
        },
        content: content,
        styles: {
            header: {
                fontSize: 16,
                bold: true,
                alignment: 'center'
            }
        }
    };
    
    // Generate and open PDF (with fallback for popup blockers)
    try {
        console.log('Creating PDF with font:', fontFamily);
        console.log('Content items count:', content.length);
        console.log('Publications count:', publications.length);
        
        // Validate content
        if (!content || content.length === 0) {
            throw new Error('ไม่มีข้อมูลสำหรับสร้าง PDF');
        }
        
        const fileName = `admission_form_${form.academic_year || 'unknown'}_${new Date().getTime()}.pdf`;
        
        // Check if pdfMake is available
        if (typeof pdfMake === 'undefined') {
            throw new Error('pdfMake library ไม่พร้อมใช้งาน');
        }
        
        // Create PDF instance
        const pdfDocGenerator = pdfMake.createPdf(docDefinition);
        
        if (!pdfDocGenerator) {
            throw new Error('ไม่สามารถสร้าง PDF instance ได้');
        }
        
        // Try to open in new window first
        // If blocked by browser, will fallback to download
        try {
            pdfDocGenerator.getBlob((blob) => {
                if (!blob) {
                    console.error('PDF blob is null or undefined');
                    throw new Error('ไม่สามารถสร้าง PDF blob ได้');
                }
                
                // Create blob URL
                const blobUrl = URL.createObjectURL(blob);
                
                // Try to open in new window
                const newWindow = window.open(blobUrl, '_blank');
                
                // If popup was blocked, download instead
                if (!newWindow || newWindow.closed || typeof newWindow.closed == 'undefined') {
                    console.warn('Popup blocked, downloading PDF instead');
                    pdfDocGenerator.download(fileName);
                } else {
                    console.log('PDF opened in new window:', fileName);
                    // Clean up blob URL after a delay
                    setTimeout(() => URL.revokeObjectURL(blobUrl), 1000);
                }
            });
        } catch (openError) {
            console.warn('Failed to open PDF, downloading instead:', openError);
            // Fallback to download
            pdfDocGenerator.download(fileName);
        }
        
    } catch (error) {
        console.error('PDF generation error:', error);
        console.error('Error stack:', error.stack);
        // Show user-friendly error message
        alert('เกิดข้อผิดพลาดในการสร้าง PDF: ' + (error.message || 'ไม่ทราบสาเหตุ'));
        throw error;
    }
}

/**
 * Load Thai font for PDF generation (same as publication-summary-pdf.js)
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

// Export functions for use in other scripts
window.generateAdmissionFormPDF = generateAdmissionFormPDF;
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
