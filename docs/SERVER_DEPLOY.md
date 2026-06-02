# Deploy Research Record บน win-kc (sci.uru.ac.th)

ลำดับงาน: **วางที่ `C:\inetpub\ResearchRecord`** → `.env` → IIS Application → ทดสอบ URL

---

## 1. โครงสร้างบน server (ระดับเดียวกับ newScience ใน inetpub)

```
C:\inetpub\
  newscience\                   ← sci.uru.ac.th (site → public\)
    public/
    app/
    .env
  ResearchRecord\               ← โปรเจกต์ RR (repo แยก)
    public/                     ← IIS Application "ResearchRecord" ชี้มาที่นี่
      index.php
      web.config
    app/
    writable/
    .env
    vendor/
```

URL ยังเป็น `https://sci.uru.ac.th/ResearchRecord/` — IIS **Application alias** บน site newScience ชี้ physical path ไป `C:\inetpub\ResearchRecord\public` (ไม่จำเป็นต้องมีโฟลเดอร์ใต้ `newscience\public\`)

---

## 2. วางโค้ดครั้งแรก

```powershell
cd C:\inetpub
git clone <repo-url> ResearchRecord
cd ResearchRecord
composer install --no-dev
copy .env.production.example .env
notepad .env
php spark key:generate
```

---

## 3. ค่าที่ต้องแก้ใน `C:\inetpub\ResearchRecord\.env`

| ตัวแปร | หมายเหตุ |
|--------|----------|
| `database.default.password` | รหัส MySQL (มัก `localhost`, DB `rac`) |
| `encryption.key` | จาก `php spark key:generate` |
| `newscience_sso.sharedSecret` | **ต้องตรง** `researchrecordsso.sharedSecret` ใน `C:\inetpub\newscience\.env` |
| `RESEARCH_API_KEY` | **ต้องตรง** newScience |
| `RESEARCH_SYNC_HMAC_SECRET` | **ต้องตรง** newScience |

```ini
app.baseURL = 'https://sci.uru.ac.th/ResearchRecord/'
newscience.baseUrl = "https://sci.uru.ac.th"
newscience_sso.enabled = true
uruoauth.enabled = false
```

---

## 4. IIS Application

Site **sci.uru.ac.th** → `C:\inetpub\newscience\public`

| รายการ | ค่า |
|--------|-----|
| Alias | `ResearchRecord` |
| Physical path | **`C:\inetpub\ResearchRecord\public`** |
| App pool | ใช้ pool เดียวกับ newScience หรือแยก |

```powershell
Import-Module WebAdministration
New-WebApplication -Site "sci.uru.ac.th" -Name "ResearchRecord" `
  -PhysicalPath "C:\inetpub\ResearchRecord\public" `
  -ApplicationPool "DefaultAppPool"
```

**สิทธิ์ writable:**

```powershell
icacls "C:\inetpub\ResearchRecord\writable" /grant "IIS AppPool\DefaultAppPool:(OI)(CI)M"
```

---

## 5. newScience `.env` (`C:\inetpub\newscience\.env`)

```ini
researchrecordsso.baseUrl = "https://sci.uru.ac.th/ResearchRecord"
researchrecordsso.sharedSecret = "pisit_secret"
researchrecordsso.enabled = true

RESEARCH_API_BASE_URL = https://sci.uru.ac.th/ResearchRecord/index.php
RESEARCH_API_KEY = <เดียวกับ C:\inetpub\ResearchRecord\.env>
RESEARCH_SYNC_HMAC_SECRET = <เดียวกับ RR>
```

### ลบเฉพาะโฟลเดอร์เก่าใน newScience (ถ้ามี)

RR อยู่ที่ **`C:\inetpub\ResearchRecord`** — **ไม่** อยู่ใน repo newScience

**ลบได้เฉพาะโฟลเดอร์** — **config ไม่ต้องลบ** (`researchrecordsso.*`, `RESEARCH_API_*`, `app/Config/ResearchRecordSso.php` ยังใช้ตามเดิม)

บน server ถ้ามี stub ค้าง:

```powershell
# ตรวจ
Test-Path "C:\inetpub\newscience\public\ResearchRecord"

# ลบ/เปลี่ยนชื่อ (backup ก่อน)
Rename-Item "C:\inetpub\newscience\public\ResearchRecord" "ResearchRecord.bak"
```

IIS Application ต้องชี้ `C:\inetpub\ResearchRecord\public` ไม่ใช่โฟลเดอร์ใต้ newScience

---

## 6. อัปเดตโค้ด

### จาก Mac — อัปโหลด local ทั้งก้อน (ไม่ต้อง git push ก่อน)

```bash
cd /Users/boobee/Docker/projects/ResearchRecord

# ครั้งแรก + สร้าง IIS Application
WIN_KC_PASS='รหัส Administrator' ./scripts/scp-deploy-win-kc-rr.sh --init-iis

# อัปเดตโค้ดครั้งถัดไป
WIN_KC_PASS='...' ./scripts/scp-deploy-win-kc-rr.sh
```

### จาก Mac — git pull บน server (หลัง push GitHub แล้ว)

```bash
cd /Users/boobee/Docker/projects/ResearchRecord

# ครั้งแรก (clone + IIS + .env template)
WIN_KC_PASS='รหัส Administrator' ./scripts/git-pull-win-kc-rr.sh --init

# ครั้งถัดไป
WIN_KC_PASS='...' ./scripts/git-pull-win-kc-rr.sh

# branch ที่มี email-identity migration
WIN_KC_BRANCH=feature/rr-email-identity WIN_KC_PASS='...' ./scripts/git-pull-win-kc-rr.sh
```

รหัสผ่านเดียวกับที่ใช้ `newscience/scripts/git-pull-win-kc.sh` ได้ (ตัวแปร `FTP_PASS` หรือ `WIN_KC_PASS`)

### บน server โดยตรง

```powershell
cd C:\inetpub\newscience && git pull
cd C:\inetpub\ResearchRecord && git pull && php spark migrate --all
```

หรือครั้งแรกบน server:

```powershell
powershell -ExecutionPolicy Bypass -File C:\inetpub\ResearchRecord\scripts\win-kc-setup-researchrecord.ps1
```

---

## 7. ตรวจหลัง deploy

```powershell
curl -I https://sci.uru.ac.th/ResearchRecord/
curl -I https://sci.uru.ac.th/ResearchRecord/index.php/auth/login
cd C:\inetpub\ResearchRecord && php spark migrate:status
```

---

## 8. Checklist

- [ ] `C:\inetpub\ResearchRecord\` มีโครง CI4 ครบ
- [ ] `.env` จาก `.env.production.example`
- [ ] IIS Application alias `ResearchRecord` → `C:\inetpub\ResearchRecord\public`
- [ ] `writable/` เขียนได้
- [ ] `C:\inetpub\newscience\.env` ชี้ API/SSO ตรงกับ RR
- [ ] ไม่มีโฟลเดอร์เก่า `newscience\public\ResearchRecord\` (ลบ/เปลี่ยนชื่อถ้ามี)

---

## ไฟล์อ้างอิง

| ไฟล์ | 用途 |
|------|------|
| `.env.production.example` | template `.env` |
| `docs/IIS_SCI_RESEARCHRECORD.md` | IIS รายละเอียด |
| `public/web.config` | rewrite ใน child app |
