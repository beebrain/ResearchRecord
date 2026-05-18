-- ===============================================================
-- Database Migration: Teacher-Curriculum Many-to-Many Structure
-- ===============================================================
-- Purpose: Separate faculty affiliation from curriculum teaching
-- Date: 2025-11-12
--
-- Changes:
-- 1. Create teacher_curriculum junction table
-- 2. Migrate existing curriculum_id data
-- 3. Make faculty_id NOT NULL for teachers (future enforcement)
-- ===============================================================

-- Step 1: Create teacher_curriculum junction table
-- This table maps teachers to the curriculums they teach
CREATE TABLE IF NOT EXISTS `teacher_curriculum` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `teacher_uid` INT(3) UNSIGNED NOT NULL COMMENT 'UID of teacher from user table',
  `curriculum_id` INT(11) NOT NULL COMMENT 'ID of curriculum from curriculum table',
  `role` ENUM('instructor', 'coordinator', 'assistant') DEFAULT 'instructor' COMMENT 'Role of teacher in curriculum',
  `is_primary` TINYINT(1) DEFAULT 0 COMMENT 'Is this the primary curriculum for the teacher?',
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When was teacher assigned to this curriculum',
  `status` TINYINT(1) DEFAULT 1 COMMENT '1=active, 0=inactive',
  `notes` TEXT DEFAULT NULL COMMENT 'Additional notes about this assignment',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_teacher_curriculum` (`teacher_uid`, `curriculum_id`),
  KEY `idx_teacher` (`teacher_uid`),
  KEY `idx_curriculum` (`curriculum_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_teacher_curriculum_user`
    FOREIGN KEY (`teacher_uid`) REFERENCES `user` (`uid`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_teacher_curriculum_curriculum`
    FOREIGN KEY (`curriculum_id`) REFERENCES `curriculum` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Maps teachers to curriculums they teach (many-to-many)';

-- ===============================================================
-- Step 2: Migrate existing data from user.curriculum_id
-- ===============================================================
-- Only migrate teachers who currently have a curriculum assigned
INSERT INTO `teacher_curriculum` (
  `teacher_uid`,
  `curriculum_id`,
  `role`,
  `is_primary`,
  `assigned_at`,
  `status`
)
SELECT
  u.uid,
  u.curriculum_id,
  'instructor' as role,
  1 as is_primary, -- Mark as primary curriculum since it was the only one
  u.created_at as assigned_at,
  1 as status
FROM `user` u
WHERE u.curriculum_id IS NOT NULL
  AND (u.user_type = 'TEACHER' OR u.user_type IS NULL)
  AND NOT EXISTS (
    SELECT 1 FROM `teacher_curriculum` tc
    WHERE tc.teacher_uid = u.uid AND tc.curriculum_id = u.curriculum_id
  );

-- ===============================================================
-- Step 3: Add indexes to user table for better performance
-- ===============================================================
-- Index for faculty_id if not exists
CREATE INDEX IF NOT EXISTS `idx_user_faculty` ON `user` (`faculty_id`);
CREATE INDEX IF NOT EXISTS `idx_user_type` ON `user` (`user_type`);

-- ===============================================================
-- Step 4: Create view for easy teacher-curriculum queries
-- ===============================================================
CREATE OR REPLACE VIEW `teacher_curriculum_view` AS
SELECT
  tc.id as assignment_id,
  tc.teacher_uid,
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
  -- Cross-faculty indicator (handles NULL values correctly)
  -- If teacher has no faculty (NULL) or faculty is different from curriculum's faculty, it's cross-faculty
  CASE
    WHEN u.faculty_id IS NULL THEN 1  -- Teacher without faculty is considered cross-faculty
    WHEN c.faculty_id IS NULL THEN 0  -- Curriculum should always have faculty, but handle edge case
    WHEN u.faculty_id != c.faculty_id THEN 1  -- Different faculties = cross-faculty
    ELSE 0  -- Same faculty = not cross-faculty
  END as is_cross_faculty
FROM `teacher_curriculum` tc
INNER JOIN `user` u ON tc.teacher_uid = u.uid
LEFT JOIN `faculties` uf ON u.faculty_id = uf.id
INNER JOIN `curriculum` c ON tc.curriculum_id = c.id
LEFT JOIN `faculties` cf ON c.faculty_id = cf.id
WHERE tc.status = 1 AND u.active = 1;

-- ===============================================================
-- Step 5: Verification Queries
-- ===============================================================
-- Run these queries to verify migration success:

-- Count migrated records
-- SELECT COUNT(*) as migrated_count FROM teacher_curriculum;

-- Check for teachers without faculty (should be addressed)
-- SELECT COUNT(*) as teachers_without_faculty
-- FROM user
-- WHERE (user_type = 'TEACHER' OR user_type IS NULL)
--   AND faculty_id IS NULL;

-- View cross-faculty teaching assignments
-- SELECT * FROM teacher_curriculum_view WHERE is_cross_faculty = 1;

-- ===============================================================
-- Step 6: Optional - Cleanup old curriculum_id column (DO NOT RUN YET!)
-- ===============================================================
-- IMPORTANT: Only run this after verifying the new structure works
-- and updating all application code to use teacher_curriculum table

-- ALTER TABLE `user` CHANGE COLUMN `curriculum_id` `curriculum_id_deprecated` INT(11) NULL
--   COMMENT 'DEPRECATED: Use teacher_curriculum table instead';

-- After full migration and testing:
-- ALTER TABLE `user` DROP COLUMN `curriculum_id_deprecated`;

-- ===============================================================
-- ROLLBACK PLAN (if needed)
-- ===============================================================
-- If migration fails, run these commands:

-- DROP VIEW IF EXISTS `teacher_curriculum_view`;
-- DROP TABLE IF EXISTS `teacher_curriculum`;
-- (user.curriculum_id remains unchanged)

-- ===============================================================
-- Notes for Application Updates:
-- ===============================================================
/*
1. Update UserModel.php:
   - Keep getUsersWithCurriculum() but join teacher_curriculum instead
   - Add getTeacherCurriculums($teacherUid) method
   - Add assignTeacherToCurriculum($teacherUid, $curriculumId) method
   - Add removeTeacherFromCurriculum($teacherUid, $curriculumId) method

2. Update curriculum-user management UI:
   - Allow selecting multiple curriculums per teacher
   - Show primary curriculum indicator
   - Show cross-faculty assignments with visual indicator
   - Add role selector (instructor/coordinator/assistant)

3. Enforce business rules:
   - Teachers MUST have faculty_id (add validation)
   - Teachers can teach in any curriculum (no faculty restriction)
   - At least one curriculum should be marked as primary

4. Data integrity:
   - Add application-level validation for faculty_id on teachers
   - Consider adding CHECK constraint in future MySQL version:
     ALTER TABLE user ADD CONSTRAINT check_teacher_faculty
     CHECK (user_type != 'TEACHER' OR faculty_id IS NOT NULL);
*/
