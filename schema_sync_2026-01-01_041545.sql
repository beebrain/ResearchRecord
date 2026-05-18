-- ============================================
-- Database Schema Sync SQL (SCHEMA ONLY - NO DATA)
-- Generated: 2026-01-01 04:16:00
-- Source: LOCAL (researchrecord)
-- Target: SERVER (rac)
-- ============================================

-- WARNING: Review all statements before executing!
-- This script only modifies SCHEMA (structure), not DATA

SET FOREIGN_KEY_CHECKS = 0;

-- Modify column 'id' in table 'admission_form_teachers'
ALTER TABLE `admission_form_teachers` MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT;

-- Modify column 'admission_form_id' in table 'admission_form_teachers'
ALTER TABLE `admission_form_teachers` MODIFY COLUMN `admission_form_id` int(11) NOT NULL COMMENT 'FK to student_admission_forms';

-- Modify column 'order_num' in table 'admission_form_teachers'
ALTER TABLE `admission_form_teachers` MODIFY COLUMN `order_num` int(2) NOT NULL COMMENT 'เธฅเธณเธเธฑเธเธเธตเน (1-5)';

-- Modify column 'admission_year' in table 'admission_form_teachers'
ALTER TABLE `admission_form_teachers` MODIFY COLUMN `admission_year` int(4) NULL COMMENT 'เธเธต เธ.เธจ. เธเธตเนเธฃเธฑเธเธเธฑเธเธจเธถเธเธฉเธฒ';

-- Modify column 'id' in table 'authors'
ALTER TABLE `authors` MODIFY COLUMN `id` bigint(20) NOT NULL AUTO_INCREMENT;

-- Modify column 'created_at' in table 'authors'
ALTER TABLE `authors` MODIFY COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Modify column 'id' in table 'curriculum'
ALTER TABLE `curriculum` MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT;

-- Modify column 'faculty_id' in table 'curriculum'
ALTER TABLE `curriculum` MODIFY COLUMN `faculty_id` int(11) NOT NULL;

-- Modify column 'chair_id' in table 'curriculum'
ALTER TABLE `curriculum` MODIFY COLUMN `chair_id` int(3) unsigned NULL COMMENT 'Foreign key to user.uid - ประธานหลักสูตร';

-- Modify column 'created_at' in table 'curriculum'
ALTER TABLE `curriculum` MODIFY COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Modify column 'id' in table 'cv_entries'
ALTER TABLE `cv_entries` MODIFY COLUMN `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

-- Modify column 'section_id' in table 'cv_entries'
ALTER TABLE `cv_entries` MODIFY COLUMN `section_id` bigint(20) unsigned NOT NULL;

-- Modify column 'sort_order' in table 'cv_entries'
ALTER TABLE `cv_entries` MODIFY COLUMN `sort_order` int(11) NOT NULL DEFAULT '0';

-- Modify column 'id' in table 'cv_sections'
ALTER TABLE `cv_sections` MODIFY COLUMN `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

-- Modify column 'sort_order' in table 'cv_sections'
ALTER TABLE `cv_sections` MODIFY COLUMN `sort_order` int(11) NOT NULL DEFAULT '0';

-- Modify column 'id' in table 'faculties'
ALTER TABLE `faculties` MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT;

-- Modify column 'dean_id' in table 'faculties'
ALTER TABLE `faculties` MODIFY COLUMN `dean_id` int(3) unsigned NULL COMMENT 'Foreign key to user.uid - คณบดีของคณะ';

-- Modify column 'created_at' in table 'faculties'
ALTER TABLE `faculties` MODIFY COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Modify column 'id' in table 'migrations'
ALTER TABLE `migrations` MODIFY COLUMN `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

-- Modify column 'time' in table 'migrations'
ALTER TABLE `migrations` MODIFY COLUMN `time` int(11) NOT NULL;

-- Modify column 'batch' in table 'migrations'
ALTER TABLE `migrations` MODIFY COLUMN `batch` int(10) unsigned NOT NULL;

-- Modify column 'id' in table 'publication_authors'
ALTER TABLE `publication_authors` MODIFY COLUMN `id` bigint(20) NOT NULL AUTO_INCREMENT;

-- Modify column 'publication_id' in table 'publication_authors'
ALTER TABLE `publication_authors` MODIFY COLUMN `publication_id` bigint(20) NOT NULL;

-- Modify column 'author_id' in table 'publication_authors'
ALTER TABLE `publication_authors` MODIFY COLUMN `author_id` bigint(20) NULL;

-- Modify column 'author_order' in table 'publication_authors'
ALTER TABLE `publication_authors` MODIFY COLUMN `author_order` int(11) NOT NULL;

-- Modify column 'uid' in table 'publication_authors'
ALTER TABLE `publication_authors` MODIFY COLUMN `uid` int(11) NULL;

-- Modify column 'corresponding' in table 'publication_authors'
ALTER TABLE `publication_authors` MODIFY COLUMN `corresponding` int(11) NULL;

-- NOTE: publication_view is a VIEW, not a table. Views are replaced using CREATE OR REPLACE VIEW (see below).

-- Modify column 'id' in table 'publications'
ALTER TABLE `publications` MODIFY COLUMN `id` bigint(20) NOT NULL AUTO_INCREMENT;

-- Modify column 'publication_year' in table 'publications'
ALTER TABLE `publications` MODIFY COLUMN `publication_year` int(11) NULL;

-- Modify column 'publication_month' in table 'publications'
ALTER TABLE `publications` MODIFY COLUMN `publication_month` int(11) NULL;

-- Add column 'orcid_put_code' to table 'publications'
ALTER TABLE `publications` ADD COLUMN `orcid_put_code` varchar(50) NULL COMMENT 'ORCID put_code for duplicate checking';

-- Modify column 'created_at' in table 'publications'
ALTER TABLE `publications` MODIFY COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Modify column 'updated_at' in table 'publications'
ALTER TABLE `publications` MODIFY COLUMN `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP();

-- Modify column 'approve' in table 'publications'
ALTER TABLE `publications` MODIFY COLUMN `approve` int(11) NULL;

-- Add index 'idx_orcid_put_code' to table 'publications'
CREATE INDEX `idx_orcid_put_code` ON `publications` (`orcid_put_code`);

-- Modify column 'id' in table 'roles'
ALTER TABLE `roles` MODIFY COLUMN `id` int(10) unsigned NOT NULL AUTO_INCREMENT;

-- Modify column 'created_at' in table 'roles'
ALTER TABLE `roles` MODIFY COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP();

-- Modify column 'id' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT;

-- Modify column 'academic_year' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `academic_year` int(4) NOT NULL COMMENT 'เธเธตเธเธฒเธฃเธจเธถเธเธฉเธฒ (เธ.เธจ.) เนเธเนเธ 2568, 2569';

-- Modify column 'curriculum_id' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `curriculum_id` int(11) NOT NULL COMMENT 'เธซเธฅเธฑเธเธชเธนเธเธฃ';

-- Modify column 'faculty_id' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `faculty_id` int(11) NOT NULL COMMENT 'เธเธเธฐ';

-- Modify column 'curriculum_version_year' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `curriculum_version_year` int(4) NULL COMMENT 'เธเธเธฑเธเธเธต เธ.เธจ.';

-- Modify column 'quality_assessment_year1' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `quality_assessment_year1` int(4) NULL COMMENT 'เธเธตเธเธฒเธฃเธจเธถเธเธฉเธฒ (เธเธตเนเธฃเธ)';

-- Modify column 'quality_assessment_year2' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `quality_assessment_year2` int(4) NULL COMMENT 'เธเธตเธเธฒเธฃเธจเธถเธเธฉเธฒ (เธเธตเธเธตเนเธชเธญเธ)';

-- Modify column 'teachers_incomplete_order' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `teachers_incomplete_order` int(2) NULL COMMENT 'เธฅเธณเธเธฑเธเธเธตเนเนเธกเนเธเธฃเธ';

-- Modify column 'retiring_year1' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `retiring_year1` int(4) NULL COMMENT 'เธเธต เธ.เธจ. เธเธตเนเธกเธตเธญเธฒเธเธฒเธฃเธขเนเนเธเธฉเธตเธขเธ (เธเธตเนเธฃเธ)';

-- Modify column 'retiring_count1' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `retiring_count1` int(3) NULL COMMENT 'เธเธณเธเธงเธเธญเธฒเธเธฒเธฃเธขเนเนเธเธฉเธตเธขเธ (เธเธตเนเธฃเธ)';

-- Modify column 'retiring_year2' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `retiring_year2` int(4) NULL COMMENT 'เธเธต เธ.เธจ. เธเธตเนเธกเธตเธญเธฒเธเธฒเธฃเธขเนเนเธเธฉเธตเธขเธ (เธเธตเธเธตเนเธชเธญเธ)';

-- Modify column 'retiring_count2' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `retiring_count2` int(3) NULL COMMENT 'เธเธณเธเธงเธเธญเธฒเธเธฒเธฃเธขเนเนเธเธฉเธตเธขเธ (เธเธตเธเธตเนเธชเธญเธ)';

-- Modify column 'major_count' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `major_count` int(3) NULL COMMENT 'เธเธณเธเธงเธเธงเธดเธเธฒเนเธญเธ';

-- Modify column 'admission_plan_count' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `admission_plan_count` int(5) NULL COMMENT 'เนเธเธเธฃเธฑเธเธเธฑเธเธจเธถเธเธฉเธฒเธเธฒเธกเธฃเธฒเธขเธฅเธฐเนเธญเธตเธขเธเธซเธฅเธฑเธเธชเธนเธเธฃ (เธเธณเธเธงเธ เธเธ)';

-- Modify column 'target_highschool_count' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `target_highschool_count` int(5) NULL COMMENT 'เธเธณเธเธงเธเธเธฑเธเธจเธถเธเธฉเธฒเธเธตเนเธเธฐเธฃเธฑเธ (เธกเธฑเธเธขเธก)';

-- Modify column 'target_diploma_count' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `target_diploma_count` int(5) NULL COMMENT 'เธเธณเธเธงเธเธเธฑเธเธจเธถเธเธฉเธฒเธเธตเนเธเธฐเธฃเธฑเธ (เธเธงเธช.)';

-- Modify column 'current_highschool_year1' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `current_highschool_year1` int(5) NULL COMMENT 'เธเธณเธเธงเธเธเธฑเธเธจเธถเธเธฉเธฒเธเธฑเธเธเธธเธเธฑเธ เธเธฑเนเธเธเธตเธเธตเน 1 (เธกเธฑเธเธขเธก)';

-- Modify column 'current_highschool_year2' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `current_highschool_year2` int(5) NULL COMMENT 'เธเธณเธเธงเธเธเธฑเธเธจเธถเธเธฉเธฒเธเธฑเธเธเธธเธเธฑเธ เธเธฑเนเธเธเธตเธเธตเน 2 (เธกเธฑเธเธขเธก)';

-- Modify column 'current_highschool_year3' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `current_highschool_year3` int(5) NULL COMMENT 'เธเธณเธเธงเธเธเธฑเธเธจเธถเธเธฉเธฒเธเธฑเธเธเธธเธเธฑเธ เธเธฑเนเธเธเธตเธเธตเน 3 (เธกเธฑเธเธขเธก)';

-- Modify column 'current_highschool_year4' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `current_highschool_year4` int(5) NULL COMMENT 'เธเธณเธเธงเธเธเธฑเธเธจเธถเธเธฉเธฒเธเธฑเธเธเธธเธเธฑเธ เธเธฑเนเธเธเธตเธเธตเน 4 (เธกเธฑเธเธขเธก)';

-- Modify column 'current_highschool_graduated' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `current_highschool_graduated` int(5) NULL COMMENT 'เธชเธณเนเธฃเนเธเธเธฒเธฃเธจเธถเธเธฉเธฒ (เธกเธฑเธเธขเธก)';

-- Modify column 'current_highschool_remain' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `current_highschool_remain` int(5) NULL COMMENT 'เธเธฑเธเธจเธถเธเธฉเธฒเธเนเธฒเธเธเธฑเนเธ (เธกเธฑเธเธขเธก)';

-- Modify column 'current_diploma_year1' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `current_diploma_year1` int(5) NULL COMMENT 'เธเธณเธเธงเธเธเธฑเธเธจเธถเธเธฉเธฒเธเธฑเธเธเธธเธเธฑเธ เธเธฑเนเธเธเธตเธเธตเน 1 (เธเธงเธช.)';

-- Modify column 'current_diploma_year2' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `current_diploma_year2` int(5) NULL COMMENT 'เธเธณเธเธงเธเธเธฑเธเธจเธถเธเธฉเธฒเธเธฑเธเธเธธเธเธฑเธ เธเธฑเนเธเธเธตเธเธตเน 2 (เธเธงเธช.)';

-- Modify column 'current_diploma_year3' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `current_diploma_year3` int(5) NULL COMMENT 'เธเธณเธเธงเธเธเธฑเธเธจเธถเธเธฉเธฒเธเธฑเธเธเธธเธเธฑเธ เธเธฑเนเธเธเธตเธเธตเน 3 (เธเธงเธช.)';

-- Modify column 'current_diploma_year4' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `current_diploma_year4` int(5) NULL COMMENT 'เธเธณเธเธงเธเธเธฑเธเธจเธถเธเธฉเธฒเธเธฑเธเธเธธเธเธฑเธ เธเธฑเนเธเธเธตเธเธตเน 4 (เธเธงเธช.)';

-- Modify column 'current_diploma_graduated' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `current_diploma_graduated` int(5) NULL COMMENT 'เธชเธณเนเธฃเนเธเธเธฒเธฃเธจเธถเธเธฉเธฒ (เธเธงเธช.)';

-- Modify column 'current_diploma_remain' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `current_diploma_remain` int(5) NULL COMMENT 'เธเธฑเธเธจเธถเธเธฉเธฒเธเนเธฒเธเธเธฑเนเธ (เธเธงเธช.)';

-- Modify column 'created_at' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Modify column 'updated_at' in table 'student_admission_forms'
ALTER TABLE `student_admission_forms` MODIFY COLUMN `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP();

-- Modify column 'id' in table 'teacher_curriculum'
ALTER TABLE `teacher_curriculum` MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT;

-- Modify column 'teacher_uid' in table 'teacher_curriculum'
ALTER TABLE `teacher_curriculum` MODIFY COLUMN `teacher_uid` int(10) unsigned NOT NULL COMMENT 'UID of teacher from user table';

-- Modify column 'curriculum_id' in table 'teacher_curriculum'
ALTER TABLE `teacher_curriculum` MODIFY COLUMN `curriculum_id` int(11) NOT NULL COMMENT 'ID of curriculum from curriculum table';

-- Modify column 'assigned_at' in table 'teacher_curriculum'
ALTER TABLE `teacher_curriculum` MODIFY COLUMN `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When was teacher assigned to this curriculum';

-- NOTE: teacher_curriculum_view is a VIEW, not a table. Views are replaced using CREATE OR REPLACE VIEW (see below).

-- Modify column 'admin' in table 'user'
ALTER TABLE `user` MODIFY COLUMN `admin` int(11) NOT NULL DEFAULT '0';

-- Modify column 'edoc' in table 'user'
ALTER TABLE `user` MODIFY COLUMN `edoc` int(11) NOT NULL DEFAULT '0';

-- Modify column 'curriculum_id' in table 'user'
ALTER TABLE `user` MODIFY COLUMN `curriculum_id` int(11) NULL;

-- Modify column 'faculty_id' in table 'user'
ALTER TABLE `user` MODIFY COLUMN `faculty_id` int(11) NULL;

-- Modify column 'department_id' in table 'user'
ALTER TABLE `user` MODIFY COLUMN `department_id` int(11) NULL;

-- Add column 'bio' to table 'user'
ALTER TABLE `user` ADD COLUMN `bio` text NULL COMMENT 'Professional biography/summary for CV';

-- Add column 'expertise' to table 'user'
ALTER TABLE `user` ADD COLUMN `expertise` text NULL COMMENT 'Areas of expertise (comma-separated)';

-- Add column 'phone' to table 'user'
ALTER TABLE `user` ADD COLUMN `phone` varchar(50) NULL COMMENT 'Contact phone number';

-- Add column 'institution' to table 'user'
ALTER TABLE `user` ADD COLUMN `institution` varchar(255) NULL COMMENT 'Current institution/affiliation';

-- Add column 'google_scholar' to table 'user'
ALTER TABLE `user` ADD COLUMN `google_scholar` varchar(500) NULL COMMENT 'Google Scholar profile URL';

-- Add column 'orcid' to table 'user'
ALTER TABLE `user` ADD COLUMN `orcid` varchar(500) NULL COMMENT 'ORCID profile URL';

-- Add column 'scopus' to table 'user'
ALTER TABLE `user` ADD COLUMN `scopus` varchar(500) NULL COMMENT 'Scopus profile URL';

-- Add column 'researchgate' to table 'user'
ALTER TABLE `user` ADD COLUMN `researchgate` varchar(500) NULL COMMENT 'ResearchGate profile URL';

-- Add column 'linkedin' to table 'user'
ALTER TABLE `user` ADD COLUMN `linkedin` varchar(500) NULL COMMENT 'LinkedIn profile URL';

-- Modify column 'id' in table 'user_profile'
ALTER TABLE `user_profile` MODIFY COLUMN `id` int(10) unsigned NOT NULL AUTO_INCREMENT;

-- Add column 'orcid_id' to table 'user_profile'
ALTER TABLE `user_profile` ADD COLUMN `orcid_id` varchar(20) NULL COMMENT 'ORCID iD';

-- Add column 'orcid_data' to table 'user_profile'
ALTER TABLE `user_profile` ADD COLUMN `orcid_data` longtext NULL COMMENT 'Cached ORCID API response data';

-- Add column 'orcid_synced_at' to table 'user_profile'
ALTER TABLE `user_profile` ADD COLUMN `orcid_synced_at` datetime NULL COMMENT 'Last ORCID sync timestamp';

-- Modify column 'id' in table 'user_roles'
ALTER TABLE `user_roles` MODIFY COLUMN `id` int(10) unsigned NOT NULL AUTO_INCREMENT;

-- Modify column 'user_id' in table 'user_roles'
ALTER TABLE `user_roles` MODIFY COLUMN `user_id` int(10) unsigned NOT NULL;

-- Modify column 'role_id' in table 'user_roles'
ALTER TABLE `user_roles` MODIFY COLUMN `role_id` int(10) unsigned NOT NULL;

-- Modify column 'faculty_id' in table 'user_roles'
ALTER TABLE `user_roles` MODIFY COLUMN `faculty_id` int(11) NULL COMMENT 'Optional: Scope role to specific faculty (for faculty_admin role)';

-- Modify column 'assigned_at' in table 'user_roles'
ALTER TABLE `user_roles` MODIFY COLUMN `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP();

-- Modify column 'assigned_by' in table 'user_roles'
ALTER TABLE `user_roles` MODIFY COLUMN `assigned_by` int(10) unsigned NULL;

-- Replace view 'publication_view'
SET FOREIGN_KEY_CHECKS = 1;
