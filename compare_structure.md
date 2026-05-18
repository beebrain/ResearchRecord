# การเปรียบเทียบโครงสร้างตาราง: Production vs Local

## สรุปผลการตรวจสอบ

### ✅ ตาราง `faculties`

#### Production (จาก SQL dump):
```sql
CREATE TABLE `faculties` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `dean_id` int unsigned DEFAULT NULL COMMENT 'Foreign key to user.uid - คณบดีของคณะ',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `fk_faculties_dean` (`dean_id`),
  CONSTRAINT `fk_faculties_dean` FOREIGN KEY (`dean_id`) REFERENCES `user` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

#### Local (จาก Migration):
```php
'dean_id' => [
    'type' => 'INT',
    'constraint' => 3,
    'unsigned' => true,
    'null' => true,
    'comment' => 'Foreign key to user.uid - คณบดีของคณะ',
    'after' => 'status'
]
```

**ผลการเปรียบเทียบ:**
- ✅ **ตรงกัน**: Production มี `dean_id` และ foreign key `fk_faculties_dean`
- ⚠️ **ความแตกต่างเล็กน้อย**: 
  - Production: `int unsigned` (ไม่ระบุ constraint)
  - Local: `INT(3) UNSIGNED` (ระบุ constraint 3)
  - **หมายเหตุ**: ใน MySQL `INT(3)` และ `INT` ทำงานเหมือนกัน (3 คือ display width ไม่ใช่ constraint จริงๆ)

---

### ✅ ตาราง `curriculum`

#### Production (จาก SQL dump):
```sql
CREATE TABLE `curriculum` (
  `id` int NOT NULL AUTO_INCREMENT,
  `faculty_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `degree_level` enum('bachelor','master','doctoral') COLLATE utf8mb4_general_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `chair_id` int unsigned DEFAULT NULL COMMENT 'Foreign key to user.uid - ประธานหลักสูตร',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_curriculum_faculties` (`faculty_id`),
  KEY `fk_curriculum_chair` (`chair_id`),
  CONSTRAINT `fk_curriculum_chair` FOREIGN KEY (`chair_id`) REFERENCES `user` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_curriculum_faculties` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

#### Local (จาก Migration):
```php
'chair_id' => [
    'type' => 'INT',
    'constraint' => 3,
    'unsigned' => true,
    'null' => true,
    'comment' => 'Foreign key to user.uid - ประธานหลักสูตร',
    'after' => 'status'
]
```

**ผลการเปรียบเทียบ:**
- ✅ **ตรงกัน**: Production มี `chair_id` และ foreign key `fk_curriculum_chair`
- ⚠️ **ความแตกต่างเล็กน้อย**: 
  - Production: `int unsigned` (ไม่ระบุ constraint)
  - Local: `INT(3) UNSIGNED` (ระบุ constraint 3)
  - **หมายเหตุ**: ใน MySQL `INT(3)` และ `INT` ทำงานเหมือนกัน

---

## สรุป

### ✅ **โครงสร้างตรงกัน**

Production database **มีโครงสร้างที่ถูกต้องแล้ว**:
1. ✅ ตาราง `faculties` มีคอลัมน์ `dean_id` และ foreign key `fk_faculties_dean`
2. ✅ ตาราง `curriculum` มีคอลัมน์ `chair_id` และ foreign key `fk_curriculum_chair`
3. ✅ Foreign keys ถูกตั้งค่าเป็น `ON DELETE SET NULL ON UPDATE CASCADE` ถูกต้อง

### ⚠️ **ความแตกต่างเล็กน้อย (ไม่สำคัญ)**

- **Production**: ใช้ `int unsigned` (ไม่ระบุ display width)
- **Local**: ใช้ `INT(3) UNSIGNED` (ระบุ display width = 3)

**หมายเหตุ**: ความแตกต่างนี้ไม่สำคัญ เพราะ:
- `INT(3)` ใน MySQL ไม่ได้จำกัดขนาดข้อมูลจริงๆ
- ตัวเลข 3 คือ **display width** สำหรับการแสดงผลเท่านั้น
- ทั้งสองแบบสามารถเก็บค่าได้เหมือนกัน (0 ถึง 4,294,967,295 สำหรับ UNSIGNED)

### 📋 **ข้อแนะนำ**

**Production database พร้อมใช้งานแล้ว** ไม่ต้องทำ migration เพิ่มเติม

หากต้องการให้โครงสร้างเหมือนกันทุกประการ (optional):
```sql
-- ไม่จำเป็น แต่ถ้าต้องการให้เหมือนกันทุกประการ
ALTER TABLE faculties MODIFY COLUMN dean_id INT(3) UNSIGNED NULL;
ALTER TABLE curriculum MODIFY COLUMN chair_id INT(3) UNSIGNED NULL;
```

แต่**ไม่จำเป็น**เพราะการทำงานเหมือนกันทุกประการ

