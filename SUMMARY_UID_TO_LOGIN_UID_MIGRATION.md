# สรุปการเปลี่ยนแปลงจาก uid เป็น login_uid

## วันที่: 2025-11-17

## ภาพรวม
เปลี่ยนจากการใช้ `uid` (auto-increment) เป็น `login_uid` (varchar) เป็น primary key ของตาราง `user` เพื่อรองรับการย้ายข้อมูลระหว่างระบบ

---

## ไฟล์ที่แก้ไข

### 1. Models
- ✅ **UserModel.php**: เปลี่ยน `primaryKey` เป็น `login_uid`, อัปเดต joins ทั้งหมด
- ✅ **AuthorModel.php**: อัปเดต joins ให้ใช้ `user.login_uid`
- ✅ **UserRoleModel.php**: อัปเดต join ให้ใช้ `user.login_uid`

### 2. Controllers
- ✅ **AuthenController.php**: ใช้ `login_uid` ใน session และการอัปเดต user
- ✅ **DashboardController.php**: ใช้ `login_uid` จาก session
- ✅ **PublicationController.php**: ใช้ `login_uid` สำหรับ `created_by` และ joins
- ✅ **UserController.php**: ใช้ `login_uid` ใน API responses
- ✅ **AdminController.php**: อัปเดต joins และ queries ให้ใช้ `login_uid`
- ✅ **SecretController.php**: ใช้ `login_uid` ใน session
- ✅ **FacultyCurriculumController.php**: อัปเดต SQL queries ให้ใช้ `u.login_uid`

### 3. JavaScript Files
- ✅ **curriculum-user-manager.js**: รองรับ `login_uid` (fallback เป็น `uid`)
- ✅ **user-roles-manager.js**: รองรับ `login_uid` (fallback เป็น `uid`)
- ✅ **author-search.js**: ใช้ `login_uid` แทน `uid`
- ✅ **email-autocomplete.js**: ใช้ `user_uid` จาก API (ซึ่งตอนนี้เป็น `login_uid`)
- ✅ **publication-ai.js**: รองรับหลาย field (`user_uid`, `user_id`, `uid`)
- ✅ **publication-form.js**: ใช้ `data('user-uid')` ซึ่งมาจาก API

### 4. Views
- ✅ **backdoor.php**: ใช้ `login_uid` แทน `uid`
- ✅ **manageEmail.php**: ใช้ `login_uid` ใน JavaScript
- ✅ **dashboard/index.php**: ใช้ `login_uid` จาก session
- ✅ **managePublication.php**: ใช้ `userUid` จาก data attribute (ซึ่งมาจาก API)

---

## การเปลี่ยนแปลงหลัก

### Database Schema
1. **UserModel**: `primaryKey = 'login_uid'` (เดิมเป็น `'uid'`)
2. **teacher_curriculum**: เปลี่ยน `teacher_uid` เป็น `teacher_login_uid` และอ้างอิง `user.login_uid`

### Session Data
- Session เก็บทั้ง `login_uid` และ `uid` (backward compatibility)
- `user_id` ใน session = `login_uid`

### API Responses
- API responses ส่ง `login_uid` เป็นหลัก
- รองรับ backward compatibility ด้วย `login_uid || uid`

### Foreign Keys
- **authors.user_uid**: ยังอ้างอิง `user.uid` อยู่ (ต้อง migrate ภายหลัง)
- **publications.created_by**: ยังอ้างอิง `user.uid` อยู่ (ต้อง migrate ภายหลัง)
- **teacher_curriculum.teacher_login_uid**: อ้างอิง `user.login_uid` (ใหม่)

---

## Migration Script

สร้างไฟล์ `migrate_uid_to_login_uid.sql` สำหรับ:
1. เพิ่ม column `teacher_login_uid` ในตาราง `teacher_curriculum`
2. Migrate ข้อมูลจาก `teacher_uid` ไป `teacher_login_uid`
3. อัปเดต foreign keys และ constraints
4. อัปเดต `teacher_curriculum_view` ให้ใช้ `login_uid`

---

## ข้อควรระวัง

1. **Foreign Keys ที่ยังใช้ uid**:
   - `authors.user_uid` → `user.uid`
   - `authors.created_by` → `user.uid`
   - `publications.created_by` → `user.uid`
   - ต้องสร้าง migration script เพิ่มเติมเพื่อเปลี่ยน foreign keys เหล่านี้

2. **Backward Compatibility**:
   - ใช้ `login_uid ?? uid` ในหลายจุดเพื่อรองรับข้อมูลเก่า
   - Session เก็บทั้ง `login_uid` และ `uid`

3. **Database Migration**:
   - ต้องรัน `migrate_uid_to_login_uid.sql` ก่อนใช้งาน
   - ตรวจสอบ foreign key constraints หลัง migration

---

## ขั้นตอนต่อไป

1. ✅ แก้ไขโค้ดทั้งหมดให้ใช้ `login_uid`
2. ⏳ รัน migration script `migrate_uid_to_login_uid.sql`
3. ⏳ สร้าง migration script สำหรับ foreign keys อื่นๆ (`authors`, `publications`)
4. ⏳ ทดสอบการทำงานทั้งหมด
5. ⏳ อัปเดต documentation

---

## สรุป

การเปลี่ยนแปลงเสร็จสมบูรณ์แล้วในส่วนของโค้ด แต่ต้องรัน migration scripts เพื่ออัปเดตโครงสร้างฐานข้อมูลและ foreign keys

