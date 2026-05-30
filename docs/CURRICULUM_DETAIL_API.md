# Curriculum Detail API – คู่มือการใช้งาน

เอกสารนี้อธิบาย API สำหรับดึง **รายละเอียดหลักสูตร** รวม **อาจารย์ผู้รับผิดชอบ (สูงสุด 5 คน)** และ **ผลงานตีพิมพ์ที่อนุมัติแล้ว** ของแต่ละคน

## Interactive docs (Swagger UI — คล้าย FastAPI)

เปิดใน browser **ไม่ต้อง login**:

| URL | คำอธิบาย |
|-----|----------|
| **`/docs`** | Swagger UI |
| **`/api/openapi.json`** | OpenAPI 3.0 spec (JSON) |

กด **Authorize** → ใส่ `X-Curriculum-Api-Token` ก่อน Try it out

---

- **Controller:** `app/Controllers/ApiController.php` → `apiGetCurriculumDetailByName()`
- **Filter:** `app/Filters/CurriculumApiTokenFilter.php`
- **Auth:** Token เฉพาะ (`CURRICULUM_API_TOKEN`) — **ไม่ใช้ session login**

---

## Authentication (Token)

### ตั้งค่า server (.env)

```bash
# สร้าง token ใหม่ (รันครั้งเดียว):
# openssl rand -hex 32

CURRICULUM_API_TOKEN=your-secret-token-here
```

### ส่ง token ใน request

| วิธี | Header |
|------|--------|
| **แนะนำ** | `X-Curriculum-Api-Token: <token>` |
| ทางเลือก | `Authorization: Bearer <token>` |

**ไม่ต้อง login RR** — **ไม่ใช้** `X-API-KEY` ของ public API อื่น (เป็นคนละ token)

### ตัวอย่าง curl

```bash
curl -s -H "X-Curriculum-Api-Token: YOUR_TOKEN" \
  "https://your-rr-host/index.php/api/curriculum-detail-by-name?curriculum_name=เทคโนโลยีอาหาร"
```

### Error

| HTTP | error | เมื่อ |
|------|-------|-------|
| 401 | `UNAUTHORIZED` | ไม่ส่ง token / token ผิด |
| 503 | `API_NOT_CONFIGURED` | ยังไม่ตั้ง `CURRICULUM_API_TOKEN` บน server |

---

## Base URL

| สภาพแวดล้อม | ตัวอย่าง |
|-------------|----------|
| Local (Docker) | `http://localhost/researchRecord/public/index.php` |
| Production | ตาม `app.baseURL` ใน `.env` |

Path เต็ม:

```
GET {baseURL}/api/curriculum-detail-by-name
```

---

## Endpoint

| Route | Method | คำอธิบาย |
|-------|--------|----------|
| `/api/curriculum-detail-by-name` | GET | ค้นหาหลักสูตรจากชื่อ แล้วส่งข้อมูลหลักสูตร + ผู้รับผิดชอบ + ผลงาน |

---

## Query parameters

| Parameter | บังคับ | คำอธิบาย |
|-----------|--------|----------|
| `curriculum_name` | **ใช่** | ชื่อหลักสูตร — ค้นหาแบบ **exact ก่อน** แล้ว **partial** (`LIKE %name%`) |
| `faculty_id` | ไม่ | รหัสคณะ — ใช้เมื่อชื่อหลักสูตรซ้ำหลายคณะ |

---

## กฎข้อมูล (Business rules)

### อาจารย์ผู้รับผิดชอบ (สูงสุด 5 คน)

ดึงจากตาราง `teacher_curriculum` (สถานะ active) และรวม **ประธานหลักสูตร** (`curriculum.chair_id`) ถ้ายังไม่อยู่ใน list

**ลำดับความสำคัญ:**

1. ประธานหลักสูตร (`chair_id`)
2. `coordinator` → `instructor` → `assistant`
3. เรียงตามชื่อไทย
4. **ตัดเหลือไม่เกิน 5 คน**

### ผลงานวิจัย

- ส่ง **รายละเอียดเต็ม** ต่ออาจารย์ (ใน array `publications`)
- เฉพาะผลงานที่ **`approve = 1`** (อนุมัติแล้ว)
- จับคู่ผู้แต่งจาก `publication_authors.author_email` (และ alias email ใน `authors`) — ไม่นับแค่ `created_by`

---

## Success response (200)

```json
{
  "success": true,
  "curriculum": {
    "id": 12,
    "name": "วิทยาการคอมพิวเตอร์",
    "code": "CS",
    "degree_level": "bachelor",
    "faculty": {
      "id": 3,
      "name": "คณะวิทยาศาสตร์",
      "code": "SCI"
    },
    "chair": {
      "uid": 45,
      "email": "chair@university.ac.th",
      "name_thai": "ผศ.ดร. สมชาย ใจดี",
      "name_english": "Asst. Prof. Dr. Somchai Jaidee"
    }
  },
  "responsible_teachers": [
    {
      "order": 1,
      "uid": 45,
      "email": "chair@university.ac.th",
      "name_thai": "ผศ.ดร. สมชาย ใจดี",
      "name_english": "Asst. Prof. Dr. Somchai Jaidee",
      "role": "coordinator",
      "position": "ประธานหลักสูตร",
      "is_chair": true,
      "publication_count": 8,
      "publications": [
        {
          "id": 101,
          "title": "ชื่อผลงาน",
          "abstract": "...",
          "publication_type": "journal",
          "source": "Journal Name",
          "publication_year": "2024",
          "publication_year_be": 2567,
          "publication_month": null,
          "volume": "12",
          "pages": "1-10",
          "doi": "10.1234/example",
          "isbn": null,
          "keywords": "keyword1, keyword2",
          "authors": "ชื่อผู้แต่ง",
          "authors_thai": "ชื่อผู้แต่ง",
          "authors_english": "Author Name",
          "approve": 1,
          "created_at": "2024-06-01 10:00:00"
        }
      ]
    }
  ],
  "summary": {
    "responsible_count": 5,
    "total_publications": 42,
    "unique_publications": 38,
    "publications_filter": "approved_only"
  },
  "retrieved_at": "2026-05-30 14:30:00"
}
```

### ฟิลด์สำคัญ

| ฟิลด์ | ความหมาย |
|-------|----------|
| `responsible_teachers[].order` | ลำดับ 1–5 |
| `responsible_teachers[].role` | ค่าจาก DB: `coordinator`, `instructor`, `assistant` หรือ `chair` |
| `responsible_teachers[].position` | ป้ายภาษาไทย (ประธานหลักสูตร, ผู้ประสานงานหลักสูตร, ฯลฯ) |
| `summary.total_publications` | รวมทุกรายการ (นับซ้ำถ้าหลายคน co-author งานเดียวกัน) |
| `summary.unique_publications` | จำนวน publication ไม่ซ้ำทั้งหลักสูตร |

---

## Error responses

### 400 – ไม่ส่งชื่อหลักสูตร

```json
{
  "success": false,
  "error": "MISSING_PARAMETER",
  "message": "curriculum_name parameter is required"
}
```

### 404 – ไม่พบหลักสูตร

```json
{
  "success": false,
  "error": "CURRICULUM_NOT_FOUND",
  "message": "No active curriculum matched the given name"
}
```

### 409 – ชื่อตรงหลายหลักสูตร

```json
{
  "success": false,
  "error": "AMBIGUOUS_CURRICULUM",
  "message": "Multiple curricula matched; pass faculty_id to disambiguate",
  "candidates": [
    {
      "id": 12,
      "name": "วิทยาการคอมพิวเตอร์",
      "code": "CS",
      "faculty_id": 3,
      "faculty_name": "คณะวิทยาศาสตร์",
      "faculty_code": "SCI"
    },
    {
      "id": 28,
      "name": "วิทยาการคอมพิวเตอร์",
      "code": "CS-ED",
      "faculty_id": 5,
      "faculty_name": "คณะครุศาสตร์",
      "faculty_code": "EDU"
    }
  ]
}
```

**วิธีแก้:** เรียกซ้ำพร้อม `faculty_id` จาก `candidates`

### 500 – ข้อผิดพลาดฝั่งเซิร์ฟเวอร์

```json
{
  "success": false,
  "error": "SERVER_ERROR",
  "message": "An error occurred while processing the request"
}
```

---

## ตัวอย่างการเรียกใช้

### 1. JavaScript (fetch)

```javascript
const params = new URLSearchParams({
  curriculum_name: 'วิทยาการคอม',
});

const res = await fetch(`/api/curriculum-detail-by-name?${params}`, {
  headers: {
    Accept: 'application/json',
    'X-Curriculum-Api-Token': 'YOUR_TOKEN',
  },
});

const data = await res.json();

if (res.status === 409) {
  console.log('เลือกคณะ:', data.candidates);
} else if (data.success) {
  console.log(data.curriculum.name, data.responsible_teachers);
}
```

### 2. curl

```bash
curl -s -H "X-Curriculum-Api-Token: YOUR_TOKEN" \
  "http://localhost/ResearchRecord/public/index.php/api/curriculum-detail-by-name?curriculum_name=วิทยาการคอมพิวเตอร์" \
  | jq .
```

### 3. กรณีชื่อซ้ำ — ระบุคณะ

```bash
curl -s -H "X-Curriculum-Api-Token: YOUR_TOKEN" \
  "http://localhost/ResearchRecord/public/index.php/api/curriculum-detail-by-name?curriculum_name=วิทยาการคอมพิวเตอร์&faculty_id=3" \
  | jq .
```

### 4. jQuery

```javascript
$.ajax({
  url: '/api/curriculum-detail-by-name',
  data: { curriculum_name: 'วิทยาการคอมพิวเตอร์', faculty_id: 3 },
  headers: { 'X-Curriculum-Api-Token': 'YOUR_TOKEN' },
  dataType: 'json',
}).done(function (data) {
  if (data.success) {
    console.log(data.responsible_teachers);
  }
});
```

---

## Flow การค้นหาชื่อหลักสูตร

```
curriculum_name
      │
      ▼
  exact match (ไม่สน case)?
      │ ใช่ ──► 1 รายการ ──► 200
      │ หลายรายการ ──► 409 + candidates
      │
      ▼
  partial match (LIKE)?
      │ 0 ──► 404
      │ 1 ──► 200
      │ หลาย ──► 409 + candidates
```

ถ้าส่ง `faculty_id` มาด้วย จะกรองเฉพาะหลักสูตรในคณะนั้นก่อนตัดสิน 404/409

---

## เปรียบเทียบกับ API อื่น

| API | Auth | Input | Output |
|-----|------|-------|--------|
| `/api/public/publications-by-email` | API Key | email | ผลงาน 1 คน |
| `/api/public/faculty-personnel` | API Key | faculty_id/code | บุคลากรทั้งคณะ |
| **`/api/curriculum-detail-by-name`** | **Curriculum token** | **ชื่อหลักสูตร** | **หลักสูตร + ผู้รับผิดชอบ 5 คน + ผลงาน approve** |

ดู public API เพิ่มเติม: [PUBLIC_API.md](./PUBLIC_API.md)

---

## ข้อควรทราบ

1. **หลักสูตรต้อง `status = 1`** (active) จึงจะถูกค้นหา
2. **อาจารย์ต้องอยู่ใน `teacher_curriculum`** และ `user.active = 1` — ถ้าไม่มีใครใน list แต่มี `chair_id` จะได้ประธานอย่างน้อย 1 คน
3. **ผลงานที่ยังไม่อนุมัติ** (`approve` เป็น `null` หรือ `0`) **จะไม่ปรากฏ**
4. Response อาจใหญ่ถ้าอาจารย์มีผลงานมาก — ฝั่ง client ควร paginate หรือแสดงทีละคนถ้าจำเป็น (API ปัจจุบันส่งทั้งหมดต่อคน)

---

## ไฟล์ที่เกี่ยวข้อง

| ไฟล์ | บทบาท |
|------|--------|
| `app/Controllers/ApiController.php` | endpoint + format response |
| `app/Models/CurriculumModel.php` | ค้นหาชื่อหลักสูตร |
| `app/Models/UserModel.php` | `getCurriculumResponsibleTeachers()` |
| `app/Models/PublicationModel.php` | `getApprovedPublicationsByCanonicalEmail()` |
| `app/Filters/CurriculumApiTokenFilter.php` | ตรวจ `CURRICULUM_API_TOKEN` |

---

## Changelog

| วันที่ | รายการ |
|--------|--------|
| 2026-05-30 | เปลี่ยน auth เป็น token (`CURRICULUM_API_TOKEN`) แทน session login |
| 2026-05-30 | สร้างเอกสาร — endpoint `/api/curriculum-detail-by-name` |
