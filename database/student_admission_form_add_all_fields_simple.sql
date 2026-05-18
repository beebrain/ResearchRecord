-- ========================================
-- Add all missing fields for Student Admission Form (Simple Version)
-- Run this script if the dynamic version doesn't work
-- ========================================

-- Check and add retiring_teachers
ALTER TABLE `student_admission_forms` 
ADD COLUMN `retiring_teachers` TEXT DEFAULT NULL COMMENT 'อาจารย์ที่เกษียณ (JSON: [{"year":2565,"count":2},...])' AFTER `teachers_incomplete_reason`;

-- Check and add studying_teachers  
ALTER TABLE `student_admission_forms` 
ADD COLUMN `studying_teachers` TEXT DEFAULT NULL COMMENT 'อาจารย์ศึกษาต่อ (JSON: [{"year":2565,"count":1},...])' AFTER `retiring_teachers`;

-- Check and add teachers_incomplete_teachers
ALTER TABLE `student_admission_forms` 
ADD COLUMN `teachers_incomplete_teachers` TEXT DEFAULT NULL COMMENT 'อาจารย์ที่ไม่ครบ (JSON: [{"user_id":123,"name":"ชื่อ"},...])' AFTER `teachers_incomplete_reason`;

-- Note: If you get "Duplicate column name" error, it means the column already exists and you can ignore it.
