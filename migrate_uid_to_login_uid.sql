-- ===============================================================
-- Migration: Change from uid to login_uid
-- Purpose: Change all references from user.uid to user.login_uid
-- Date: 2025-11-17
-- ===============================================================

-- Step 1: Add teacher_login_uid column to teacher_curriculum table
ALTER TABLE `teacher_curriculum` 
ADD COLUMN `teacher_login_uid` VARCHAR(255) NULL AFTER `teacher_uid`;

-- Step 2: Populate teacher_login_uid from user table
UPDATE `teacher_curriculum` tc
INNER JOIN `user` u ON tc.teacher_uid = u.uid
SET tc.teacher_login_uid = u.login_uid
WHERE u.login_uid IS NOT NULL;

-- Step 3: Drop old foreign key constraint
ALTER TABLE `teacher_curriculum` 
DROP FOREIGN KEY IF EXISTS `fk_teacher_curriculum_user`;

-- Step 4: Drop old unique constraint
ALTER TABLE `teacher_curriculum` 
DROP INDEX IF EXISTS `unique_teacher_curriculum`;

-- Step 5: Drop old index
ALTER TABLE `teacher_curriculum` 
DROP INDEX IF EXISTS `idx_teacher`;

-- Step 6: Make teacher_login_uid NOT NULL (after data migration)
-- ALTER TABLE `teacher_curriculum` 
-- MODIFY COLUMN `teacher_login_uid` VARCHAR(255) NOT NULL;

-- Step 7: Add new unique constraint on (teacher_login_uid, curriculum_id)
ALTER TABLE `teacher_curriculum` 
ADD UNIQUE KEY `unique_teacher_curriculum_login_uid` (`teacher_login_uid`, `curriculum_id`);

-- Step 8: Add new index on teacher_login_uid
ALTER TABLE `teacher_curriculum` 
ADD INDEX `idx_teacher_login_uid` (`teacher_login_uid`);

-- Step 9: Add new foreign key constraint
ALTER TABLE `teacher_curriculum` 
ADD CONSTRAINT `fk_teacher_curriculum_user_login_uid`
FOREIGN KEY (`teacher_login_uid`) REFERENCES `user` (`login_uid`)
ON DELETE CASCADE ON UPDATE CASCADE;

-- Step 10: Drop old teacher_uid column (after verifying everything works)
-- ALTER TABLE `teacher_curriculum` 
-- DROP COLUMN `teacher_uid`;

-- ===============================================================
-- Update teacher_curriculum_view to use login_uid
-- ===============================================================
CREATE OR REPLACE VIEW `teacher_curriculum_view` AS
SELECT
  tc.id as assignment_id,
  tc.teacher_login_uid as teacher_login_uid,
  tc.curriculum_id,
  tc.role,
  tc.is_primary,
  tc.assigned_at,
  tc.status as assignment_status,
  
  -- User information
  u.thai_name,
  u.thai_lastname,
  u.gf_name,
  u.email,
  u.user_type,
  u.faculty_id as teacher_faculty_id,
  uf.name as teacher_faculty_name,
  uf.code as teacher_faculty_code,
  
  -- Curriculum information
  c.name as curriculum_name,
  c.code as curriculum_code,
  c.degree_level,
  c.faculty_id as curriculum_faculty_id,
  cf.name as curriculum_faculty_name,
  cf.code as curriculum_faculty_code,
  
  -- Cross-faculty indicator (fixed to handle NULL values)
  CASE
    WHEN u.faculty_id IS NULL THEN 1  -- Teacher without faculty is considered cross-faculty
    WHEN c.faculty_id IS NULL THEN 0  -- Curriculum should always have faculty, but handle edge case
    WHEN u.faculty_id != c.faculty_id THEN 1  -- Different faculties = cross-faculty
    ELSE 0  -- Same faculty = not cross-faculty
  END as is_cross_faculty
FROM `teacher_curriculum` tc
INNER JOIN `user` u ON tc.teacher_login_uid = u.login_uid
LEFT JOIN `faculties` uf ON u.faculty_id = uf.id
INNER JOIN `curriculum` c ON tc.curriculum_id = c.id
LEFT JOIN `faculties` cf ON c.faculty_id = cf.id
WHERE tc.status = 1 AND u.active = 1;

-- ===============================================================
-- Verification Queries
-- ===============================================================
-- Check if all records have teacher_login_uid populated
-- SELECT COUNT(*) as total, 
--        COUNT(teacher_login_uid) as with_login_uid,
--        COUNT(teacher_uid) as with_uid
-- FROM teacher_curriculum;

-- Check for orphaned records
-- SELECT COUNT(*) as orphaned
-- FROM teacher_curriculum tc
-- LEFT JOIN user u ON tc.teacher_login_uid = u.login_uid
-- WHERE u.login_uid IS NULL;

