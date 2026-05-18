-- Create Test Admin Users for Different Levels
-- ตรวจสอบว่า User Login เป็น Admin แต่ละระดับจะเห็นสถิติที่ถูกต้องหรือไม่

-- 1. Update Pisit to be Super Admin
UPDATE `user` 
SET 
    `role` = 'super_admin',
    `admin` = 1
WHERE `uid` = (SELECT uid FROM (SELECT uid FROM `user` WHERE email = 'pisit.nak@live.uru.ac.th') AS tmp)
LIMIT 1;

-- 2. Create/Update a Faculty Admin user
-- Assuming we need a user to be Faculty Admin for Faculty ID 1
UPDATE `user` 
SET 
    `role` = 'faculty_admin',
    `admin` = 1,
    `managed_faculties` = '["1"]',  -- Managing Faculty ID 1
    `faculty_id` = 1
WHERE `uid` = (
    SELECT uid FROM (
        SELECT uid FROM `user` 
        WHERE email != 'pisit.nak@live.uru.ac.th' 
        AND faculty_id = 1 
        LIMIT 1
    ) AS tmp
)
LIMIT 1;

-- 3. Keep another user as Regular User (no admin)
-- This is already the default state

-- Verify the changes
SELECT 
    uid,
    email,
    CONCAT(gf_name, ' ', gl_name) AS full_name,
    role,
    admin,
    faculty_id,
    managed_faculties
FROM `user`
WHERE role IS NOT NULL OR admin = 1
ORDER BY role DESC, admin DESC;
