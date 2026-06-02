-- Recreate views after user.email is PRIMARY KEY (no uid).
DROP VIEW IF EXISTS `publication_view`;
DROP VIEW IF EXISTS `teacher_curriculum_view`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `teacher_curriculum_view` AS
SELECT
    tc.id AS assignment_id,
    tc.teacher_email,
    tc.curriculum_id,
    tc.role,
    tc.is_primary,
    tc.assigned_at,
    tc.status AS assignment_status,
    u.thai_name,
    u.thai_lastname,
    u.gf_name,
    u.email,
    u.user_type,
    u.faculty_id AS teacher_faculty_id,
    uf.name AS teacher_faculty_name,
    uf.code AS teacher_faculty_code,
    c.name AS curriculum_name,
    c.code AS curriculum_code,
    c.degree_level,
    c.faculty_id AS curriculum_faculty_id,
    cf.name AS curriculum_faculty_name,
    cf.code AS curriculum_faculty_code,
    (CASE WHEN u.faculty_id <> c.faculty_id THEN 1 ELSE 0 END) AS is_cross_faculty
FROM teacher_curriculum tc
INNER JOIN user u ON u.email = tc.teacher_email
LEFT JOIN faculties uf ON uf.id = u.faculty_id
INNER JOIN curriculum c ON c.id = tc.curriculum_id
LEFT JOIN faculties cf ON cf.id = c.faculty_id
WHERE tc.status = 1 AND u.active = 1;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `publication_view` AS
SELECT
    p.id,
    p.title,
    p.publication_type,
    p.source,
    p.publication_year,
    p.publication_month,
    p.volume,
    p.pages,
    p.doi,
    p.isbn,
    p.abstract,
    p.keywords,
    p.notes,
    p.created_at,
    p.created_by_email,
    p.approve,
    CONCAT(COALESCE(u.gf_name, ''), ' ', COALESCE(u.gl_name, '')) AS created_by_name,
    u.faculty_id AS created_by_faculty_id,
    creator_faculty.name AS created_by_faculty_name,
    u.faculty_id AS faculty_id,
    GROUP_CONCAT(
        DISTINCT CASE
            WHEN email_user.email IS NOT NULL THEN (
                SELECT GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ')
                FROM teacher_curriculum tc
                INNER JOIN curriculum c ON tc.curriculum_id = c.id
                WHERE tc.teacher_email = email_user.email AND tc.status = 1
            )
            ELSE NULL
        END
        ORDER BY pa.author_order SEPARATOR ', '
    ) AS author_curriculum,
    GROUP_CONCAT(
        CASE
            WHEN email_user.email IS NOT NULL
            THEN CONCAT(COALESCE(email_user.gf_name, ''), ' ', COALESCE(email_user.gl_name, ''))
            ELSE pa.author_name
        END
        ORDER BY pa.author_order SEPARATOR ', '
    ) AS authors_names_en,
    GROUP_CONCAT(
        CASE
            WHEN email_user.email IS NOT NULL
            THEN CONCAT(COALESCE(email_user.thai_name, ''), ' ', COALESCE(email_user.thai_lastname, ''))
            ELSE pa.author_name
        END
        ORDER BY pa.author_order SEPARATOR ', '
    ) AS authors_names_thai,
    GROUP_CONCAT(
        DISTINCT CASE
            WHEN email_user.email IS NOT NULL THEN COALESCE(
                email_user_faculty.name,
                (SELECT GROUP_CONCAT(DISTINCT f.name SEPARATOR ', ')
                 FROM teacher_curriculum tc
                 INNER JOIN curriculum c ON tc.curriculum_id = c.id
                 INNER JOIN faculties f ON c.faculty_id = f.id
                 WHERE tc.teacher_email = email_user.email AND tc.status = 1)
            )
            ELSE NULL
        END
        ORDER BY email_user_faculty.name SEPARATOR ', '
    ) AS author_faculties,
    GROUP_CONCAT(DISTINCT email_user.email ORDER BY pa.author_order SEPARATOR ',') AS author_emails,
    GROUP_CONCAT(
        DISTINCT (
            SELECT GROUP_CONCAT(DISTINCT tc.curriculum_id SEPARATOR ',')
            FROM teacher_curriculum tc
            WHERE tc.teacher_email = email_user.email AND tc.status = 1
        ) SEPARATOR ','
    ) AS author_curriculum_ids,
    GROUP_CONCAT(
        DISTINCT CASE
            WHEN email_user.email IS NOT NULL THEN CONCAT_WS(',',
                email_user.faculty_id,
                (SELECT GROUP_CONCAT(DISTINCT c.faculty_id SEPARATOR ',')
                 FROM teacher_curriculum tc
                 INNER JOIN curriculum c ON tc.curriculum_id = c.id
                 WHERE tc.teacher_email = email_user.email AND tc.status = 1)
            )
            ELSE NULL
        END SEPARATOR ','
    ) AS author_faculty_ids
FROM publications p
LEFT JOIN user u ON p.created_by_email = u.email
LEFT JOIN faculties creator_faculty ON u.faculty_id = creator_faculty.id
LEFT JOIN publication_authors pa ON p.id = pa.publication_id
LEFT JOIN user email_user ON email_user.email = LOWER(TRIM(pa.author_email))
    AND pa.author_email IS NOT NULL AND TRIM(pa.author_email) <> ''
LEFT JOIN faculties email_user_faculty ON email_user.faculty_id = email_user_faculty.id
GROUP BY
    p.id, p.title, p.publication_type, p.source, p.publication_year, p.publication_month,
    p.volume, p.pages, p.doi, p.isbn, p.abstract, p.keywords, p.notes,
    p.created_at, p.created_by_email, p.approve,
    u.gf_name, u.gl_name, u.faculty_id, creator_faculty.name;
