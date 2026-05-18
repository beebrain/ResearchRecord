-- ตรวจสอบข้อมูล Pisit Nakjai
SELECT 
    uid,
    email,
    CONCAT(gf_name, ' ', gl_name) as full_name,
    admin,
    role,
    active,
    faculty_id
FROM user
WHERE email LIKE '%pisit%' OR gf_name LIKE '%Pisit%' OR gl_name LIKE '%Nakjai%'
LIMIT 5;

-- อัพเดท Pisit ให้เป็น Super Admin (ถ้ายังไม่ใช่)
-- UPDATE user 
-- SET admin = 1, role = 'super_admin' 
-- WHERE email LIKE '%pisit%nakjai%';
