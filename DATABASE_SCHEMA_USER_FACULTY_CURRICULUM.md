# Database Schema: User Faculty and Curriculum Relationship

## Overview

This document describes the reconstructed database schema for user (teacher) faculty and curriculum relationships.

## Key Principles

1. **User (Teacher) Faculty Affiliation**
   - Every user (teacher) MUST have a `faculty_id` from the `faculties` table
   - This represents the user's main faculty affiliation (สังกัดคณะ)
   - Stored in `user.faculty_id` column
   - Foreign key relationship: `user.faculty_id` → `faculties.id`

2. **User (Teacher) Curriculum Assignment**
   - Users (teachers) CAN have or NOT have curriculum assignments
   - Users can have curriculum that does NOT belong to their faculty
   - Curriculum assignments are stored in `teacher_curriculum` table (many-to-many)
   - Primary curriculum is marked with `is_primary = 1` in `teacher_curriculum` table
   - Legacy support: `user.curriculum_id` can still exist but `teacher_curriculum` is preferred

## Database Structure

### User Table (`user`)

```sql
CREATE TABLE `user` (
  `uid` INT(3) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `faculty_id` INT(11) DEFAULT NULL COMMENT 'Main faculty affiliation (required for TEACHER)',
  `curriculum_id` INT(11) DEFAULT NULL COMMENT 'Legacy: primary curriculum (deprecated, use teacher_curriculum)',
  `user_type` VARCHAR(50) DEFAULT NULL COMMENT 'TEACHER, STUDENT, STAFF, etc.',
  -- other fields...
  PRIMARY KEY (`uid`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_user_faculty` (`faculty_id`),
  KEY `fk_user_curriculum` (`curriculum_id`),
  CONSTRAINT `fk_user_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_user_curriculum` FOREIGN KEY (`curriculum_id`) REFERENCES `curriculum` (`id`) ON DELETE SET NULL
);
```

### Teacher Curriculum Table (`teacher_curriculum`)

```sql
CREATE TABLE `teacher_curriculum` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `teacher_uid` INT(3) UNSIGNED NOT NULL,
  `curriculum_id` INT(11) NOT NULL,
  `role` ENUM('instructor', 'coordinator', 'assistant') DEFAULT 'instructor',
  `is_primary` TINYINT(1) DEFAULT 0 COMMENT '1=primary curriculum',
  `status` TINYINT(1) DEFAULT 1 COMMENT '1=active, 0=inactive',
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_teacher_curriculum` (`teacher_uid`, `curriculum_id`),
  CONSTRAINT `fk_teacher_curriculum_user` FOREIGN KEY (`teacher_uid`) REFERENCES `user` (`uid`) ON DELETE CASCADE,
  CONSTRAINT `fk_teacher_curriculum_curriculum` FOREIGN KEY (`curriculum_id`) REFERENCES `curriculum` (`id`) ON DELETE CASCADE
);
```

### Curriculum Table (`curriculum`)

```sql
CREATE TABLE `curriculum` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `faculty_id` INT(11) NOT NULL COMMENT 'Faculty that owns this curriculum',
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(20) NOT NULL,
  -- other fields...
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_curriculum_faculties` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`)
);
```

## Relationships

```
user (1) -----> (M) faculties
  |                (user.faculty_id → faculties.id)
  |
  | (many-to-many)
  |
  v
teacher_curriculum (junction table)
  |
  v
curriculum (M) -----> (1) faculties
                        (curriculum.faculty_id → faculties.id)
```

## Important Notes

1. **User Faculty vs Curriculum Faculty**
   - `user.faculty_id` = User's main faculty affiliation (required for TEACHER)
   - `curriculum.faculty_id` = Faculty that owns the curriculum
   - These CAN be different! A teacher from Faculty A can teach in a curriculum from Faculty B

2. **Primary Curriculum**
   - The primary curriculum is stored in `teacher_curriculum` with `is_primary = 1`
   - A teacher can have multiple curriculums but only one primary
   - Legacy: `user.curriculum_id` may still exist but should be migrated to `teacher_curriculum`

3. **Query Pattern for User-Roles View**
   ```sql
   SELECT 
     user.uid,
     user.email,
     user.faculty_id as user_faculty_id,
     uf.name as user_faculty_name,
     tc.curriculum_id,
     c.name as curriculum_name,
     c.code as curriculum_code
   FROM user
   LEFT JOIN faculties uf ON uf.id = user.faculty_id
   LEFT JOIN teacher_curriculum tc ON tc.teacher_uid = user.uid AND tc.is_primary = 1 AND tc.status = 1
   LEFT JOIN curriculum c ON c.id = tc.curriculum_id
   WHERE user.active = 1
   ```

## Migration Notes

- Existing `user.curriculum_id` values should be migrated to `teacher_curriculum` table
- Set `is_primary = 1` for migrated records
- `user.curriculum_id` can remain for backward compatibility but new assignments should use `teacher_curriculum`


