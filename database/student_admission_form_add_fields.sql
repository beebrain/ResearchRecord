-- ========================================
-- Add missing fields for Section 4.2 and 4.3
-- ========================================

-- Add third set for retiring teachers (Section 4.2)
-- Note: Run these one by one if column already exists error occurs
ALTER TABLE `student_admission_forms` 
ADD COLUMN `retiring_year3` int(4) DEFAULT NULL COMMENT 'ปี พ.ศ. ที่มีอาจารย์เกษียณ (ปีที่สาม)' AFTER `retiring_count2`;

ALTER TABLE `student_admission_forms` 
ADD COLUMN `retiring_count3` int(3) DEFAULT NULL COMMENT 'จำนวนอาจารย์เกษียณ (ปีที่สาม)' AFTER `retiring_year3`;

-- Add fields for teachers pursuing further studies (Section 4.3)
ALTER TABLE `student_admission_forms` 
ADD COLUMN `studying_year1` int(4) DEFAULT NULL COMMENT 'ปี พ.ศ. ที่มีอาจารย์ศึกษาต่อ (ปีแรก)' AFTER `retiring_count3`;

ALTER TABLE `student_admission_forms` 
ADD COLUMN `studying_count1` int(3) DEFAULT NULL COMMENT 'จำนวนอาจารย์ศึกษาต่อ (ปีแรก)' AFTER `studying_year1`;

ALTER TABLE `student_admission_forms` 
ADD COLUMN `studying_year2` int(4) DEFAULT NULL COMMENT 'ปี พ.ศ. ที่มีอาจารย์ศึกษาต่อ (ปีที่สอง)' AFTER `studying_count1`;

ALTER TABLE `student_admission_forms` 
ADD COLUMN `studying_count2` int(3) DEFAULT NULL COMMENT 'จำนวนอาจารย์ศึกษาต่อ (ปีที่สอง)' AFTER `studying_year2`;

ALTER TABLE `student_admission_forms` 
ADD COLUMN `studying_year3` int(4) DEFAULT NULL COMMENT 'ปี พ.ศ. ที่มีอาจารย์ศึกษาต่อ (ปีที่สาม)' AFTER `studying_count2`;

ALTER TABLE `student_admission_forms` 
ADD COLUMN `studying_count3` int(3) DEFAULT NULL COMMENT 'จำนวนอาจารย์ศึกษาต่อ (ปีที่สาม)' AFTER `studying_year3`;
