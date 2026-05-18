# ตรวจสอบการใช้ publication_view

## สรุป Controller และ Model ที่ใช้ publication_view

### 1. StudentAdmissionFormModel.php

- **Method**: `getTeacherPublications()`
- **Usage**: `SELECT * FROM publication_view WHERE id IN (...)`
- **Status**: ✅ ทำงานได้ปกติ (ใช้ SELECT \*)
- **Note**: Map ผ่าน email เท่านั้น

### 2. PublicationModel.php

- **Method**: `getAllPublicationsWithAuthors()`
- **Usage**: `SELECT * FROM publication_view ORDER BY ...`
- **Status**: ✅ ทำงานได้ปกติ (ใช้ SELECT \*)

- **Method**: `getPublicationsByUser()`
- **Usage**: `SELECT * FROM publication_view WHERE created_by = ...`
- **Status**: ✅ ทำงานได้ปกติ (ใช้ SELECT \* และ created_by field)

### 3. AdminController.php

- **Method**: `getStats()`
- **Usage**: `SELECT COUNT(*) FROM publication_view`
- **Status**: ✅ ทำงานได้ปกติ

- **Method**: `getStatsByFaculty()`
- **Usage**: `SELECT COUNT(*) FROM publication_view WHERE faculty_id IN (...)`
- **Status**: ✅ ทำงานได้ปกติ (มี field faculty_id แล้ว)

- **Method**: `getAvailableYears()`
- **Usage**: `SELECT DISTINCT publication_year FROM publication_view WHERE faculty_id IN (...)`
- **Status**: ✅ ทำงานได้ปกติ (มี field faculty_id แล้ว)

### 4. CurriculumModel.php

- **Method**: `getWithPublicationStats()`
- **Usage**: `SELECT COUNT(*) FROM publication_view WHERE author_curriculum LIKE ...`
- **Status**: ✅ ทำงานได้ปกติ (field author_curriculum ยังมีอยู่)

### 5. FacultyModel.php

- **Method**: `getWithPublicationCount()`
- **Usage**: `SELECT COUNT(*) FROM publication_view WHERE author_faculties LIKE ...`
- **Status**: ✅ ทำงานได้ปกติ (field author_faculties ยังมีอยู่)

## Fields ที่สำคัญใน publication_view (หลังอัปเดต)

✅ **Fields ที่ยังมีอยู่**:

- `id`, `title`, `publication_type`, `source`, `publication_year`, etc.
- `created_by`, `created_by_name`, `created_by_faculty_id`
- `faculty_id` (ใหม่ - สำหรับ backward compatibility)
- `author_curriculum`, `authors_names_en`, `authors_names_thai`
- `author_faculties`, `author_uids`
- `author_curriculum_ids`, `author_faculty_ids`

## การเปลี่ยนแปลงสำคัญ

⚠️ **การ Map Authors**:

- **เดิม**: Map ผ่าน `authors.user_uid` → `user.uid`
- **ใหม่**: Map ผ่าน `pa.author_email` → `user.email` หรือ `authors.email`

⚠️ **ผลกระทบ**:

- `author_uids` จะมีเฉพาะ UIDs ที่ match ผ่าน email เท่านั้น
- `author_curriculum`, `author_faculties` จะมีเฉพาะข้อมูลของ authors ที่ match ผ่าน email

## วิธีทดสอบ

1. รัน SQL script: `update_publication_view_email_mapping.sql`
2. ทดสอบแต่ละ endpoint:
   - `/admin/admission?year=2569&faculty=16` ✅
   - `/api/dashboard/publications` ✅
   - `/admin/dashboard` (stats) ✅
   - `/admin/curriculum` (curriculum stats) ✅
   - `/admin/faculty` (faculty stats) ✅
