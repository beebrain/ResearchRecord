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

    // Helper: convert an academic position/role to Thai.
    // The DB role is stored in English (e.g. "instructor" from teacher_curriculum),
    // which must not be printed raw in the Thai form.
    function getPositionThai(pos) {
        if (!pos) return 'อาจารย์';
        const s = String(pos).trim().toLowerCase();
        const map = {
            'instructor': 'อาจารย์',
            'lecturer': 'อาจารย์',
            'teacher': 'อาจารย์',
            'assistant professor': 'ผู้ช่วยศาสตราจารย์',
            'asst. professor': 'ผู้ช่วยศาสตราจารย์',
            'asst professor': 'ผู้ช่วยศาสตราจารย์',
            'associate professor': 'รองศาสตราจารย์',
            'assoc. professor': 'รองศาสตราจารย์',
            'assoc professor': 'รองศาสตราจารย์',
            'professor': 'ศาสตราจารย์',
            'prof.': 'ศาสตราจารย์',
            'prof': 'ศาสตราจารย์'
        };
        // ถ้า map ได้ใช้ค่าไทย, ถ้าเป็นภาษาไทยอยู่แล้วใช้ค่าเดิม
        return map[s] || pos;
    }

    // Helper: wrap Thai text to fit a fixed-width table cell.
    // Thai has no spaces, so a long title is one unbreakable "word" that forces the
    // column (and the whole table) wider than the page. We pre-break it into real
    // lines joined with "\n" — which pdfmake renders natively, so there is no need
    // to insert a zero-width-space character (the THSarabunNew font has no glyph for
    // U+200B and would render it as a tofu box "□").
    //
    // Line width is estimated from measured THSarabunNew advances @12pt:
    //   combining marks (tone/upper/lower vowels) = 0pt, space ≈ 2.6pt, other ≈ 4.7pt.
    // maxWidth = available text width of the cell in points.
    function wrapThaiText(text, maxWidth) {
        if (text === null || text === undefined) return '';
        const str = String(text);
        if (!str) return '';
        const MAX_WIDTH = maxWidth || 400;
        const W_OTHER = 4.7;
        const W_SPACE = 2.6;

        const isZeroWidth = (c) =>
            c === 0x0E31 || (c >= 0x0E34 && c <= 0x0E3A) || (c >= 0x0E47 && c <= 0x0E4E);
        const charW = (ch) => {
            const c = ch.codePointAt(0);
            if (isZeroWidth(c)) return 0;
            if (ch === ' ') return W_SPACE;
            return W_OTHER;
        };
        const strW = (s) => { let w = 0; for (const ch of s) w += charW(ch); return w; };

        // แตกเป็น "คำ" ภาษาไทยเพื่อเลือกจุดตัดบรรทัดที่เหมาะสม (ถ้าเบราว์เซอร์รองรับ)
        let words;
        try {
            if (typeof Intl !== 'undefined' && Intl.Segmenter) {
                words = Array.from(new Intl.Segmenter('th', { granularity: 'word' }).segment(str), s => s.segment);
            } else {
                words = str.match(/\s+|\S+/g) || [str];
            }
        } catch (e) {
            words = str.match(/\s+|\S+/g) || [str];
        }

        const lines = [];
        let cur = '', curW = 0;
        const flush = () => { lines.push(cur.replace(/^ +/, '')); cur = ''; curW = 0; };

        for (const word of words) {
            const ww = strW(word);
            if (curW > 0 && curW + ww > MAX_WIDTH) flush();
            if (ww > MAX_WIDTH) {
                // คำเดียวยาวเกินบรรทัด → ตัดทีละอักขระ
                for (const ch of word) {
                    const cw = charW(ch);
                    if (curW + cw > MAX_WIDTH && curW > 0) flush();
                    cur += ch; curW += cw;
                }
            } else {
                cur += word; curW += ww;
            }
        }
        if (cur !== '') flush();

        return lines.join('\n');
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
        // Get position from titleThai, fallback to Thai-mapped role (e.g. instructor -> อาจารย์)
        const position = teacher.titleThai || getPositionThai(teacher.position);
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
            // '*' on the name column makes the table span the full portrait
            // page width (usable ≈ 495pt); year columns fixed and centred.
            widths: [30, 80, '*', 50, 50, 50, 50, 50], // ที่, ตำแหน่ง, ชื่อ-นามสกุล, 5 ปี
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
    
    // Section 5 presents one block per responsible teacher: a qualifications
    // header (position / all education degrees / relationship) followed by that
    // teacher's academic works rendered as APA-style citations.
    // - Education comes from the CV module (teacher.education, attached server-side).
    // - Works are grouped from form.publications by user_id (= teacher email),
    //   which getTeacherPublications() already tags per teacher.

    // Build an APA-style citation array (source italicised) from a publication row.
    const buildApaCitation = (pub) => {
        const authors = (pub.authors_names_th || pub.authors_names_en
            || pub.author_name_th || pub.author_name || '').trim();
        const yearCe = parseInt(pub.publication_year || 0);
        const yearBe = yearCe ? (yearCe + 543).toString() : '-';
        const title = (pub.title || '').trim();
        const source = (pub.source || '').trim();
        const volume = (pub.volume != null ? String(pub.volume) : '').trim();
        const pages = (pub.pages != null ? String(pub.pages) : '').trim();

        const parts = [];
        if (authors) parts.push({ text: authors + ' ' });
        parts.push({ text: '(' + yearBe + '). ' });
        if (title) parts.push({ text: title + '. ' });
        if (source) parts.push({ text: source, italics: true });
        let tail = '';
        if (volume) tail += (source ? ', ' : '') + volume;
        if (pages) tail += ((volume || source) ? ', ' : '') + 'หน้า ' + pages;
        tail += '.';
        parts.push({ text: tail });
        return parts;
    };

    // Group teacher publications by teacher email (user_id), newest first.
    const pubsByTeacher = {};
    publications.forEach(pub => {
        const uid = (pub.user_id || pub.author_uid || '').toString().toLowerCase().trim();
        if (!uid) return;
        (pubsByTeacher[uid] = pubsByTeacher[uid] || []).push(pub);
    });
    Object.keys(pubsByTeacher).forEach(uid => {
        pubsByTeacher[uid].sort((a, b) =>
            (parseInt(b.publication_year || 0)) - (parseInt(a.publication_year || 0)));
    });

    const responsibleTeachers = form.teachers || [];

    responsibleTeachers.forEach((teacher, idx) => {
        const email = (teacher.user_email || teacher.user_id || '').toString().toLowerCase().trim();
        const position = teacher.titleThai || getPositionThai(teacher.position) || '-';
        const nameOnly = (teacher.thai_name && teacher.thai_lastname)
            ? (teacher.thai_name + ' ' + teacher.thai_lastname)
            : (teacher.full_name
                ? teacher.full_name.replace(/^(อาจารย์|ดร\.|ผศ\.|ผศ\.ดร\.|รศ\.|รศ\.ดร\.|ศ\.|ศ\.ดร\.)\s*/g, '')
                : '-');

        // Education cell — list every degree (highest/most recent first).
        const eduList = teacher.education || [];
        const eduCell = eduList.length
            ? {
                stack: eduList.map(e => {
                    let line = (e.title || '').trim();
                    if (e.organization) line += (line ? ' ' : '') + e.organization;
                    if (e.grad_year_be) line += ' (' + e.grad_year_be + ')';
                    return { text: line, fontSize: 11, margin: [0, 0, 0, 2] };
                })
            }
            : { text: '-', fontSize: 11 };

        // Works block — APA citations under the standard heading.
        const teacherPubs = pubsByTeacher[email] || [];
        const worksStack = [{
            text: 'ผลงานวิชาการ (อย่างน้อย 1 รายการในรอบ 5 ปี (ปีการศึกษา ' +
                ((currentYear - 4) + '–' + currentYear) + '))',
            bold: true,
            fontSize: 11,
            margin: [0, 0, 0, 4]
        }];
        if (teacherPubs.length) {
            teacherPubs.forEach(pub => {
                worksStack.push({ text: buildApaCitation(pub), fontSize: 11, margin: [0, 0, 0, 4] });
            });
        } else {
            worksStack.push({ text: '-', fontSize: 11 });
        }

        const headerFill = '#e8e8e8';
        const teacherTable = [
            [
                { text: 'ลำดับ', alignment: 'center', fontSize: 11, bold: true, fillColor: headerFill },
                { text: 'ชื่อ–นามสกุล', alignment: 'center', fontSize: 11, bold: true, fillColor: headerFill },
                { text: 'ตำแหน่ง\nทางวิชาการ', alignment: 'center', fontSize: 11, bold: true, fillColor: headerFill },
                { text: 'คุณวุฒิการศึกษา\n(ทุกระดับการศึกษา)', alignment: 'center', fontSize: 11, bold: true, fillColor: headerFill },
                { text: 'ความสัมพันธ์\n(วุฒิตรง หรือ สัมพันธ์)', alignment: 'center', fontSize: 11, bold: true, fillColor: headerFill }
            ],
            [
                { text: (idx + 1).toString(), alignment: 'center', fontSize: 11 },
                { text: nameOnly, fontSize: 11 },
                { text: position, fontSize: 11 },
                eduCell,
                { text: teacher.degree_relation || '-', alignment: 'center', fontSize: 11 }
            ],
            [
                { colSpan: 5, stack: worksStack }, {}, {}, {}, {}
            ]
        ];

        content.push({
            table: {
                headerRows: 1,
                // Landscape A4 usable width ≈ 742pt.
                widths: [30, 120, 90, '*', 90],
                body: teacherTable
            },
            layout: {
                hLineWidth: function() { return 1; },
                vLineWidth: function() { return 1; },
                hLineColor: function() { return '#000000'; },
                vLineColor: function() { return '#000000'; },
                paddingLeft: function() { return 5; },
                paddingRight: function() { return 5; },
                paddingTop: function() { return 3; },
                paddingBottom: function() { return 3; }
            },
            // keep each teacher block from splitting awkwardly across pages
            unbreakable: false,
            margin: [0, 0, 0, 12]
        });
    });

    if (responsibleTeachers.length === 0) {
        content.push({ text: '-', fontSize: 12, margin: [0, 0, 0, 12] });
    }
    
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
    // ใช้ String(...) === '1' ให้ตรงกับ section ๗ (ค่าจาก DB/JSON เป็น string '1')
    const hasMajorMinor = String(form.has_major_minor) === '1';

    if (hasMajorMinor) {
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
    
    // Per-branch qualifications when หลักสูตรมีวิชาเอก/แขนง
    let branchMajors = [];
    if (form.majors_detail) {
        try {
            branchMajors = typeof form.majors_detail === 'string' ? JSON.parse(form.majors_detail) : form.majors_detail;
            if (!Array.isArray(branchMajors)) branchMajors = [];
        } catch (e) {
            branchMajors = [];
        }
    }
    const useBranchQuals = String(form.has_major_minor) === '1' && branchMajors.length > 0;

    if (useBranchQuals) {
        branchMajors.forEach((m, idx) => {
            content.push({
                text: `${idx + 1}. ${m.major_name || '-'}`,
                fontSize: 14,
                bold: true,
                margin: [30, 0, 0, 5]
            });
            const quals = Array.isArray(m.qualifications) ? m.qualifications.filter(q => q && String(q).trim()) : [];
            if (quals.length > 0) {
                quals.forEach((qual, i) => {
                    content.push({
                        text: `${i + 1}. ${qual}`,
                        fontSize: 14,
                        margin: [40, 0, 0, 3]
                    });
                });
            } else {
                content.push({
                    text: 'ไม่ได้ระบุ',
                    fontSize: 14,
                    margin: [40, 0, 0, 3],
                    color: '#999999'
                });
            }
            content.push({ text: '', margin: [0, 0, 0, 8] });
        });
    } else {

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

    } // end else (global qualifications)

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
        
        // ส่ง PDF ที่เรนเดอร์เสร็จไปให้ server stream กลับมาเป็นไฟล์ พร้อมส่วนหัว
        // Content-Disposition เพื่อให้ "ตั้งชื่อไฟล์ได้ถูกต้องทุกเบราว์เซอร์"
        // (บางเบราว์เซอร์/in-app webview ไม่สนใจ <a download> บน blob: URL จึงตั้งชื่อ
        //  ไฟล์เป็น UUID ของ blob แทนชื่อจริง — การให้ server กำหนดชื่อจึงชัวร์กว่า)
        const downloadUrl = (typeof window.appRoute === 'function')
            ? window.appRoute('admin/admission/pdf-download')
            : ((typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/index.php/admin/admission/pdf-download');

        // Fallback: ดาวน์โหลดฝั่ง client ด้วย <a download> (เผื่อ server-download ใช้ไม่ได้)
        function fallbackClientDownload(blob) {
            try {
                const blobUrl = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = blobUrl;
                a.download = fileName;
                a.rel = 'noopener';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.__lastPdfBlobUrl = blobUrl;
                setTimeout(() => URL.revokeObjectURL(blobUrl), 120000);
                if (typeof window.__onPdfReady === 'function') window.__onPdfReady(blobUrl, fileName);
            } catch (e) {
                console.error('PDF fallback error:', e);
                if (typeof window.__onPdfError === 'function') window.__onPdfError(e);
            }
        }

        pdfDocGenerator.getBlob((blob) => {
            try {
                if (!blob) throw new Error('ไม่สามารถสร้าง PDF blob ได้');

                const reader = new FileReader();
                reader.onload = function () {
                    try {
                        const base64 = String(reader.result).split(',')[1] || '';
                        if (!base64) throw new Error('แปลง PDF เป็น base64 ไม่สำเร็จ');

                        // submit ผ่าน hidden form → hidden iframe เพื่อให้ browser ดาวน์โหลด
                        // ไฟล์จาก response ของ server (ไม่พึ่ง a.download)
                        let iframe = document.getElementById('__pdf_dl_frame');
                        if (!iframe) {
                            iframe = document.createElement('iframe');
                            iframe.id = '__pdf_dl_frame';
                            iframe.name = '__pdf_dl_frame';
                            iframe.style.display = 'none';
                            document.body.appendChild(iframe);
                        }

                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = downloadUrl;
                        form.target = '__pdf_dl_frame';
                        form.style.display = 'none';

                        const dataInput = document.createElement('textarea');
                        dataInput.name = 'data';
                        dataInput.value = base64;
                        form.appendChild(dataInput);

                        const nameInput = document.createElement('input');
                        nameInput.type = 'hidden';
                        nameInput.name = 'filename';
                        nameInput.value = fileName;
                        form.appendChild(nameInput);

                        document.body.appendChild(form);
                        form.submit();
                        document.body.removeChild(form);

                        console.log('PDF submitted to server for download:', fileName);
                        window.__lastPdfFileName = fileName;

                        // เก็บ blob URL ไว้ให้กด "เปิดดู PDF" ได้
                        const blobUrl = URL.createObjectURL(blob);
                        window.__lastPdfBlobUrl = blobUrl;
                        setTimeout(() => URL.revokeObjectURL(blobUrl), 120000);

                        if (typeof window.__onPdfReady === 'function') window.__onPdfReady(blobUrl, fileName);
                    } catch (postErr) {
                        console.error('PDF server-download error, falling back to client download:', postErr);
                        fallbackClientDownload(blob);
                    }
                };
                reader.onerror = function () { fallbackClientDownload(blob); };
                reader.readAsDataURL(blob);
            } catch (deliverError) {
                console.error('PDF delivery error:', deliverError);
                if (typeof window.__onPdfError === 'function') window.__onPdfError(deliverError);
            }
        });
        
    } catch (error) {
        console.error('PDF generation error:', error);
        console.error('Error stack:', error.stack);
        // ส่ง log ไป server (ถ้ามี logger จากหน้า pdf-summary)
        if (typeof window.__pdfLog === 'function') {
            window.__pdfLog('error', 'generateAdmissionFormPDF error: ' + (error.message || error), {
                stack: error && error.stack ? String(error.stack).substring(0, 800) : null
            });
        }
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
