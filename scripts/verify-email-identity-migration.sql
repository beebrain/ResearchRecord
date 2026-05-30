-- RR email identity migration — verification queries
-- Usage: mysql -uroot -p rac < scripts/verify-email-identity-migration.sql

SET @sample_email := 'pisit.nak@live.uru.ac.th';

-- ========== BASELINE ==========
SELECT '=== BASELINE: user.email integrity ===' AS section;
SELECT COUNT(*) AS total_users FROM user;
SELECT COUNT(*) AS null_or_empty_email
FROM user WHERE email IS NULL OR TRIM(email) = '';
SELECT COUNT(*) AS duplicate_normalized_emails
FROM (
    SELECT LOWER(TRIM(email)) AS e, COUNT(*) AS c
    FROM user
    GROUP BY LOWER(TRIM(email))
    HAVING c > 1
) d;

SELECT '=== BASELINE: cv_sections ===' AS section;
SELECT COUNT(*) AS cv_sections_total FROM cv_sections;
SELECT COUNT(*) AS cv_sections_orphan_uid
FROM cv_sections cs
LEFT JOIN user u ON u.uid = cs.user_uid
WHERE u.uid IS NULL;

SELECT '=== BASELINE: publication_authors breakdown ===' AS section;
SELECT
    SUM(author_email IS NOT NULL AND TRIM(author_email) != '' AND uid IS NOT NULL) AS both,
    SUM(author_email IS NOT NULL AND TRIM(author_email) != '' AND uid IS NULL) AS email_only,
    SUM((author_email IS NULL OR TRIM(author_email) = '') AND uid IS NOT NULL) AS uid_only,
    SUM((author_email IS NULL OR TRIM(author_email) = '') AND uid IS NULL) AS neither
FROM publication_authors;

-- ========== PHASE 2: owner_email_norm (run after migration) ==========
SELECT '=== PHASE 2: cv_sections.owner_email_norm ===' AS section;
SELECT COUNT(*) AS has_column
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'cv_sections'
  AND COLUMN_NAME = 'owner_email_norm';

SELECT COUNT(*) AS null_owner_email
FROM cv_sections
WHERE owner_email_norm IS NULL OR TRIM(owner_email_norm) = '';

SELECT COUNT(*) AS owner_email_mismatch
FROM cv_sections cs
JOIN user u ON u.uid = cs.user_uid
WHERE LOWER(TRIM(cs.owner_email_norm)) <> LOWER(TRIM(u.email));

-- ========== PUBLICATION_PARITY (Phase 1) ==========
-- Compare publication IDs: by user.uid path vs direct author_email
SELECT '=== PUBLICATION_PARITY: sample user ===' AS section;

SET @uid := (SELECT uid FROM user WHERE LOWER(TRIM(email)) = LOWER(TRIM(@sample_email)) LIMIT 1);

SELECT COUNT(DISTINCT pa.publication_id) AS via_author_email_only
FROM publication_authors pa
WHERE LOWER(TRIM(pa.author_email)) = LOWER(TRIM(@sample_email));

SELECT COUNT(DISTINCT pa.publication_id) AS via_uid_on_pa
FROM publication_authors pa
WHERE pa.uid = @uid;

-- Publications only in email set but not uid set (co-author email-only rows)
SELECT pa.publication_id, p.title
FROM publication_authors pa
JOIN publications p ON p.id = pa.publication_id
WHERE LOWER(TRIM(pa.author_email)) = LOWER(TRIM(@sample_email))
  AND (pa.uid IS NULL OR pa.uid <> @uid)
LIMIT 10;

-- ========== CV ORPHANS ==========
SELECT '=== CV_ORPHANS ===' AS section;
SELECT cs.id, cs.user_uid, cs.owner_email_norm, cs.title
FROM cv_sections cs
LEFT JOIN user u ON u.uid = cs.user_uid
WHERE u.uid IS NULL
LIMIT 10;
