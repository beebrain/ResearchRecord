-- Update user 254 (panarat.han@live.uru.ac.th) to be Faculty Admin
-- Managing Faculty ID 2

UPDATE `user` 
SET 
    `role` = 'faculty_admin',
    `admin` = 1,
    `managed_faculties` = '[2]'  -- Managing Faculty ID 2 only
WHERE `uid` = 254;

-- Verify the update
SELECT 
    uid,
    email,
    CONCAT(gf_name, ' ', gl_name) AS full_name,
    role,
    admin,
    faculty_id,
    managed_faculties
FROM `user`
WHERE uid = 254;
