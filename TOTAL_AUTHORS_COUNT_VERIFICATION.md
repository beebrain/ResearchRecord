# การนับ Total Authors - ตรวจสอบความถูกต้อง

**วันที่**: 2025-12-05  
**วัตถุประสงค์**: ตรวจสอบว่าการนับ Total Authors ถูกต้องตามเงื่อนไข

---

## ✅ **เงื่อนไขการนับ Total Authors**

### 1. **นับเฉพาะอาจารย์ที่อยู่ในระบบ** ✅
- ตาราง: `user` (u.active = 1)
- ต้องอยู่ใน `teacher_curriculum` (tc.status = 1)

### 2. **มีผลงานเท่านั้น** ✅
- INNER JOIN กับ `publication_authors`
- ตรวจสอบ 3 เงื่อนไข:
  - `pa.uid = u.uid` (มี uid ตรงกัน)
  - `pa.author_email = u.email` (มี email ตรงกัน)
  - `EXISTS (SELECT 1 FROM authors a WHERE a.id = pa.author_id AND a.user_uid = u.uid)` (link ผ่าน authors table)

### 3. **ไม่นับซ้ำอาจารย์ที่มีหลายผลงาน** ✅
- ใช้ `COUNT(DISTINCT u.uid)` ในทุก query
- แม้อาจารย์มี 10 ผลงาน ก็นับเป็น 1 คนเท่านั้น

### 4. **ระดับ Admin**

#### **Super Admin** ✅
```sql
SELECT COUNT(DISTINCT u.uid) as count
FROM user u
INNER JOIN teacher_curriculum tc ON tc.teacher_uid = u.uid AND tc.status = 1
INNER JOIN publication_authors pa ON (
    pa.uid = u.uid 
    OR pa.author_email = u.email
    OR EXISTS (
        SELECT 1 FROM authors a 
        WHERE a.id = pa.author_id AND a.user_uid = u.uid
    )
)
WHERE u.active = 1
```
**นับ**: อาจารย์ทั้งมหาวิทยาลัย (ไม่มี filter คณะ)

---

#### **Faculty Admin** ✅
```sql
SELECT COUNT(DISTINCT u.uid) as count
FROM user u
INNER JOIN teacher_curriculum tc ON tc.teacher_uid = u.uid AND tc.status = 1
INNER JOIN curriculum c ON c.id = tc.curriculum_id AND c.faculty_id IN (1, 2, ...)
INNER JOIN publication_authors pa ON (...)
INNER JOIN publication_view pv ON pv.id = pa.publication_id AND pv.faculty_id IN (1, 2, ...)
WHERE u.active = 1
```
**นับ**: เฉพาะอาจารย์ในหลักสูตรของคณะที่ดูแล **และ** มีผลงานในคณะนั้น

**เงื่อนไขสำคัญ:**
- `c.faculty_id IN (...)` - อาจารย์ต้องสอนในหลักสูตรของคณะที่ดูแล
- `pv.faculty_id IN (...)` - ผลงานต้องอยู่ในคณะที่ดูแล

---

#### **Regular User** ✅
```sql
SELECT COUNT(DISTINCT pa.author_id) as count
FROM publication_authors pa
INNER JOIN publications p ON p.id = pa.publication_id
WHERE pa.author_id IS NOT NULL
AND p.id IN (...)  -- publication IDs ของ user นั้น
```
**นับ**: co-authors ในผลงานของ user (ไม่เปลี่ยนแปลง logic)

---

## 🔍 **การป้องกันการนับซ้ำ**

### ตัวอย่างกรณีทดสอบ:

**อาจารย์ A** มี:
- 5 ผลงาน
- สอนใน 2 หลักสูตร
- อยู่ใน 1 คณะ

**ผลการนับ**:
- Super Admin: นับ **1 คน** (ไม่ใช่ 5 คน) ✅
- Faculty Admin (คณะของอาจารย์ A): นับ **1 คน** ✅
- Faculty Admin (คณะอื่น): นับ **0 คน** ✅

**เหตุผล**: ใช้ `COUNT(DISTINCT u.uid)` จึงนับเฉพาะ UID ที่ไม่ซ้ำกัน

---

## 📊 **ตัวอย่างการทดสอบ**

### Test Case 1: อาจารย์ที่มีหลายผลงาน

```sql
-- ดูอาจารย์ที่มี > 1 ผลงาน
SELECT 
    u.uid,
    u.email,
    COUNT(DISTINCT pa.publication_id) as publication_count
FROM user u
INNER JOIN teacher_curriculum tc ON tc.teacher_uid = u.uid AND tc.status = 1
INNER JOIN publication_authors pa ON (
    pa.uid = u.uid 
    OR pa.author_email = u.email
)
WHERE u.active = 1
GROUP BY u.uid, u.email
HAVING publication_count > 1
ORDER BY publication_count DESC;
```

**ผลลัพธ์ที่คาดหวัง**: แสดงอาจารย์และจำนวนผลงาน แต่เมื่อนับ Total Authors จะนับเป็น **1 คน/1 UID เท่านั้น**

---

### Test Case 2: อาจารย์ในหลายหลักสูตร

```sql
-- ดูอาจารย์ที่สอนหลายหลักสูตร
SELECT 
    u.uid,
    u.email,
    COUNT(DISTINCT tc.curriculum_id) as curriculum_count,
    GROUP_CONCAT(DISTINCT c.name) as curricula
FROM user u
INNER JOIN teacher_curriculum tc ON tc.teacher_uid = u.uid AND tc.status = 1
INNER JOIN curriculum c ON c.id = tc.curriculum_id
WHERE u.active = 1
GROUP BY u.uid, u.email
HAVING curriculum_count > 1;
```

**ผลลัพธ์ที่คาดหวัง**: แม้สอนหลายหลักสูตร เมื่อนับ Total Authors จะนับเป็น **1 คน/1 UID เท่านั้น**

---

### Test Case 3: Faculty Admin Filter

```sql
-- นับอาจารย์ในคณะ ID = 1 (Faculty Admin)
SELECT COUNT(DISTINCT u.uid) as count
FROM user u
INNER JOIN teacher_curriculum tc ON tc.teacher_uid = u.uid AND tc.status = 1
INNER JOIN curriculum c ON c.id = tc.curriculum_id AND c.faculty_id = 1
INNER JOIN publication_authors pa ON (...)
INNER JOIN publication_view pv ON pv.id = pa.publication_id AND pv.faculty_id = 1
WHERE u.active = 1;
```

**ผลลัพธ์ที่คาดหวัง**: นับเฉพาะอาจารย์ที่:
- สอนในหลักสูตรของคณะ 1
- มีผลงานในคณะ 1
- ไม่นับซ้ำ

---

## ✅ **สรุปการตรวจสอบ**

| เงื่อนไข | สถานะ | หมายเหตุ |
|---------|-------|----------|
| นับเฉพาะอาจารย์ในระบบ | ✅ | `user.active = 1` + `teacher_curriculum.status = 1` |
| มีผลงานเท่านั้น | ✅ | INNER JOIN `publication_authors` |
| ไม่นับซ้ำ | ✅ | `COUNT(DISTINCT u.uid)` |
| Super Admin: ทั้งมหาวิทยาลัย | ✅ | ไม่มี filter คณะ |
| Faculty Admin: เฉพาะคณะ | ✅ | Filter `c.faculty_id IN (...)` + `pv.faculty_id IN (...)` |
| Regular User: co-authors | ✅ | นับ authors ในผลงานของ user |

---

## 🎯 **ข้อดีของ Logic ปัจจุบัน**

1. **แม่นยำ**: นับเฉพาะอาจารย์ที่มีผลงาน
2. **ไม่ซ้ำ**: ใช้ DISTINCT u.uid
3. **Filter ถูกต้อง**: แยกชัดเจนระหว่าง Super Admin / Faculty Admin / Regular User
4. **Performance**: ใช้ INNER JOIN แทน subquery ซ้อนกัน

---

## 📝 **คำแนะนำ**

### หากต้องการทดสอบเพิ่มเติม:

1. รัน SQL ใน `test_total_authors_count.sql`
2. ตรวจสอบว่าผลลัพธ์ตรงกับที่แสดงใน Dashboard
3. Login เป็น Faculty Admin และตรวจสอบว่านับเฉพาะคณะที่ดูแล

### หากพบปัญหา:

1. ตรวจสอบ `teacher_curriculum.status` ว่าเป็น 1
2. ตรวจสอบ `user.active` ว่าเป็น 1
3. ตรวจสอบความสัมพันธ์ระหว่าง `authors.user_uid` กับ `user.uid`
4. ตรวจสอบ `publication_authors.author_email` ว่าตรงกับ `user.email`

---

**สรุป**: Logic การนับ Total Authors ถูกต้องตามเงื่อนไขทั้งหมด ใช้ DISTINCT เพื่อป้องกันการนับซ้ำ และ filter ตาม role ของ admin อย่างเหมาะสม ✅
