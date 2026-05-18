# Checklist สำหรับ Deploy Dashboard ขึ้น Website

## ⚠️ ปัญหาที่พบ: 404 Error สำหรับ API Endpoints

### ไฟล์ที่ต้อง Deploy (5 ไฟล์)

#### 1. ไฟล์ใหม่ (ต้อง Upload)
- [ ] `app/Controllers/AdminDashboardController.php` ⚠️ **สำคัญมาก**
- [ ] `public/assets/js/admin-dashboard.js`

#### 2. ไฟล์ที่แก้ไข (ต้อง Replace)
- [ ] `app/Controllers/AdminController.php`
- [ ] `app/Config/Routes.php`
- [ ] `app/Views/admin/Dashboard/dashboard.php`

---

## ขั้นตอนการ Deploy

### Step 1: Upload ไฟล์ใหม่
```bash
# ตรวจสอบว่าไฟล์มีอยู่จริง
app/Controllers/AdminDashboardController.php
public/assets/js/admin-dashboard.js
```

### Step 2: Replace ไฟล์ที่แก้ไข
```bash
app/Controllers/AdminController.php
app/Config/Routes.php
app/Views/admin/Dashboard/dashboard.php
```

### Step 3: Clear Cache
```bash
# ลบ cache ของ CodeIgniter
rm -rf writable/cache/*
# หรือ
writable/cache/* (ลบทั้งหมด)
```

### Step 4: ตรวจสอบ Permissions
```bash
# ไฟล์ควรมี permission 644
chmod 644 app/Controllers/AdminDashboardController.php
chmod 644 public/assets/js/admin-dashboard.js

# Directory ควรมี permission 755
chmod 755 app/Controllers/
chmod 755 public/assets/js/
```

---

## ตรวจสอบหลัง Deploy

### 1. ตรวจสอบว่าไฟล์อยู่ที่ถูกต้อง
```
✅ app/Controllers/AdminDashboardController.php ต้องมีอยู่
✅ public/assets/js/admin-dashboard.js ต้องมีอยู่
```

### 2. ตรวจสอบ Routes
เปิด Browser Console (F12) และตรวจสอบ:
```
✅ API Endpoints ถูกต้อง
✅ ไม่มี 404 errors
```

### 3. ทดสอบ API โดยตรง
ลองเปิด URL นี้ใน browser (ต้อง login ก่อน):
```
http://research.academic.uru.ac.th/public/index.php/api/dashboard/stats
```

ควรเห็น JSON response แทน 404

---

## Troubleshooting

### ถ้ายัง 404 อยู่:

#### 1. ตรวจสอบว่า Controller ถูก Load
- ตรวจสอบว่า `app/Controllers/AdminDashboardController.php` ถูก upload แล้ว
- ตรวจสอบ namespace: `namespace App\Controllers;`
- ตรวจสอบ class name: `class AdminDashboardController`

#### 2. ตรวจสอบ Routes
- เปิด `app/Config/Routes.php`
- ตรวจสอบว่า routes group `api/dashboard` มีอยู่
- ตรวจสอบว่า routes ใหม่ถูกเพิ่มแล้ว

#### 3. ตรวจสอบ Autoload
- ตรวจสอบ `app/Config/Autoload.php` (ถ้ามี)
- ตรวจสอบว่า Composer autoload ทำงาน

#### 4. ตรวจสอบ Logs
- ดู `writable/logs/log-YYYY-MM-DD.log`
- หา error messages ที่เกี่ยวข้อง

#### 5. ตรวจสอบ Base URL
- เปิด `app/Config/App.php`
- ตรวจสอบ `$baseURL` ถูกต้องหรือไม่

---

## API Endpoints ที่ต้องทำงาน

| Endpoint | Method | Controller |
|----------|--------|------------|
| `/api/dashboard/stats` | GET | AdminDashboardController::getStatistics |
| `/api/dashboard/summary` | GET | AdminDashboardController::getSummary |
| `/api/dashboard/curriculum-readiness` | GET | AdminDashboardController::getCurriculumReadiness |
| `/api/dashboard/curriculum-publications` | GET | AdminDashboardController::getCurriculumPublications |

---

## Quick Fix: ถ้ายัง 404

ลองทดสอบว่า Controller ทำงานหรือไม่:

1. สร้าง test route ใน `Routes.php`:
```php
$routes->get('test-dashboard', 'AdminDashboardController::getStatistics');
```

2. เปิด URL: `http://research.academic.uru.ac.th/public/index.php/test-dashboard`

3. ถ้าได้ response = Controller ทำงาน
4. ถ้ายัง 404 = Controller ไม่ถูก load หรือ path ไม่ถูกต้อง

---

## สรุป

**ไฟล์ที่สำคัญที่สุด:**
1. ⚠️ `app/Controllers/AdminDashboardController.php` - **ต้องมีไฟล์นี้**
2. `app/Config/Routes.php` - ต้องมี routes ใหม่
3. `app/Views/admin/Dashboard/dashboard.php` - ใช้ `site_url()` แล้ว

**ตรวจสอบ:**
- [ ] ไฟล์ทั้งหมดถูก upload แล้ว
- [ ] Cache ถูก clear แล้ว
- [ ] Permissions ถูกต้อง
- [ ] Controller methods มีอยู่ครบ
