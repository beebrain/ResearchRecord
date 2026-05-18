-- อัพเดท Pisit Nakjai ให้เป็น Super Admin
-- ใช้เฉพาะ role column, ไม่ใช้ admin column

-- 1. ตรวจสอบข้อมูลปัจจุบันของ Pisit
SELECT 
    uid,
    email,
    CONCAT(gf_name, ' ', gl_name) as full_name,
    role,
    admin,
    active
FROM user
WHERE email LIKE '%pisit%' AND email LIKE '%nakjai%'
LIMIT 1;

-- 2. อัพเดท role เป็น super_admin
UPDATE user 
SET role = 'super_admin'
WHERE email LIKE '%pisit%' AND email LIKE '%nakjai%';

-- 3. ตรวจสอบหลังอัพเดท
SELECT 
    uid,
    email,
    CONCAT(gf_name, ' ', gl_name) as full_name,
    role,
    admin,
    active
FROM user
WHERE email LIKE '%pisit%' AND email LIKE '%nakjai%'
LIMIT 1;
