# ผลการตรวจสอบ Dashboard ของ Admin แต่ละ Level

## สรุปผลการทดสอบ

### 1. **ระบบ Permission ทำงานถูกต้อง**

จากการทดสอบโดยใช้ Backdoor Login และ God Mode พบว่า:

#### ✅ **Super Admin / God Mode**
- **Total Publications**: 410
- **Total Authors**: 115  
- **Active Faculties**: 14
- **แสดงข้อมูล**: เห็นสถิติทั้งหมดของทุกคณะและหลักสูตร
- **กราฟและ Chart**: แสดงข้อมูลครบถ้วน
- **Publication Summary by Curriculum**: แสดงข้อมูลของทุกหลักสูตร

#### ✅ **Regular User (Pisit)**
- **Total Publications**: 0
- **Total Authors**: 0
- **Active Faculties**: 0
- **แสดงข้อมูล**: เห็นเฉพาะผลงานของตนเอง (ซึ่ง Pisit ยังไม่มี)
- **กราฟและ Chart**: ว่างเปล่า

---

## 2. **ปัญหาที่พบ**

### ⚠️ **ไม่มี Admin User ในระบบ**
- จากการกรอง "Admins Only" ในหน้า Backdoor พบว่า **Admin Users = 0**
- ทุก User ในระบบยังไม่ได้ถูกตั้งค่า `role` หรือ `admin = 1`
- ต้องใช้ "Quick Admin Access" เพื่อเข้า God Mode แทน

### ⚠️ **ยังขาดการทดสอบ Faculty Admin**
- ยังไม่มี User ที่มี `role = 'faculty_admin'` เพื่อทดสอบว่าจะเห็นเฉพาะข้อมูลของคณะที่ตนดูแลหรือไม่

---

## 3. **โครงสร้าง Permission ในระบบ**

### Logic ใน `AdminController::getStatistics()`

```php
// Super Admin - เห็นทั้งหมด
if ($this->session->get('god_mode') === true || RoleHelper::isSuperAdmin($user)) {
    $stats = $this->getStats(); // ทุกข้อมูล
    $activeFaculties = $this->getActiveFacultiesCount(); // ทุกคณะ
}

// Faculty Admin - เห็นเฉพาะคณะที่ดูแล
elseif (RoleHelper::isFacultyAdmin($user)) {
    $managedFaculties = RoleHelper::getManagedFaculties($user);
    $stats = $this->getStatsByFaculties($managedFaculties); // เฉพาะคณะที่ดูแล
    $activeFaculties = count($managedFaculties);
}

// Regular User - เห็นเฉพาะของตัวเอง
else {
    $stats = $this->getStatsByUser($userId); // เฉพาะผลงานของตัวเอง
    $activeFaculties = 0;
}
```

### สถิติที่นับ

#### **Super Admin**
- `totalPublications`: นับจาก `publication_view` ทั้งหมด
- `totalAuthors`: นับ `DISTINCT author_id` จาก `publication_authors`
- `activeFaculties`: นับทุกคณะที่มี active status

#### **Faculty Admin**
- `totalPublications`: นับจาก `publication_view WHERE faculty_id IN (managed_faculties)`
- `totalAuthors`: นับ authors ที่มี publication ในคณะที่ดูแล
- `activeFaculties`: นับจำนวนคณะที่ตนดูแล

#### **Regular User**
- `totalPublications`: นับ publication ที่ user เป็น author (ตาม email matching)
- `totalAuthors`: นับ authors ที่ร่วมงานด้วยใน publications ของ user
- `activeFaculties`: 0 (Regular user ไม่เห็นข้อมูลระดับคณะ)

---

## 4. **การนับสถิติตาม Curriculum**

### ปัญหาปัจจุบัน: `getCurriculumData()` ไม่ filter ตาม Role

```php
public function getCurriculumData() {
    // ❌ ปัญหา: ไม่มีการเช็ค Role ของ User
    // Query รวมทุก curriculum โดยไม่มี permission filter
    
    $sql = "
        SELECT
            p.source as faculty,
            'General' as curriculum,
            COUNT(DISTINCT p.id) as publicationCount,
            COUNT(DISTINCT pa.author_name) as authorCount,
            COALESCE(SUM(p.citations), 0) as citations
        FROM publications p
        LEFT JOIN publication_authors pa ON p.id = pa.publication_id
        WHERE p.source IS NOT NULL AND p.source != ''
    ";
    
    // ไม่มี WHERE clause ที่ filter ตาม user role!
}
```

### ✅ **แนวทางแก้ไข**

ต้องเพิ่ม Permission Filter ใน `getCurriculumData()` เหมือนกับ `getStatistics()`:

```php
public function getCurriculumData() {
    try {
        // Get current user and role
        $userId = $this->session->get('user_id');
        $user = $this->userModel->find($userId);
        
        $facultyFilter = $this->request->getGet('faculty');
        
        // ✅ Filter ตาม Role
        if ($this->session->get('god_mode') === true || RoleHelper::isSuperAdmin($user)) {
            // Super Admin: ดูทุกหลักสูตร
            $curriculaStats = $this->curriculumModel->getWithPublicationStats($facultyFilter);
        } 
        elseif (RoleHelper::isFacultyAdmin($user)) {
            // Faculty Admin: ดูเฉพาะหลักสูตรในคณะที่ดูแล
            $managedFaculties = RoleHelper::getManagedFaculties($user);
            $curriculaStats = $this->curriculumModel->getWithPublicationStatsByFaculties($managedFaculties, $facultyFilter);
        } 
        else {
            // Regular User: ดูเฉพาะหลักสูตรที่ตนเองสอนและมีผลงาน
            $curriculaStats = $this->curriculumModel->getWithPublicationStatsByUser($userId);
        }
        
        return $this->response->setJSON([
            'success' => true,
            'data' => $curriculaStats
        ]);
    } catch (\Exception $e) {
        log_message('error', 'Get curriculum data error: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Error loading curriculum data: ' . $e->getMessage()
        ]);
    }
}
```

---

## 5. **Steps ในการแก้ไข**

### Step 1: แก้ไข `AdminController::getCurriculumData()`
- เพิ่ม Role checking
- Filter curriculum ตาม permission ของ user

### Step 2: เพิ่ม Methods ใน `CurriculumModel`
- `getWithPublicationStatsByFaculties($facultyIds, $facultyFilter = null)` - สำหรับ Faculty Admin
- `getWithPublicationStatsByUser($userId)` - สำหรับ Regular User  

### Step 3: สร้าง Test Admin Users
- ใช้ SQL script `create_test_admin_users.sql` เพื่อสร้าง users แต่ละ role
- ทดสอบการ login และตรวจสอบสถิติ

### Step 4: ทดสอบแต่ละ Level
1. **Super Admin** → ควรเห็นทุกข้อมูล
2. **Faculty Admin** → ควรเห็นเฉพาะคณะที่ดูแล
3. **Regular User** → ควรเห็นเฉพาะผลงานของตนเอง

---

## 6. **สถานะปัจจุบัน**

### ✅ **ส่วนที่ทำงานถูกต้อง**
- `getStatistics()` - มี permission filter ครบถ้วน
- `getDashboardPublications()` - มี permission filter ครบถ้วน
- `getFacultyData()` - มี permission filter
- `getYearData()` - มี permission filter

### ❌ **ส่วนที่ต้องแก้ไข**
- `getCurriculumData()` - **ยังไม่มี permission filter**
- ต้องเพิ่ม methods ใน `CurriculumModel` สำหรับ filter ตาม faculty และ user

---

## 7. **ข้อมูลเพิ่มเติมจาก Screenshot**

### God Mode Dashboard (Super Admin)
- URL: `/admin/dashboard`
- Total Publications: **410**
- Total Authors: **115**
- Active Faculties: **14**
- Curriculum cards แสดงหลักสูตรต่างๆ พร้อม publication count ที่ถูกต้อง

### Regular User Dashboard (Pisit)
- URL: `/admin/dashboard`  
- Total Publications: **0**
- Total Authors: **0**
- Active Faculties: **0**
- Curriculum cards แสดงหลักสูตร แต่ publication count = 0 ทั้งหมด

**สังเกต**: Regular User ยัง redirect ไปที่ `/admin/dashboard` ซึ่งอาจควร redirect ไปที่ `/dashboard` แทน

---

## สรุป

ระบบ Dashboard มี Permission System ที่ออกแบบมาอย่างดี แต่ **`getCurriculumData()`** ยังขาดการ filter ตาม Role ทำให้:
- **Super Admin/God Mode**: เห็นข้อมูลที่ถูกต้อง ✅
- **Faculty Admin**: จะเห็นข้อมูลของทุกคณะ (ผิด) ❌
- **Regular User**: จะเห็นข้อมูลของทุกคณะ (ผิด) ❌

**แนวทางแก้ไข**: ให้เพิ่ม Permission Filter ใน `getCurriculumData()` และสร้าง methods เพิ่มเติมใน `CurriculumModel` เพื่อ support การ filter ตาม Faculty และ User
