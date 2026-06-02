# URU Portal login — ResearchRecord + newScience

ResearchRecord และ newScience ใช้ **URU Portal OAuth** ชุดเดียวกัน (`client_id = sci`) บน `sci.uru.ac.th`

## วิธีเข้าใช้งาน (production — แนะนำ)

### Login ผ่าน newScience แล้วกลับ RR (SSO hop)

1. Login ที่ newScience ผ่าน URU Portal แล้วคลิก **กบศ** → `/go-research-record`
2. หรือเปิด `https://sci.uru.ac.th/ResearchRecord/` → (ถัดไป) redirect ไป newScience OAuth
3. newScience สร้าง HMAC token → `.../ResearchRecord/index.php/auth/sso-entry?token=...`
4. RR ตรวจลายเซ็นแล้วสร้าง session

### OAuth ตรงที่ RR (ไม่ใช้ใน production sci — ปิดด้วย `uruoauth.enabled = false`)

## ตั้งค่า ResearchRecord (.env) — production บน sci.uru.ac.th

ดู template: [`.env.production.example`](../.env.production.example) และ [SERVER_DEPLOY.md](./SERVER_DEPLOY.md)

```ini
app.baseURL = 'https://sci.uru.ac.th/ResearchRecord/'

# Login ผ่าน newScience เท่านั้น
newscience.baseUrl = "https://sci.uru.ac.th"
newscience_sso.enabled = true
newscience_sso.sharedSecret = <เดียวกับ researchrecordsso.sharedSecret>
uruoauth.enabled = false

RESEARCH_API_KEY = <เดียวกับ newScience>
RESEARCH_SYNC_HMAC_SECRET = <เดียวกับ newScience>
```

## ตั้งค่า newScience (.env)

```ini
researchrecordsso.baseUrl = https://sci.uru.ac.th/ResearchRecord
researchrecordsso.sharedSecret = <เดียวกับ newscience_sso.sharedSecret ใน RR>
researchrecordsso.enabled = true

RESEARCH_API_BASE_URL = https://sci.uru.ac.th/ResearchRecord/index.php
RESEARCH_API_KEY = <เดียวกับ RR>
RESEARCH_SYNC_HMAC_SECRET = <เดียวกับ RR>
```

## ลงทะเบียนที่ URU Portal

เพิ่ม **redirect URI** สำหรับ client `sci`:

| Environment | Callback URL |
|-------------|--------------|
| Production | `https://sci.uru.ac.th/ResearchRecord/index.php/oauth` |
| Local (ถ้าทดสอบ) | `http://127.0.0.1/ResearchRecord/public/index.php/oauth` |

newScience ใช้ callback เดิม: `https://sci.uru.ac.th/index.php/oauth`

## ไฟล์สำคัญ

| Repo | ไฟล์ |
|------|------|
| ResearchRecord | `app/Config/UruPortalOAuth.php`, `app/Services/OAuthService.php`, `app/Controllers/AuthenController.php` |
| ResearchRecord | `app/Config/NewscienceSso.php`, `AuthenController::ssoEntry()` |
| newScience | `app/Config/ResearchRecordSso.php`, `Admin\Auth::goResearchRecord` |
| newScience | `RESEARCH_API_*` ใน `.env` (sync CV/API) |

## ตรวจสอบ

- **OAuth ตรง:** `/auth/login` → Portal → กลับมา dashboard ได้
- **SSO hop:** login newScience → คลิก กบศ → เปิด RR dashboard โดยไม่ถาม login อีก
- **Secret ต้องตรง:** `newscience_sso.sharedSecret` (RR) = `researchrecordsso.sharedSecret` (newScience)

## Local dev

- OAuth ปิดได้ด้วย `uruoauth.enabled = false` แล้วใช้ `/dev/login`
- SSO hop ต้องตั้ง `researchrecordsso.baseUrl` ชี้ local RR และ secret ตรงกัน
