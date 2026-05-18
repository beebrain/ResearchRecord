<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixPublicationView extends Migration
{
    public function up()
    {
        // Drop and recreate publication_view with created_by column
        $this->db->query('DROP VIEW IF EXISTS publication_view');

        $sql = "CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `publication_view` AS SELECT
    p.`id`,
    p.`title`,
    p.`publication_type`,
    p.`source`,
    p.`publication_year`,
    p.`created_at`,
    p.`created_by`,

    -- Creator information
    CONCAT(u.`gf_name`, ' ', u.`gl_name`) as `created_by_name`,

    -- Authors with their curriculum and faculty information
    GROUP_CONCAT(

                CASE
                    WHEN author_curriculum.`name` IS NOT NULL
                    THEN CONCAT(author_curriculum.`name`)
                    ELSE ''
                END

        ORDER BY pa.`author_order`
        SEPARATOR ', '
    ) as `author_curriculum`,

    -- Separate field for just author names (clean)
    GROUP_CONCAT(
        CASE
            WHEN pa.`author_id` IS NOT NULL AND a.`user_uid` IS NOT NULL
            THEN CONCAT(uu.`gf_name`, ' ', uu.`gl_name`)
            ELSE pa.`author_name`
        END
        ORDER BY pa.`author_order`
        SEPARATOR ', '
    ) as `authors_names_en`,

    GROUP_CONCAT(
        CASE
            WHEN pa.`author_id` IS NOT NULL AND a.`user_uid` IS NOT NULL
            THEN CONCAT(uu.`thai_name`, ' ', uu.`thai_lastname`)
            ELSE pa.`author_name`
        END
        ORDER BY pa.`author_order`
        SEPARATOR ', '
    ) as `authors_names_thai`,

    -- Author faculties (for collaboration analysis)
    GROUP_CONCAT(
        DISTINCT author_faculty.`name`
        ORDER BY author_faculty.`name`
        SEPARATOR ', '
    ) as `author_faculties`

FROM `publications` p
LEFT JOIN `user` u ON p.`created_by` = u.`uid`
LEFT JOIN `curriculum` creator_curriculum ON u.`curriculum_id` = creator_curriculum.`id`
LEFT JOIN `faculties` creator_faculty ON creator_curriculum.`faculty_id` = creator_faculty.`id`
LEFT JOIN `publication_authors` pa ON p.`id` = pa.`publication_id`
LEFT JOIN `authors` a ON pa.`author_id` = a.`id`
LEFT JOIN `user` uu ON a.`user_uid` = uu.`uid`
LEFT JOIN `curriculum` author_curriculum ON uu.`curriculum_id` = author_curriculum.`id`
LEFT JOIN `faculties` author_faculty ON author_curriculum.`faculty_id` = author_faculty.`id`

GROUP BY
    p.`id`,
    p.`title`,
    p.`publication_type`,
    p.`source`,
    p.`publication_year`,
    p.`created_at`,
    p.`created_by`,
    u.`gf_name`,
    u.`gl_name`";

        $this->db->query($sql);
    }

    public function down()
    {
        // Revert to old view without created_by
        $this->db->query('DROP VIEW IF EXISTS publication_view');

        $sql = "CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `publication_view` AS SELECT
    p.`id`,
    p.`title`,
    p.`publication_type`,
    p.`source`,
    p.`publication_year`,
    p.`created_at`,

    -- Creator information
    CONCAT(u.`gf_name`, ' ', u.`gl_name`) as `created_by_name`,

    -- Authors with their curriculum and faculty information
    GROUP_CONCAT(

                CASE
                    WHEN author_curriculum.`name` IS NOT NULL
                    THEN CONCAT(author_curriculum.`name`)
                    ELSE ''
                END

        ORDER BY pa.`author_order`
        SEPARATOR ', '
    ) as `author_curriculum`,

    -- Separate field for just author names (clean)
    GROUP_CONCAT(
        CASE
            WHEN pa.`author_id` IS NOT NULL AND a.`user_uid` IS NOT NULL
            THEN CONCAT(uu.`gf_name`, ' ', uu.`gl_name`)
            ELSE pa.`author_name`
        END
        ORDER BY pa.`author_order`
        SEPARATOR ', '
    ) as `authors_names_en`,

    GROUP_CONCAT(
        CASE
            WHEN pa.`author_id` IS NOT NULL AND a.`user_uid` IS NOT NULL
            THEN CONCAT(uu.`thai_name`, ' ', uu.`thai_lastname`)
            ELSE pa.`author_name`
        END
        ORDER BY pa.`author_order`
        SEPARATOR ', '
    ) as `authors_names_thai`,

    -- Author faculties (for collaboration analysis)
    GROUP_CONCAT(
        DISTINCT author_faculty.`name`
        ORDER BY author_faculty.`name`
        SEPARATOR ', '
    ) as `author_faculties`

FROM `publications` p
LEFT JOIN `user` u ON p.`created_by` = u.`uid`
LEFT JOIN `curriculum` creator_curriculum ON u.`curriculum_id` = creator_curriculum.`id`
LEFT JOIN `faculties` creator_faculty ON creator_curriculum.`faculty_id` = creator_faculty.`id`
LEFT JOIN `publication_authors` pa ON p.`id` = pa.`publication_id`
LEFT JOIN `authors` a ON pa.`author_id` = a.`id`
LEFT JOIN `user` uu ON a.`user_uid` = uu.`uid`
LEFT JOIN `curriculum` author_curriculum ON uu.`curriculum_id` = author_curriculum.`id`
LEFT JOIN `faculties` author_faculty ON author_curriculum.`faculty_id` = author_faculty.`id`

GROUP BY
    p.`id`,
    p.`title`,
    p.`publication_type`,
    p.`source`,
    p.`publication_year`,
    p.`created_at`,
    u.`gf_name`,
    u.`gl_name`";

        $this->db->query($sql);
    }
}
