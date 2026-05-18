-- ========================================
-- Add missing fields for Student Admission Form
-- This script will add only the fields that don't exist
-- ========================================

-- Add retiring_teachers (ignore error if exists)
ALTER TABLE `student_admission_forms` 
ADD COLUMN `retiring_teachers` TEXT DEFAULT NULL COMMENT 'อาจารย์ที่เกษียณ (JSON: [{"year":2565,"count":2},...])' AFTER `teachers_incomplete_reason`;

-- Add studying_teachers (ignore error if exists)
ALTER TABLE `student_admission_forms` 
ADD COLUMN `studying_teachers` TEXT DEFAULT NULL COMMENT 'อาจารย์ศึกษาต่อ (JSON: [{"year":2565,"count":1},...])' AFTER `retiring_teachers`;

-- Note: If you get "Duplicate column name" error, it means the column already exists and you can ignore it.
