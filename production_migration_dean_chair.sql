-- =====================================================
-- Production Migration Script
-- เพิ่มคอลัมน์ dean_id และ chair_id สำหรับ Production
-- =====================================================
-- 
-- สิ่งที่ต้องทำ:
-- 1. เพิ่มคอลัมน์ dean_id ในตาราง faculties
-- 2. เพิ่มคอลัมน์ chair_id ในตาราง curriculum
-- 3. เพิ่ม foreign key constraints
--
-- วิธีรัน: 
-- - ผ่าน phpMyAdmin: Copy และ Paste SQL นี้แล้วรัน
-- - ผ่าน MySQL Command Line: mysql -u username -p database_name < production_migration_dean_chair.sql
-- =====================================================

-- =====================================================
-- 1. เพิ่มคอลัมน์ dean_id ในตาราง faculties
-- =====================================================

-- ตรวจสอบว่าคอลัมน์มีอยู่แล้วหรือไม่
SET @dbname = DATABASE();
SET @tablename = "faculties";
SET @columnname = "dean_id";

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column dean_id already exists in faculties table.' as message;",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " INT(3) UNSIGNED NULL COMMENT 'Foreign key to user.uid - คณบดีของคณะ' AFTER status;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- =====================================================
-- 2. เพิ่ม Foreign Key สำหรับ dean_id
-- =====================================================

SET @fk_name = "fk_faculties_dean";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (CONSTRAINT_NAME = @fk_name)
  ) > 0,
  "SELECT 'Foreign key fk_faculties_dean already exists.' as message;",
  CONCAT("ALTER TABLE ", @tablename, " ADD CONSTRAINT ", @fk_name, " FOREIGN KEY (", @columnname, ") REFERENCES user(uid) ON DELETE SET NULL ON UPDATE CASCADE;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- =====================================================
-- 3. เพิ่มคอลัมน์ chair_id ในตาราง curriculum
-- =====================================================

SET @tablename = "curriculum";
SET @columnname = "chair_id";

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column chair_id already exists in curriculum table.' as message;",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " INT(3) UNSIGNED NULL COMMENT 'Foreign key to user.uid - ประธานหลักสูตร' AFTER status;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- =====================================================
-- 4. เพิ่ม Foreign Key สำหรับ chair_id
-- =====================================================

SET @fk_name = "fk_curriculum_chair";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (CONSTRAINT_NAME = @fk_name)
  ) > 0,
  "SELECT 'Foreign key fk_curriculum_chair already exists.' as message;",
  CONCAT("ALTER TABLE ", @tablename, " ADD CONSTRAINT ", @fk_name, " FOREIGN KEY (", @columnname, ") REFERENCES user(uid) ON DELETE SET NULL ON UPDATE CASCADE;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- =====================================================
-- 5. ตรวจสอบผลลัพธ์
-- =====================================================

SELECT 
    'Migration completed!' as status,
    'Please verify the following:' as note;

-- ตรวจสอบคอลัมน์ dean_id
SELECT 
    'faculties.dean_id' as column_name,
    CASE 
        WHEN COUNT(*) > 0 THEN 'EXISTS ✓'
        ELSE 'MISSING ✗'
    END as status
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @dbname
  AND TABLE_NAME = 'faculties'
  AND COLUMN_NAME = 'dean_id';

-- ตรวจสอบคอลัมน์ chair_id
SELECT 
    'curriculum.chair_id' as column_name,
    CASE 
        WHEN COUNT(*) > 0 THEN 'EXISTS ✓'
        ELSE 'MISSING ✗'
    END as status
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @dbname
  AND TABLE_NAME = 'curriculum'
  AND COLUMN_NAME = 'chair_id';

-- ตรวจสอบ Foreign Keys
SELECT 
    CONSTRAINT_NAME as foreign_key_name,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = @dbname
  AND CONSTRAINT_NAME IN ('fk_faculties_dean', 'fk_curriculum_chair')
  AND REFERENCED_TABLE_NAME IS NOT NULL;

