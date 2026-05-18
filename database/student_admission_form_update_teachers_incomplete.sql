-- ========================================
-- Update Section 4.1: Teachers Incomplete
-- Change from single order number to multiple teacher selection
-- ========================================

-- Add new field for storing incomplete teachers as JSON
ALTER TABLE `student_admission_forms` 
ADD COLUMN `teachers_incomplete_teachers` TEXT DEFAULT NULL COMMENT 'อาจารย์ที่ไม่ครบ (JSON: [{"user_id":123,"name":"ชื่อ"},...])' AFTER `teachers_incomplete_reason`;

-- Note: Keep old field teachers_incomplete_order for backward compatibility
