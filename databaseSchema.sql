-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.4.27-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.5.0.6677
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table researchrecord.authors
CREATE TABLE IF NOT EXISTS `authors` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `user_uid` int(3) unsigned zerofill DEFAULT NULL,
  `created_by` int(3) unsigned zerofill NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `fk_authors_user` (`user_uid`),
  KEY `fk_authors_created_by` (`created_by`),
  CONSTRAINT `fk_authors_created_by` FOREIGN KEY (`created_by`) REFERENCES `user` (`uid`) ON DELETE CASCADE,
  CONSTRAINT `fk_authors_user` FOREIGN KEY (`user_uid`) REFERENCES `user` (`uid`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table researchrecord.curriculum
CREATE TABLE IF NOT EXISTS `curriculum` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `faculty_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `degree_level` enum('bachelor','master','doctoral') NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `FK_curriculum_faculties` (`faculty_id`),
  CONSTRAINT `FK_curriculum_faculties` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table researchrecord.faculties
CREATE TABLE IF NOT EXISTS `faculties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(10) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table researchrecord.migrations
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table researchrecord.publications
CREATE TABLE IF NOT EXISTS `publications` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `abstract` text DEFAULT NULL,
  `publication_type` enum('journal','book','proceedings','thesis','report','other') NOT NULL,
  `source` varchar(500) NOT NULL,
  `publication_year` int(11) DEFAULT NULL,
  `publication_month` int(11) DEFAULT NULL,
  `volume` varchar(50) DEFAULT NULL,
  `pages` varchar(100) DEFAULT NULL,
  `doi` varchar(255) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `keywords` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(3) unsigned zerofill NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `referencelink` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table researchrecord.publication_authors
CREATE TABLE IF NOT EXISTS `publication_authors` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `publication_id` bigint(20) NOT NULL,
  `author_name` varchar(255) NOT NULL,
  `author_email` varchar(255) DEFAULT NULL,
  `author_affiliation` varchar(500) DEFAULT NULL,
  `author_id` bigint(20) DEFAULT NULL,
  `author_order` int(11) NOT NULL,
  `uid` int(3) DEFAULT NULL,
  `corresponding` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_pub_authors_publication` (`publication_id`),
  KEY `fk_pub_authors_author` (`author_id`),
  CONSTRAINT `fk_pub_authors_author` FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pub_authors_publication` FOREIGN KEY (`publication_id`) REFERENCES `publications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for view researchrecord.publication_view
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `publication_view` (
	`id` BIGINT(20) NOT NULL,
	`title` TEXT NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`publication_type` ENUM('journal','book','proceedings','thesis','report','other') NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`source` VARCHAR(500) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`publication_year` INT(11) NULL,
	`created_at` TIMESTAMP NOT NULL,
	`created_by` INT(3) UNSIGNED ZEROFILL NOT NULL,
	`created_by_name` VARCHAR(511) NULL COLLATE 'utf8_general_ci',
	`author_curriculum` MEDIUMTEXT NULL COLLATE 'utf8mb4_general_ci',
	`authors_names_en` MEDIUMTEXT NULL COLLATE 'utf8mb4_unicode_ci',
	`authors_names_thai` MEDIUMTEXT NULL COLLATE 'utf8mb4_unicode_ci',
	`author_faculties` MEDIUMTEXT NULL COLLATE 'utf8mb4_general_ci'
) ENGINE=MyISAM;

-- Dumping structure for table researchrecord.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table researchrecord.user
CREATE TABLE IF NOT EXISTS `user` (
  `uid` int(3) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `login_uid` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `gf_name` varchar(255) DEFAULT NULL,
  `gl_name` varchar(255) DEFAULT NULL,
  `major` varchar(255) DEFAULT NULL,
  `thai_name` varchar(255) DEFAULT NULL,
  `thai_lastname` varchar(255) DEFAULT NULL,
  `profile_picture` text NOT NULL,
  `profile_customer` varchar(255) NOT NULL,
  `created_at` varchar(255) NOT NULL,
  `titleThai` varchar(255) NOT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  `active` int(1) unsigned zerofill NOT NULL DEFAULT 0,
  `admin` int(11) NOT NULL DEFAULT 0,
  `role` enum('user','faculty_admin','super_admin') NOT NULL DEFAULT 'user',
  `managed_faculties` text DEFAULT NULL COMMENT 'JSON array of faculty IDs for faculty admins',
  `edoc` int(11) NOT NULL DEFAULT 0,
  `curriculum_id` int(11) DEFAULT NULL,
  `faculty_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `user_type` varchar(50) DEFAULT NULL COMMENT 'TEACHER, STUDENT, STAFF, etc.',
  `degree` varchar(50) DEFAULT NULL COMMENT 'BACHELOR, MASTER, DOCTORAL',
  `gender` varchar(20) DEFAULT NULL,
  `nickname` varchar(100) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `citizen_id` varchar(20) DEFAULT NULL COMMENT 'Thai citizen ID (13 digits)',
  `passport_id` varchar(20) DEFAULT NULL COMMENT 'Passport number for international users',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_user_curriculum` (`curriculum_id`),
  CONSTRAINT `fk_user_curriculum` FOREIGN KEY (`curriculum_id`) REFERENCES `curriculum` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=249 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table researchrecord.user_roles
CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(3) unsigned NOT NULL,
  `role_id` int(11) unsigned NOT NULL,
  `faculty_id` int(11) DEFAULT NULL COMMENT 'Optional: Scope role to specific faculty (for faculty_admin role)',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `assigned_by` int(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_roles_user_id_foreign` (`user_id`),
  KEY `user_roles_role_id_foreign` (`role_id`),
  KEY `user_roles_faculty_id_foreign` (`faculty_id`),
  CONSTRAINT `user_roles_faculty_id_foreign` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `user` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for view researchrecord.publication_view
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `publication_view`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `publication_view` AS SELECT
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
    u.`gl_name` ;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
