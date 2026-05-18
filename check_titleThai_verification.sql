-- ===============================================================
-- Verification Script: Check titleThai in database vs displayed
-- ===============================================================
-- Purpose: Verify that titleThai values match between:
--   1. user table (source)
--   2. teacher_curriculum_view (after update)
--   3. Displayed values in the form
-- Date: 2026-01-01
-- ===============================================================

-- Check 1: Verify titleThai in user table for specific teachers
-- Based on the image, check these teachers:
-- 1. กิตติ์ คุณกิตติ - should have "ศ.ดร."
-- 2. ชุติมา เมืองด่าน - should have "ดร."
-- 3. ภาคภูมิ โชคทวีพาณิชย์ - should have "ศ.ดร."
-- 4. วรวุฒิ ธุวะคำ - should have "ศ.ดร."
-- 5. วีระศักดิ์ แก้วทรัพย์ - should have "ดร."

SELECT 
    u.uid,
    u.titleThai,
    u.thai_name,
    u.thai_lastname,
    CONCAT(u.thai_name, ' ', u.thai_lastname) as full_name,
    CASE 
        WHEN u.titleThai IS NULL THEN 'NULL'
        WHEN u.titleThai = '' THEN 'EMPTY'
        ELSE u.titleThai
    END as titleThai_status
FROM user u
WHERE (u.thai_name LIKE '%กิตติ์%' AND u.thai_lastname LIKE '%คุณกิตติ%')
   OR (u.thai_name LIKE '%ชุติมา%' AND u.thai_lastname LIKE '%เมืองด่าน%')
   OR (u.thai_name LIKE '%ภาคภูมิ%' AND u.thai_lastname LIKE '%โชคทวีพาณิชย์%')
   OR (u.thai_name LIKE '%วรวุฒิ%' AND u.thai_lastname LIKE '%ธุวะคำ%')
   OR (u.thai_name LIKE '%วีระศักดิ์%' AND u.thai_lastname LIKE '%แก้วทรัพย์%')
ORDER BY u.thai_name;

-- Check 2: Verify titleThai in teacher_curriculum_view (after update)
-- This will show if the view has titleThai field and values
SELECT 
    tcv.teacher_uid,
    tcv.titleThai,
    tcv.thai_name,
    tcv.thai_lastname,
    tcv.curriculum_name,
    CASE 
        WHEN tcv.titleThai IS NULL THEN 'NULL'
        WHEN tcv.titleThai = '' THEN 'EMPTY'
        ELSE tcv.titleThai
    END as titleThai_status
FROM teacher_curriculum_view tcv
WHERE (tcv.thai_name LIKE '%กิตติ์%' AND tcv.thai_lastname LIKE '%คุณกิตติ%')
   OR (tcv.thai_name LIKE '%ชุติมา%' AND tcv.thai_lastname LIKE '%เมืองด่าน%')
   OR (tcv.thai_name LIKE '%ภาคภูมิ%' AND tcv.thai_lastname LIKE '%โชคทวีพาณิชย์%')
   OR (tcv.thai_name LIKE '%วรวุฒิ%' AND tcv.thai_lastname LIKE '%ธุวะคำ%')
   OR (tcv.thai_name LIKE '%วีระศักดิ์%' AND tcv.thai_lastname LIKE '%แก้วทรัพย์%')
ORDER BY tcv.thai_name;

-- Check 3: Compare user table vs teacher_curriculum_view
-- This will show if there are any discrepancies
SELECT 
    u.uid,
    u.titleThai as user_titleThai,
    tcv.titleThai as view_titleThai,
    u.thai_name,
    u.thai_lastname,
    CASE 
        WHEN u.titleThai = tcv.titleThai THEN 'MATCH'
        WHEN u.titleThai IS NULL AND tcv.titleThai IS NULL THEN 'BOTH_NULL'
        WHEN u.titleThai IS NULL AND tcv.titleThai IS NOT NULL THEN 'USER_NULL_VIEW_HAS'
        WHEN u.titleThai IS NOT NULL AND tcv.titleThai IS NULL THEN 'USER_HAS_VIEW_NULL'
        ELSE 'MISMATCH'
    END as comparison_status
FROM user u
LEFT JOIN teacher_curriculum_view tcv ON u.uid = tcv.teacher_uid
WHERE (u.thai_name LIKE '%กิตติ์%' AND u.thai_lastname LIKE '%คุณกิตติ%')
   OR (u.thai_name LIKE '%ชุติมา%' AND u.thai_lastname LIKE '%เมืองด่าน%')
   OR (u.thai_name LIKE '%ภาคภูมิ%' AND u.thai_lastname LIKE '%โชคทวีพาณิชย์%')
   OR (u.thai_name LIKE '%วรวุฒิ%' AND u.thai_lastname LIKE '%ธุวะคำ%')
   OR (u.thai_name LIKE '%วีระศักดิ์%' AND u.thai_lastname LIKE '%แก้วทรัพย์%')
ORDER BY u.thai_name;

-- Check 4: General check - All teachers in teacher_curriculum_view with titleThai
SELECT 
    COUNT(*) as total_teachers,
    COUNT(titleThai) as teachers_with_titleThai,
    COUNT(*) - COUNT(titleThai) as teachers_without_titleThai,
    COUNT(DISTINCT titleThai) as unique_titleThai_values
FROM teacher_curriculum_view
WHERE assignment_status = 1;

-- Check 5: List all unique titleThai values in the view
SELECT 
    titleThai,
    COUNT(*) as count
FROM teacher_curriculum_view
WHERE assignment_status = 1
GROUP BY titleThai
ORDER BY count DESC;
