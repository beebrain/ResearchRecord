-- ทดสอบการนับ Total Authors
-- ตรวจสอบว่าไม่นับซ้ำอาจารย์ที่มีหลายผลงาน

-- 1. Super Admin: นับอาจารย์ทั้งมหาวิทยาลัยที่มีผลงาน
-- (ไม่จำกัดคณะ)
SELECT 
    u.uid,
    u.email,
    CONCAT(u.gf_name, ' ', u.gl_name) AS full_name,
    u.faculty_id,
    COUNT(DISTINCT pa.publication_id) as publication_count
FROM user u
INNER JOIN teacher_curriculum tc ON tc.teacher_uid = u.uid AND tc.status = 1
INNER JOIN publication_authors pa ON (
    pa.uid = u.uid 
    OR pa.author_email = u.email
    OR EXISTS (
        SELECT 1 FROM authors a 
        WHERE a.id = pa.author_id AND a.user_uid = u.uid
    )
)
WHERE u.active = 1
GROUP BY u.uid, u.email, u.gf_name, u.gl_name, u.faculty_id
ORDER BY publication_count DESC;

-- นับจำนวนอาจารย์ทั้งหมด (Super Admin)
SELECT COUNT(DISTINCT u.uid) as total_authors
FROM user u
INNER JOIN teacher_curriculum tc ON tc.teacher_uid = u.uid AND tc.status = 1
INNER JOIN publication_authors pa ON (
    pa.uid = u.uid 
    OR pa.author_email = u.email
    OR EXISTS (
        SELECT 1 FROM authors a 
        WHERE a.id = pa.author_id AND a.user_uid = u.uid
    )
)
WHERE u.active = 1;

-- 2. Faculty Admin: นับอาจารย์ในคณะ ID = 1 เท่านั้น
-- (ตัวอย่างสำหรับ Faculty ID 1)
SELECT 
    u.uid,
    u.email,
    CONCAT(u.gf_name, ' ', u.gl_name) AS full_name,
    c.faculty_id,
    f.name as faculty_name,
    c.name as curriculum_name,
    COUNT(DISTINCT pa.publication_id) as publication_count
FROM user u
INNER JOIN teacher_curriculum tc ON tc.teacher_uid = u.uid AND tc.status = 1
INNER JOIN curriculum c ON c.id = tc.curriculum_id AND c.faculty_id = 1  -- Faculty ID 1
INNER JOIN faculties f ON f.id = c.faculty_id
INNER JOIN publication_authors pa ON (
    pa.uid = u.uid 
    OR pa.author_email = u.email
    OR EXISTS (
        SELECT 1 FROM authors a 
        WHERE a.id = pa.author_id AND a.user_uid = u.uid
    )
)
INNER JOIN publication_view pv ON pv.id = pa.publication_id AND pv.faculty_id = 1  -- Faculty ID 1
WHERE u.active = 1
GROUP BY u.uid, u.email, u.gf_name, u.gl_name, c.faculty_id, f.name, c.name
ORDER BY publication_count DESC;

-- นับจำนวนอาจารย์ในคณะ ID = 1 (Faculty Admin)
SELECT COUNT(DISTINCT u.uid) as total_authors_faculty_1
FROM user u
INNER JOIN teacher_curriculum tc ON tc.teacher_uid = u.uid AND tc.status = 1
INNER JOIN curriculum c ON c.id = tc.curriculum_id AND c.faculty_id = 1
INNER JOIN publication_authors pa ON (
    pa.uid = u.uid 
    OR pa.author_email = u.email
    OR EXISTS (
        SELECT 1 FROM authors a 
        WHERE a.id = pa.author_id AND a.user_uid = u.uid
    )
)
INNER JOIN publication_view pv ON pv.id = pa.publication_id AND pv.faculty_id = 1
WHERE u.active = 1;

-- 3. ตรวจสอบอาจารย์ที่มีหลายผลงาน (ไม่ควรนับซ้ำ)
-- แสดงอาจารย์ที่มีมากกว่า 1 ผลงาน เพื่อยืนยันว่าไม่นับซ้ำ
SELECT 
    u.uid,
    u.email,
    CONCAT(u.gf_name, ' ', u.gl_name) AS full_name,
    COUNT(DISTINCT pa.publication_id) as publication_count,
    GROUP_CONCAT(DISTINCT pa.publication_id ORDER BY pa.publication_id) as publication_ids
FROM user u
INNER JOIN teacher_curriculum tc ON tc.teacher_uid = u.uid AND tc.status = 1
INNER JOIN publication_authors pa ON (
    pa.uid = u.uid 
    OR pa.author_email = u.email
    OR EXISTS (
        SELECT 1 FROM authors a 
        WHERE a.id = pa.author_id AND a.user_uid = u.uid
    )
)
WHERE u.active = 1
GROUP BY u.uid, u.email, u.gf_name, u.gl_name
HAVING COUNT(DISTINCT pa.publication_id) > 1
ORDER BY publication_count DESC
LIMIT 10;

-- 4. ตรวจสอบว่า teacher_curriculum มี status = 1 เท่านั้น
SELECT 
    tc.teacher_uid,
    u.email,
    CONCAT(u.gf_name, ' ', u.gl_name) AS full_name,
    c.name as curriculum_name,
    tc.status,
    tc.is_primary
FROM teacher_curriculum tc
INNER JOIN user u ON u.uid = tc.teacher_uid
INNER JOIN curriculum c ON c.id = tc.curriculum_id
WHERE tc.status = 1
ORDER BY tc.teacher_uid;

-- 5. สรุป: จำนวนอาจารย์แยกตามคณะ
SELECT 
    f.id as faculty_id,
    f.name as faculty_name,
    COUNT(DISTINCT u.uid) as teacher_count_with_publications
FROM faculties f
LEFT JOIN curriculum c ON c.faculty_id = f.id
LEFT JOIN teacher_curriculum tc ON tc.curriculum_id = c.id AND tc.status = 1
LEFT JOIN user u ON u.uid = tc.teacher_uid AND u.active = 1
LEFT JOIN publication_authors pa ON (
    pa.uid = u.uid 
    OR pa.author_email = u.email
    OR EXISTS (
        SELECT 1 FROM authors a 
        WHERE a.id = pa.author_id AND a.user_uid = u.uid
    )
)
WHERE pa.publication_id IS NOT NULL  -- มีผลงานเท่านั้น
GROUP BY f.id, f.name
ORDER BY f.id;
