-- Debug script to check teacher_curriculum data
-- Run this in phpMyAdmin to see what's missing

-- 1. Check if teacher_curriculum table exists and has data
SELECT 'teacher_curriculum table count' as check_name, COUNT(*) as count FROM teacher_curriculum;

-- 2. Check if teacher_curriculum_view exists and has data  
SELECT 'teacher_curriculum_view count' as check_name, COUNT(*) as count FROM teacher_curriculum_view;

-- 3. Check sample data from teacher_curriculum
SELECT 'Sample teacher_curriculum data' as check_name;
SELECT * FROM teacher_curriculum LIMIT 5;

-- 4. Check sample data from teacher_curriculum_view
SELECT 'Sample teacher_curriculum_view data' as check_name;
SELECT * FROM teacher_curriculum_view LIMIT 5;

-- 5. Check which users have curriculum assignments
SELECT 'Users with curriculum assignments' as check_name;
SELECT 
    u.uid,
    CONCAT(u.thai_name, ' ', u.thai_lastname) as teacher_name,
    tc.curriculum_id,
    c.name as curriculum_name,
    tc.is_primary,
    tc.status
FROM user u
LEFT JOIN teacher_curriculum tc ON u.uid = tc.teacher_uid
LEFT JOIN curriculum c ON tc.curriculum_id = c.id
WHERE u.active = 1
ORDER BY u.uid
LIMIT 10;

-- 6. Check for View definition
SHOW CREATE VIEW teacher_curriculum_view;
