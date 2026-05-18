-- ========================================
-- Update fields for Section 4.2 and 4.3 to support unlimited entries
-- Store as JSON or comma-separated values
-- ========================================

-- Remove old individual fields for retiring teachers (if exists)
-- ALTER TABLE `student_admission_forms` 
-- DROP COLUMN IF EXISTS `retiring_year1`,
-- DROP COLUMN IF EXISTS `retiring_count1`,
-- DROP COLUMN IF EXISTS `retiring_year2`,
-- DROP COLUMN IF EXISTS `retiring_count2`,
-- DROP COLUMN IF EXISTS `retiring_year3`,
-- DROP COLUMN IF EXISTS `retiring_count3`;

-- Add new JSON fields for unlimited entries
ALTER TABLE `student_admission_forms` 
ADD COLUMN `retiring_teachers` TEXT DEFAULT NULL COMMENT 'อาจารย์ที่เกษียณ (JSON: [{"year":2565,"count":2},...])' AFTER `teachers_incomplete_reason`;

ALTER TABLE `student_admission_forms` 
ADD COLUMN `studying_teachers` TEXT DEFAULT NULL COMMENT 'อาจารย์ศึกษาต่อ (JSON: [{"year":2565,"count":1},...])' AFTER `retiring_teachers`;

-- Note: Keep old fields for backward compatibility, but new entries will use JSON fields
