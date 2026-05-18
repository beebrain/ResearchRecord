# เปรียบเทียบการทำงาน: publications/create vs /dashboard/orcid

## 1. publications/create (หน้าเพิ่มผลงานโดยใช้ AI)

### Controller & Method

- **Controller**: `PublicationController`
- **Method**: `store()` (POST `/publications/store`)
- **Flow**: Form submission → `store()` → `addAuthors()` → `PublicationAuthorModel::addAuthorsToPublication()`

### การบันทึก Author

```php
// PublicationController::store()
$this->addAuthors($publicationId, $authors, $userData['uid']);

// PublicationController::addAuthors()
// ใช้ PublicationAuthorModel::addAuthorsToPublication()
// บันทึก author ทั้งหมดที่ส่งมา ไม่ว่าจะ match หรือไม่ match
```

### ลักษณะการทำงาน

- ✅ **บันทึกทุก Author**: บันทึก author ทั้งหมดที่ส่งมาจาก form ไม่ว่าจะ match กับ user ในระบบหรือไม่
- ❌ **ไม่มีการ Match**: ไม่มีการตรวจสอบว่า author match กับ user ในระบบหรือไม่
- ✅ **บันทึกทันที**: บันทึก author ทันทีที่ form submit
- 📝 **ใช้ Model**: ใช้ `PublicationAuthorModel::addAuthorsToPublication()` ซึ่งบันทึกแบบ batch

### ตัวอย่าง Code

```php
// PublicationAuthorModel::addAuthorsToPublication()
foreach ($authors as $index => $author) {
    $data[] = [
        'publication_id' => $publicationId,
        'author_name' => $author['name'],
        'author_email' => $author['email'] ?? null,
        'author_affiliation' => $author['affiliation'] ?? null,
        'author_id' => $author['author_id'] ?? null,  // อาจเป็น null
        'uid' => $author['uid'] ?? null,  // อาจเป็น null
        'author_order' => $index + 1,
        'corresponding' => $author['corresponding'] ?? 0
    ];
}
return $this->insertBatch($data);  // บันทึกทุก author
```

---

## 2. /dashboard/orcid (หน้า Sync ORCID)

### Controller & Method

- **Controller**: `DashboardController`
- **Method**: `syncOrcidWithDoi()` → `saveOrcidPublication()` (POST `/dashboard/sync-orcid-doi`)
- **Flow**: AJAX call → `syncOrcidWithDoi()` → `fetchPublicationByDoi()` → `matchAuthorsToUsers()` → `saveOrcidPublication()`

### การบันทึก Author

```php
// DashboardController::saveOrcidPublication()
// เรียก matchAuthorsToUsers() เพื่อ match author กับ user ในระบบ
// บันทึกเฉพาะ author ที่ match สำเร็จ
if ($matchedUser && !empty($authorId)) {
    $this->db->table('publication_authors')->insert([...]);
}
```

### ลักษณะการทำงาน

- ✅ **บันทึกเฉพาะ Author ที่ Match**: บันทึกเฉพาะ author ที่ match กับ user ในระบบสำเร็จเท่านั้น
- ✅ **มีการ Match**: มีการตรวจสอบว่า author match กับ user ในระบบหรือไม่
  - Match โดย email (direct หรือผ่าน authors table)
  - Match โดย Thai name
  - Match โดย English name
- ✅ **สร้าง/Update Author Record**: เมื่อ match สำเร็จ จะสร้างหรือ update record ใน `authors` table
- 📝 **ใช้ Direct Insert**: ใช้ `$this->db->table('publication_authors')->insert()` โดยตรง

### ตัวอย่าง Code

```php
// DashboardController::saveOrcidPublication()
foreach ($authors as $author) {
    // ... matching logic ...

    // Insert into publication_authors only when author is successfully matched
    if ($matchedUser && !empty($authorId)) {
        $this->db->table('publication_authors')->insert([
            'publication_id' => $publicationId,
            'author_id' => $authorId,
            'author_name' => $authorNameToSave,
            'author_email' => $matchedUser['email'] ?? $authorEmail,
            'author_order' => $order,
            'uid' => $matchedUser['uid'] ?? null
        ]);
    } else {
        log_message('info', "Skipped publication_author insert (not matched)");
    }
}
```

---

## สรุปความแตกต่าง

| หัวข้อ                     | publications/create                   | /dashboard/orcid                                |
| -------------------------- | ------------------------------------- | ----------------------------------------------- |
| **Controller**             | `PublicationController`               | `DashboardController`                           |
| **Method**                 | `store()`                             | `syncOrcidWithDoi()` → `saveOrcidPublication()` |
| **การ Match Author**       | ❌ ไม่มีการ match                     | ✅ มีการ match กับ user ในระบบ                  |
| **การบันทึก Author**       | ✅ บันทึกทุก author                   | ✅ บันทึกเฉพาะ author ที่ match สำเร็จ          |
| **การสร้าง Author Record** | ❌ ไม่สร้าง (ใช้ author_id ที่มีอยู่) | ✅ สร้าง/update record ใน `authors` table       |
| **การบันทึก Email**        | ✅ บันทึก email จาก form              | ✅ บันทึก email จาก matched user                |
| **การบันทึก UID**          | ✅ บันทึก uid ถ้ามี                   | ✅ บันทึก uid จาก matched user                  |
| **การบันทึก Name**         | ✅ บันทึก name จาก form               | ✅ บันทึก name จาก matched user (Thai/English)  |

---

## ปัญหาที่อาจเกิดขึ้น

### 1. publications/create

- **ปัญหา**: บันทึก author ที่ไม่ match กับ user ในระบบ → ทำให้ `uid` และ `author_id` เป็น `null`
- **ผลกระทบ**: ไม่สามารถ map author กับ user ในระบบได้
- **แก้ไข**: ควรเพิ่มการ match author ก่อนบันทึก (เช่นเดียวกับ `/dashboard/orcid`)

### 2. /dashboard/orcid

- **ปัญหา**: บันทึกเฉพาะ author ที่ match สำเร็จ → author ที่ไม่ match จะไม่ถูกบันทึก
- **ผลกระทบ**: อาจสูญเสียข้อมูล author ที่ไม่ match
- **แก้ไข**: ควรบันทึก author ที่ไม่ match ด้วย (แต่ไม่ต้องมี `uid` และ `author_id`)

---

## คำแนะนำ

1. **ปรับ `publications/create`** ให้มีการ match author ก่อนบันทึก (เช่นเดียวกับ `/dashboard/orcid`)
2. **ปรับ `/dashboard/orcid`** ให้บันทึก author ที่ไม่ match ด้วย (แต่ไม่ต้องมี `uid` และ `author_id`)
3. **ใช้ Logic เดียวกัน** สำหรับการ match author ในทั้งสองหน้า
