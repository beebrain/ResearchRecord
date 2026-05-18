-- สร้าง/อัพเดท Test Users สำหรับทดสอบ Dashboard Permissions

-- 1. อัพเดท Pisit เป็น Super Admin เพื่อทดสอบ
UPDATE `user` 
SET 
    `role` = 'super_admin',
    `admin` = 1,
    `managed_faculties` = NULL
WHERE email = 'pisit.nak@live.uru.ac.th';

-- 2. หา User ที่มี faculty_id = 1 และตั้งเป็น Faculty Admin
-- (ต้อง run query นี้ก่อนเพื่อหา UID)
SELECT uid, email, gf_name, gl_name, faculty_id, role
FROM `user`
WHERE faculty_id = 1 
  AND email != 'pisit.nak@live.uru.ac.th'
  AND active = 1
LIMIT 5;

-- 3. หลังจากได้ UID แล้ว ให้อัพเดทเป็น Faculty Admin (แทนที่ <UID_HERE> ด้วย UID จริง)
-- UPDATE `user` 
-- SET 
--     `role` = 'faculty_admin',
--     `admin` = 1,
--     `managed_faculties` = '[1]'  -- Managing Faculty ID 1 only
-- WHERE uid = <UID_HERE>;

-- 4. หา Regular User (ไม่มี admin) เพื่อทดสอบ
SELECT uid, email, gf_name, gl_name, faculty_id, role, admin
FROM `user`
WHERE (admin IS NULL OR admin = 0)
  AND active = 1
LIMIT 5;

-- ตรวจสอบผลลัพธ์
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
ORDER BY 
    CASE 
        WHEN role = 'super_admin' THEN 1
        WHEN role = 'faculty_admin' THEN 2
        ELSE 3
    END,
    email;
