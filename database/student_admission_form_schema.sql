-- ========================================
-- Schema for Student Admission Form (แบบฟอร์มขอเปิดรับนักศึกษาใหม่)
-- Created: 2025-12-08
-- ========================================

-- Main table for storing admission form data by curriculum and year
CREATE TABLE IF NOT EXISTS `student_admission_forms` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `academic_year` int(4) NOT NULL COMMENT 'ปีการศึกษา (พ.ศ.) เช่น 2568, 2569',
    `curriculum_id` int(11) NOT NULL COMMENT 'หลักสูตร',
    `faculty_id` int(11) NOT NULL COMMENT 'คณะ',
    
    -- Section 1: Basic curriculum info
    `curriculum_name` varchar(500) DEFAULT NULL COMMENT 'ชื่อหลักสูตรสาขาวิชา',
    `curriculum_version_year` int(4) DEFAULT NULL COMMENT 'ฉบับปี พ.ศ.',
    
    -- Section 2: Ministry approval
    `ministry_approval_date` date DEFAULT NULL COMMENT 'วันที่ได้รับการพิจารณาความสอดคล้องจาก สป.อว.',
    `university_approval_date` date DEFAULT NULL COMMENT 'สภามหาวิทยาลัยเห็นชอบเมื่อวันที่ (กรณีปรับปรุง)',
    
    -- Section 3: Quality assessment (2 years back)
    `quality_assessment_year1` int(4) DEFAULT NULL COMMENT 'ปีการศึกษา (ปีแรก)',
    `quality_assessment_result1` varchar(100) DEFAULT NULL COMMENT 'ผลการประเมินอยู่ในเกณฑ์ (ปีแรก)',
    `quality_assessment_year2` int(4) DEFAULT NULL COMMENT 'ปีการศึกษา (ปีที่สอง)',
    `quality_assessment_result2` varchar(100) DEFAULT NULL COMMENT 'ผลการประเมินอยู่ในเกณฑ์ (ปีที่สอง)',
    
    -- Section 4.1: Responsible teachers status
    `teachers_status` enum('complete','incomplete') DEFAULT NULL COMMENT 'การคงอยู่ของอาจารย์ในหลักสูตร (ครบ/ไม่ครบ)',
    `teachers_incomplete_order` int(2) DEFAULT NULL COMMENT 'ลำดับที่ไม่ครบ',
    `teachers_incomplete_reason` text DEFAULT NULL COMMENT 'เหตุผลที่ไม่ครบ',
    
    -- Section 4.2: Retiring teachers
    `retiring_year1` int(4) DEFAULT NULL COMMENT 'ปี พ.ศ. ที่มีอาจารย์เกษียณ (ปีแรก)',
    `retiring_count1` int(3) DEFAULT NULL COMMENT 'จำนวนอาจารย์เกษียณ (ปีแรก)',
    `retiring_year2` int(4) DEFAULT NULL COMMENT 'ปี พ.ศ. ที่มีอาจารย์เกษียณ (ปีที่สอง)',
    `retiring_count2` int(3) DEFAULT NULL COMMENT 'จำนวนอาจารย์เกษียณ (ปีที่สอง)',
    
    -- Section 6: Major/Minor info
    `has_major_minor` tinyint(1) DEFAULT 0 COMMENT 'หลักสูตรมีวิชาเอก/แขนง (0=ไม่มี, 1=มี)',
    `major_count` int(3) DEFAULT NULL COMMENT 'จำนวนวิชาเอก',
    `admission_plan_count` int(5) DEFAULT NULL COMMENT 'แผนรับนักศึกษาตามรายละเอียดหลักสูตร (จำนวน คน)',
    
    -- Section 6: Admission target groups
    `target_highschool` tinyint(1) DEFAULT 0 COMMENT 'กลุ่มผู้เรียนที่จะเปิดรับ: มัธยมศึกษาตอนปลายหรือเทียบเท่า',
    `target_highschool_count` int(5) DEFAULT NULL COMMENT 'จำนวนนักศึกษาที่จะรับ (มัธยม)',
    `target_diploma` tinyint(1) DEFAULT 0 COMMENT 'กลุ่มผู้เรียนที่จะเปิดรับ: ปวส./อนุปริญญา',
    `target_diploma_count` int(5) DEFAULT NULL COMMENT 'จำนวนนักศึกษาที่จะรับ (ปวส.)',
    
    -- Section 7: Qualifications (single textbox for multiple qualifications)
    `qualification_highschool` text DEFAULT NULL COMMENT 'คุณสมบัติผู้เรียน มัธยมศึกษาตอนปลายหรือเทียบเท่า',
    `qualification_diploma` text DEFAULT NULL COMMENT 'คุณสมบัติผู้เรียน ปวส./อนุปริญญา',
    
    -- Section 8: Current students - High school track
    `current_highschool_year1` int(5) DEFAULT NULL COMMENT 'จำนวนนักศึกษาปัจจุบัน ชั้นปีที่ 1 (มัธยม)',
    `current_highschool_year2` int(5) DEFAULT NULL COMMENT 'จำนวนนักศึกษาปัจจุบัน ชั้นปีที่ 2 (มัธยม)',
    `current_highschool_year3` int(5) DEFAULT NULL COMMENT 'จำนวนนักศึกษาปัจจุบัน ชั้นปีที่ 3 (มัธยม)',
    `current_highschool_year4` int(5) DEFAULT NULL COMMENT 'จำนวนนักศึกษาปัจจุบัน ชั้นปีที่ 4 (มัธยม)',
    `current_highschool_graduated` int(5) DEFAULT NULL COMMENT 'สำเร็จการศึกษา (มัธยม)',
    `current_highschool_remain` int(5) DEFAULT NULL COMMENT 'นักศึกษาค้างชั้น (มัธยม)',
    
    -- Section 8: Current students - Diploma track
    `current_diploma_year1` int(5) DEFAULT NULL COMMENT 'จำนวนนักศึกษาปัจจุบัน ชั้นปีที่ 1 (ปวส.)',
    `current_diploma_year2` int(5) DEFAULT NULL COMMENT 'จำนวนนักศึกษาปัจจุบัน ชั้นปีที่ 2 (ปวส.)',
    `current_diploma_year3` int(5) DEFAULT NULL COMMENT 'จำนวนนักศึกษาปัจจุบัน ชั้นปีที่ 3 (ปวส.)',
    `current_diploma_year4` int(5) DEFAULT NULL COMMENT 'จำนวนนักศึกษาปัจจุบัน ชั้นปีที่ 4 (ปวส.)',
    `current_diploma_graduated` int(5) DEFAULT NULL COMMENT 'สำเร็จการศึกษา (ปวส.)',
    `current_diploma_remain` int(5) DEFAULT NULL COMMENT 'นักศึกษาค้างชั้น (ปวส.)',
    
    -- Section 9: Remaining student development plan
    `remaining_student_plan` text DEFAULT NULL COMMENT 'แผนพัฒนานักศึกษาค้างชั้น: วิธีดำเนินการ',
    `remaining_student_kpi` text DEFAULT NULL COMMENT 'แผนพัฒนานักศึกษาค้างชั้น: ตัวชี้วัดความสำเร็จ',
    
    -- Approvals
    `curriculum_head_approval_date` date DEFAULT NULL COMMENT 'ความเห็นชอบของประธานหลักสูตร เมื่อวันที่',
    `curriculum_head_name` varchar(255) DEFAULT NULL COMMENT 'ชื่อประธานหลักสูตร',
    `dean_approval_date` date DEFAULT NULL COMMENT 'ความเห็นชอบของคณบดี เมื่อวันที่',
    `dean_name` varchar(255) DEFAULT NULL COMMENT 'ชื่อคณบดี',
    
    -- Status and metadata
    `status` enum('draft','submitted','approved','rejected') DEFAULT 'draft' COMMENT 'สถานะ',
    `created_by` int(3) unsigned zerofill DEFAULT NULL COMMENT 'สร้างโดย',
    `updated_by` int(3) unsigned zerofill DEFAULT NULL COMMENT 'แก้ไขโดย',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_year_curriculum` (`academic_year`, `curriculum_id`),
    KEY `fk_admission_curriculum` (`curriculum_id`),
    KEY `fk_admission_faculty` (`faculty_id`),
    KEY `fk_admission_created_by` (`created_by`),
    CONSTRAINT `fk_admission_curriculum` FOREIGN KEY (`curriculum_id`) REFERENCES `curriculum` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_admission_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_admission_created_by` FOREIGN KEY (`created_by`) REFERENCES `user` (`uid`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for responsible teachers (Section 4)
CREATE TABLE IF NOT EXISTS `admission_form_teachers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `admission_form_id` int(11) NOT NULL COMMENT 'FK to student_admission_forms',
    `order_num` int(2) NOT NULL COMMENT 'ลำดับที่ (1-5)',
    `position` varchar(100) DEFAULT NULL COMMENT 'ตำแหน่ง',
    `full_name` varchar(255) DEFAULT NULL COMMENT 'ชื่อ-นามสกุล',
    `user_id` int(3) unsigned zerofill DEFAULT NULL COMMENT 'FK to user table if linked',
    
    -- Publications by year (5 years)
    `pub_year_1` tinyint(1) DEFAULT 0 COMMENT 'มีผลงานปีที่ 1 (ย้อนหลัง 5 ปี)',
    `pub_year_2` tinyint(1) DEFAULT 0 COMMENT 'มีผลงานปีที่ 2',
    `pub_year_3` tinyint(1) DEFAULT 0 COMMENT 'มีผลงานปีที่ 3',
    `pub_year_4` tinyint(1) DEFAULT 0 COMMENT 'มีผลงานปีที่ 4',
    `pub_year_5` tinyint(1) DEFAULT 0 COMMENT 'มีผลงานปีที่ 5',
    `admission_year` int(4) DEFAULT NULL COMMENT 'ปี พ.ศ. ที่รับนักศึกษา',
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_form_order` (`admission_form_id`, `order_num`),
    KEY `fk_teacher_form` (`admission_form_id`),
    KEY `fk_teacher_user` (`user_id`),
    CONSTRAINT `fk_teacher_form` FOREIGN KEY (`admission_form_id`) REFERENCES `student_admission_forms` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_teacher_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`uid`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NOTE: Section 5 (Teacher Publications) uses the existing 'publications' table
-- Publications are fetched by joining through admission_form_teachers.user_id -> publication_authors.uid
-- No separate table needed for this section

-- Insert sample data for testing (optional)
-- You can run this after creating the tables to get sample data
