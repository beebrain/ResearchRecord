-- ===============================================================
-- Update teacher_curriculum_view to handle NULL values correctly
-- ===============================================================
-- Issue: The is_cross_faculty calculation doesn't handle NULL values properly
-- When u.faculty_id is NULL, the comparison u.faculty_id != c.faculty_id returns NULL
-- This should be fixed to properly identify cross-faculty assignments
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
  
  -- Cross-faculty indicator (fixed to handle NULL values)
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
-- Verification
-- ===============================================================
-- Check cross-faculty assignments:
-- SELECT * FROM teacher_curriculum_view WHERE is_cross_faculty = 1;

-- Check teachers without faculty teaching in curriculums:
-- SELECT * FROM teacher_curriculum_view WHERE teacher_faculty_id IS NULL;


