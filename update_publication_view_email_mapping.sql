-- ================================================================
-- UPDATE publication_view to map authors by EMAIL ONLY
-- ================================================================
-- 
-- เปลี่ยนจากการ map ผ่าน UID (a.user_uid) เป็นการ map ผ่าน Email เท่านั้น
-- โดยใช้ pa.author_email ไป match กับ user.email หรือ authors.email
-- ================================================================

DROP VIEW IF EXISTS `publication_view`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `publication_view` AS 
SELECT
    p.`id`,
    p.`title`,
    p.`publication_type`,
    p.`source`,
    p.`publication_year`,
    p.`publication_month`,
    p.`volume`,
    p.`pages`,
    p.`doi`,
    p.`isbn`,
    p.`abstract`,
    p.`keywords`,
    p.`notes`,
    p.`created_at`,
    p.`created_by`,
    p.`approve`,

    -- Creator information
    CONCAT(COALESCE(u.`gf_name`, ''), ' ', COALESCE(u.`gl_name`, '')) as `created_by_name`,
    u.`faculty_id` as `created_by_faculty_id`,
    creator_faculty.`name` as `created_by_faculty_name`,
    -- For backward compatibility: faculty_id (uses created_by_faculty_id)
    -- This field is used by AdminController for filtering publications by faculty
    u.`faculty_id` as `faculty_id`,

    -- Authors with their curriculum information (map through EMAIL)
    GROUP_CONCAT(
        DISTINCT
        CASE
            WHEN email_user.`uid` IS NOT NULL THEN
                -- ดึงหลักสูตรจาก teacher_curriculum ของอาจารย์ (map ผ่าน email)
                (SELECT GROUP_CONCAT(DISTINCT c.`name` SEPARATOR ', ')
                 FROM `teacher_curriculum` tc
                 INNER JOIN `curriculum` c ON tc.`curriculum_id` = c.`id`
                 WHERE tc.`teacher_uid` = email_user.`uid` AND tc.`status` = 1)
            ELSE NULL
        END
        ORDER BY pa.`author_order`
        SEPARATOR ', '
    ) as `author_curriculum`,

    -- Author names (English) - map through EMAIL
    GROUP_CONCAT(
        CASE
            WHEN email_user.`uid` IS NOT NULL
            THEN CONCAT(COALESCE(email_user.`gf_name`, ''), ' ', COALESCE(email_user.`gl_name`, ''))
            ELSE pa.`author_name`
        END
        ORDER BY pa.`author_order`
        SEPARATOR ', '
    ) as `authors_names_en`,

    -- Author names (Thai) - map through EMAIL
    GROUP_CONCAT(
        CASE
            WHEN email_user.`uid` IS NOT NULL
            THEN CONCAT(COALESCE(email_user.`thai_name`, ''), ' ', COALESCE(email_user.`thai_lastname`, ''))
            ELSE pa.`author_name`
        END
        ORDER BY pa.`author_order`
        SEPARATOR ', '
    ) as `authors_names_thai`,

    -- Author faculties (map through EMAIL)
    GROUP_CONCAT(
        DISTINCT
        CASE
            WHEN email_user.`uid` IS NOT NULL THEN
                -- ดึงคณะจาก user.faculty_id และ teacher_curriculum (map ผ่าน email)
                COALESCE(email_user_faculty.`name`,
                    (SELECT GROUP_CONCAT(DISTINCT f.`name` SEPARATOR ', ')
                     FROM `teacher_curriculum` tc
                     INNER JOIN `curriculum` c ON tc.`curriculum_id` = c.`id`
                     INNER JOIN `faculties` f ON c.`faculty_id` = f.`id`
                     WHERE tc.`teacher_uid` = email_user.`uid` AND tc.`status` = 1)
                )
            ELSE NULL
        END
        ORDER BY email_user_faculty.`name`
        SEPARATOR ', '
    ) as `author_faculties`,

    -- Author UIDs (map through EMAIL) - สำหรับการ filter ใน application
    GROUP_CONCAT(
        DISTINCT email_user.`uid`
        ORDER BY pa.`author_order`
        SEPARATOR ','
    ) as `author_uids`,

    -- Curriculum IDs ของ authors (map through EMAIL)
    GROUP_CONCAT(
        DISTINCT
        (SELECT GROUP_CONCAT(DISTINCT tc.`curriculum_id` SEPARATOR ',')
         FROM `teacher_curriculum` tc
         WHERE tc.`teacher_uid` = email_user.`uid` AND tc.`status` = 1)
        SEPARATOR ','
    ) as `author_curriculum_ids`,

    -- Faculty IDs ของ authors (map through EMAIL)
    GROUP_CONCAT(
        DISTINCT
        CASE
            WHEN email_user.`uid` IS NOT NULL THEN
                CONCAT_WS(',',
                    email_user.`faculty_id`,
                    (SELECT GROUP_CONCAT(DISTINCT c.`faculty_id` SEPARATOR ',')
                     FROM `teacher_curriculum` tc
                     INNER JOIN `curriculum` c ON tc.`curriculum_id` = c.`id`
                     WHERE tc.`teacher_uid` = email_user.`uid` AND tc.`status` = 1)
                )
            ELSE NULL
        END
        SEPARATOR ','
    ) as `author_faculty_ids`

FROM `publications` p
LEFT JOIN `user` u ON p.`created_by` = u.`uid`
LEFT JOIN `faculties` creator_faculty ON u.`faculty_id` = creator_faculty.`id`
LEFT JOIN `publication_authors` pa ON p.`id` = pa.`publication_id`
-- Map authors through EMAIL ONLY (not through authors.user_uid)
-- Use subquery to find user by email: first try user.email, then authors.email
LEFT JOIN `user` email_user ON (
    email_user.`uid` = COALESCE(
        -- First: Try to match pa.author_email with user.email directly
        (SELECT u1.`uid` 
         FROM `user` u1 
         WHERE pa.`author_email` IS NOT NULL 
           AND pa.`author_email` != '' 
           AND LOWER(TRIM(pa.`author_email`)) = LOWER(TRIM(u1.`email`))
         LIMIT 1),
        -- Second: Try to match pa.author_email with authors.email, then get user_uid
        (SELECT a1.`user_uid` 
         FROM `authors` a1 
         WHERE pa.`author_email` IS NOT NULL 
           AND pa.`author_email` != '' 
           AND LOWER(TRIM(pa.`author_email`)) = LOWER(TRIM(a1.`email`))
           AND a1.`user_uid` IS NOT NULL
         LIMIT 1)
    )
)
LEFT JOIN `faculties` email_user_faculty ON email_user.`faculty_id` = email_user_faculty.`id`

GROUP BY
    p.`id`,
    p.`title`,
    p.`publication_type`,
    p.`source`,
    p.`publication_year`,
    p.`publication_month`,
    p.`volume`,
    p.`pages`,
    p.`doi`,
    p.`isbn`,
    p.`abstract`,
    p.`keywords`,
    p.`notes`,
    p.`created_at`,
    p.`created_by`,
    p.`approve`,
    u.`gf_name`,
    u.`gl_name`,
    u.`faculty_id`,
    creator_faculty.`name`;

-- ================================================================
-- ตรวจสอบผลลัพธ์
-- ================================================================
-- SELECT * FROM publication_view LIMIT 10;
