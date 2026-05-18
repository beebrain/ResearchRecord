-- ========================================
-- Add all missing fields for Student Admission Form
-- Run this script to add all required fields
-- ========================================

-- Add retiring_teachers field (if not exists)
SET @dbname = DATABASE();
SET @tablename = 'student_admission_forms';
SET @columnname = 'retiring_teachers';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` TEXT DEFAULT NULL COMMENT ''อาจารย์ที่เกษียณ (JSON: [{"year":2565,"count":2},...])'' AFTER `teachers_incomplete_reason`;')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add studying_teachers field (if not exists)
SET @columnname = 'studying_teachers';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` TEXT DEFAULT NULL COMMENT ''อาจารย์ศึกษาต่อ (JSON: [{"year":2565,"count":1},...])'' AFTER `retiring_teachers`;')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add teachers_incomplete_teachers field (if not exists)
SET @columnname = 'teachers_incomplete_teachers';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` TEXT DEFAULT NULL COMMENT ''อาจารย์ที่ไม่ครบ (JSON: [{"user_id":123,"name":"ชื่อ"},...])'' AFTER `teachers_incomplete_reason`;')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Simple version (if the above doesn't work, use this):
-- ALTER TABLE `student_admission_forms` 
-- ADD COLUMN IF NOT EXISTS `retiring_teachers` TEXT DEFAULT NULL COMMENT 'อาจารย์ที่เกษียณ (JSON: [{"year":2565,"count":2},...])' AFTER `teachers_incomplete_reason`,
-- ADD COLUMN IF NOT EXISTS `studying_teachers` TEXT DEFAULT NULL COMMENT 'อาจารย์ศึกษาต่อ (JSON: [{"year":2565,"count":1},...])' AFTER `retiring_teachers`,
-- ADD COLUMN IF NOT EXISTS `teachers_incomplete_teachers` TEXT DEFAULT NULL COMMENT 'อาจารย์ที่ไม่ครบ (JSON: [{"user_id":123,"name":"ชื่อ"},...])' AFTER `teachers_incomplete_reason`;
