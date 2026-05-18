# Production Migration Guide
## การอัปเดตฐานข้อมูลสำหรับ Production

### สรุปการเปลี่ยนแปลง

การอัปเดตนี้เพิ่มฟีเจอร์การระบุ:
1. **คณบดีของแต่ละคณะ** (Dean) - ในตาราง `faculties`
2. **ประธานหลักสูตร** (Chair) - ในตาราง `curriculum`

### ตารางที่ต้องอัปเดต

#### 1. ตาราง `faculties`
- **เพิ่มคอลัมน์**: `dean_id` (INT(3) UNSIGNED NULL)
- **Foreign Key**: `fk_faculties_dean` → `user.uid`
- **ON DELETE**: SET NULL
- **ON UPDATE**: CASCADE

#### 2. ตาราง `curriculum`
- **เพิ่มคอลัมน์**: `chair_id` (INT(3) UNSIGNED NULL)
- **Foreign Key**: `fk_curriculum_chair` → `user.uid`
- **ON DELETE**: SET NULL
- **ON UPDATE**: CASCADE

### วิธีรัน Migration

#### วิธีที่ 1: ใช้ SQL Script (แนะนำ)
```bash
# ผ่าน MySQL Command Line
mysql -u [username] -p [database_name] < production_migration_dean_chair.sql

# หรือ
mysql -u [username] -p
USE [database_name];
SOURCE production_migration_dean_chair.sql;
```

#### วิธีที่ 2: ผ่าน phpMyAdmin
1. เปิด phpMyAdmin
2. เลือก database
3. ไปที่แท็บ "SQL"
4. Copy เนื้อหาจาก `production_migration_dean_chair.sql`
5. Paste และคลิก "Go"

#### วิธีที่ 3: รันทีละคำสั่ง
```sql
-- 1. เพิ่ม dean_id ใน faculties
ALTER TABLE faculties 
ADD COLUMN dean_id INT(3) UNSIGNED NULL 
COMMENT 'Foreign key to user.uid - คณบดีของคณะ' 
AFTER status;

-- 2. เพิ่ม Foreign Key สำหรับ dean_id
ALTER TABLE faculties 
ADD CONSTRAINT fk_faculties_dean 
FOREIGN KEY (dean_id) 
REFERENCES user(uid) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

-- 3. เพิ่ม chair_id ใน curriculum
ALTER TABLE curriculum 
ADD COLUMN chair_id INT(3) UNSIGNED NULL 
COMMENT 'Foreign key to user.uid - ประธานหลักสูตร' 
AFTER status;

-- 4. เพิ่ม Foreign Key สำหรับ chair_id
ALTER TABLE curriculum 
ADD CONSTRAINT fk_curriculum_chair 
FOREIGN KEY (chair_id) 
REFERENCES user(uid) 
ON DELETE SET NULL 
ON UPDATE CASCADE;
```

### การตรวจสอบหลัง Migration

รันคำสั่ง SQL ต่อไปนี้เพื่อตรวจสอบ:

```sql
-- ตรวจสอบคอลัมน์ dean_id
SHOW COLUMNS FROM faculties LIKE 'dean_id';

-- ตรวจสอบคอลัมน์ chair_id
SHOW COLUMNS FROM curriculum LIKE 'chair_id';

-- ตรวจสอบ Foreign Keys
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND CONSTRAINT_NAME IN ('fk_faculties_dean', 'fk_curriculum_chair')
  AND REFERENCED_TABLE_NAME IS NOT NULL;
```

### Rollback (ถ้าต้องการย้อนกลับ)

```sql
-- ลบ Foreign Keys ก่อน
ALTER TABLE faculties DROP FOREIGN KEY fk_faculties_dean;
ALTER TABLE curriculum DROP FOREIGN KEY fk_curriculum_chair;

-- ลบคอลัมน์
ALTER TABLE faculties DROP COLUMN dean_id;
ALTER TABLE curriculum DROP COLUMN chair_id;
```

### หมายเหตุ

1. **Backup**: ควร backup ฐานข้อมูลก่อนรัน migration
2. **Downtime**: Migration นี้ใช้เวลาน้อยมาก (ไม่กี่วินาที) แต่แนะนำให้รันในช่วงที่มีการใช้งานน้อย
3. **Data Loss**: Migration นี้ไม่ทำให้ข้อมูลหาย เพราะเป็นคอลัมน์ใหม่ที่ NULL ได้
4. **Compatibility**: ต้องใช้ MySQL 5.7+ หรือ MariaDB 10.2+

### ไฟล์ที่เกี่ยวข้อง

- `production_migration_dean_chair.sql` - SQL script สำหรับรัน migration
- `app/Database/Migrations/2025-01-20-000000_AddDeanIdToFaculties.php` - Migration file สำหรับ CodeIgniter
- `app/Database/Migrations/2025-01-20-100000_AddChairIdToCurriculum.php` - Migration file สำหรับ CodeIgniter

### หลัง Migration

หลังรัน migration สำเร็จ:
1. ตรวจสอบว่าไม่มี errors
2. ทดสอบการเลือกคณบดีในหน้า "จัดการคณะและหลักสูตร"
3. ทดสอบการเลือกประธานหลักสูตรในหน้า "จัดการหลักสูตรผู้ใช้"

