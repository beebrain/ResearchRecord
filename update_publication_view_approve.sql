-- ================================================================
-- UPDATE publication_view to include approve field
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

    -- Authors with their curriculum information (รวมข้อมูลจาก teacher_curriculum)
    GROUP_CONCAT(
        DISTINCT
        CASE
            WHEN author_user.`uid` IS NOT NULL THEN
                -- ดึงหลักสูตรจาก teacher_curriculum ของอาจารย์
                (SELECT GROUP_CONCAT(DISTINCT c.`name` SEPARATOR ', ')
                 FROM `teacher_curriculum` tc
                 INNER JOIN `curriculum` c ON tc.`curriculum_id` = c.`id`
                 WHERE tc.`teacher_uid` = author_user.`uid` AND tc.`status` = 1)
            ELSE NULL
        END
        ORDER BY pa.`author_order`
        SEPARATOR ', '
    ) as `author_curriculum`,

    -- Author names (English)
    GROUP_CONCAT(
        CASE
            WHEN pa.`author_id` IS NOT NULL AND a.`user_uid` IS NOT NULL
            THEN CONCAT(COALESCE(author_user.`gf_name`, ''), ' ', COALESCE(author_user.`gl_name`, ''))
            ELSE pa.`author_name`
        END
        ORDER BY pa.`author_order`
        SEPARATOR ', '
    ) as `authors_names_en`,

    -- Author names (Thai)
    GROUP_CONCAT(
        CASE
            WHEN pa.`author_id` IS NOT NULL AND a.`user_uid` IS NOT NULL
            THEN CONCAT(COALESCE(author_user.`thai_name`, ''), ' ', COALESCE(author_user.`thai_lastname`, ''))
            ELSE pa.`author_name`
        END
        ORDER BY pa.`author_order`
        SEPARATOR ', '
    ) as `authors_names_thai`,

    -- Author faculties (รวมจากทั้ง user.faculty_id และ curriculum)
    GROUP_CONCAT(
        DISTINCT
        CASE
            WHEN author_user.`uid` IS NOT NULL THEN
                -- ดึงคณะจาก user.faculty_id และ teacher_curriculum
                COALESCE(author_user_faculty.`name`,
                    (SELECT GROUP_CONCAT(DISTINCT f.`name` SEPARATOR ', ')
                     FROM `teacher_curriculum` tc
                     INNER JOIN `curriculum` c ON tc.`curriculum_id` = c.`id`
                     INNER JOIN `faculties` f ON c.`faculty_id` = f.`id`
                     WHERE tc.`teacher_uid` = author_user.`uid` AND tc.`status` = 1)
                )
            ELSE NULL
        END
        ORDER BY author_user_faculty.`name`
        SEPARATOR ', '
    ) as `author_faculties`,

    -- Author UIDs (สำหรับการ filter ใน application)
    GROUP_CONCAT(
        DISTINCT author_user.`uid`
        ORDER BY pa.`author_order`
        SEPARATOR ','
    ) as `author_uids`,

    -- Curriculum IDs ของ authors (สำหรับการ filter ตาม faculty)
    GROUP_CONCAT(
        DISTINCT
        (SELECT GROUP_CONCAT(DISTINCT tc.`curriculum_id` SEPARATOR ',')
         FROM `teacher_curriculum` tc
         WHERE tc.`teacher_uid` = author_user.`uid` AND tc.`status` = 1)
        SEPARATOR ','
    ) as `author_curriculum_ids`,

    -- Faculty IDs ของ authors (รวมจากทั้ง user.faculty_id และ curriculum)
    GROUP_CONCAT(
        DISTINCT
        CASE
            WHEN author_user.`uid` IS NOT NULL THEN
                CONCAT_WS(',',
                    author_user.`faculty_id`,
                    (SELECT GROUP_CONCAT(DISTINCT c.`faculty_id` SEPARATOR ',')
                     FROM `teacher_curriculum` tc
                     INNER JOIN `curriculum` c ON tc.`curriculum_id` = c.`id`
                     WHERE tc.`teacher_uid` = author_user.`uid` AND tc.`status` = 1)
                )
            ELSE NULL
        END
        SEPARATOR ','
    ) as `author_faculty_ids`

FROM `publications` p
LEFT JOIN `user` u ON p.`created_by` = u.`uid`
LEFT JOIN `faculties` creator_faculty ON u.`faculty_id` = creator_faculty.`id`
LEFT JOIN `publication_authors` pa ON p.`id` = pa.`publication_id`
LEFT JOIN `authors` a ON pa.`author_id` = a.`id`
LEFT JOIN `user` author_user ON a.`user_uid` = author_user.`uid`
LEFT JOIN `faculties` author_user_faculty ON author_user.`faculty_id` = author_user_faculty.`id`

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
