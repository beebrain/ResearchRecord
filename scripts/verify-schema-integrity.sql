-- Schema integrity checks for rac (run after migrations)
SELECT 'orphan publications.created_by' AS check_name, COUNT(*) AS bad_rows
FROM publications p
LEFT JOIN user u ON p.created_by = u.uid
WHERE p.created_by IS NOT NULL AND u.uid IS NULL

UNION ALL
SELECT 'orphan user.faculty_id', COUNT(*)
FROM user u
LEFT JOIN faculties f ON u.faculty_id = f.id
WHERE u.faculty_id IS NOT NULL AND f.id IS NULL

UNION ALL
SELECT 'orphan teacher_curriculum.teacher (active)' AS check_name, COUNT(*) AS bad_rows
FROM teacher_curriculum tc
LEFT JOIN user u ON tc.teacher_uid = u.uid
WHERE tc.status = 1 AND u.uid IS NULL

UNION ALL
SELECT 'orphan teacher_curriculum.curriculum (active)', COUNT(*)
FROM teacher_curriculum tc
LEFT JOIN curriculum c ON tc.curriculum_id = c.id
WHERE tc.status = 1 AND c.id IS NULL

UNION ALL
SELECT 'roles seeded', (SELECT COUNT(*) FROM roles)
UNION ALL
SELECT 'user_roles rows', (SELECT COUNT(*) FROM user_roles);
