# Production Deploy Checklist — Email-as-Primary-Key Migration

> ✅ **เสร็จแล้ว — รันบน production (win-kc) 2026-06-01, verified healthy 2026-06-04.**
> เอกสารนี้เก็บไว้เป็น runbook อ้างอิง/สำหรับ environment ใหม่. **ห้ามรัน Phase 2 ซ้ำบน prod ที่ migrate แล้ว** (irreversible). ตรวจสุขภาพแบบ read-only: `php scripts/verify-email-pk-state.php`. URL จริง: `https://sci.uru.ac.th/recordresearch/`.

**เป้าหมาย:** ขึ้น migration `20260530180000_UserEmailPrimaryKey` บน production โดยปลอดภัยและย้อนกลับได้

> ⚠️ **Migration นี้ irreversible** — `down()` โยน exception เจตนา การ rollback ทำได้ทาง **restore DB backup เท่านั้น** (ลบ `user.uid` + uid FKs, promote `user.email` เป็น PRIMARY KEY, recreate views) ห้ามรันถ้ายังไม่มี backup ที่ verify แล้ว

**Production ที่ active:** win-kc — Windows / IIS
`https://sci.uru.ac.th/ResearchRecord/` → physical `C:\inetpub\ResearchRecord\public`
DB: MySQL `localhost` / schema `rac`

**Branch ที่ deploy:** `feature/rr-email-identity`

อ้างอิง: [SERVER_DEPLOY.md](SERVER_DEPLOY.md) · [EMAIL_PRIMARY_KEY_MIGRATION.md](EMAIL_PRIMARY_KEY_MIGRATION.md) · [scripts/README_RR_DEPLOY.md](../scripts/README_RR_DEPLOY.md)

---

## Go / No-Go gates (ต้องผ่านทุกข้อก่อนเริ่ม)

- [ ] มี **maintenance window** (ผู้ใช้ 427 คน — มี downtime ช่วง migrate)
- [ ] **Local `rac` clone** รัน migration ผ่านครบแล้ว (สถานะปัจจุบัน ✅ ตาม EMAIL_PRIMARY_KEY_MIGRATION.md)
- [ ] เข้าถึง server ได้ (RDP / `WIN_KC_PASS` สำหรับ scripts)
- [ ] รู้รหัส MySQL `root`/`rac` บน server (สำหรับ `mysqldump` + restore)
- [ ] Branch `feature/rr-email-identity` push ขึ้น GitHub แล้ว (`git push origin feature/rr-email-identity`)

---

## Phase 0 — Backup (บังคับ, ห้ามข้าม)

บน server (PowerShell ที่ `C:\inetpub\ResearchRecord`):

```powershell
$ts = Get-Date -Format "yyyyMMdd_HHmmss"
$bk = "C:\inetpub\_backups\rac_pre_emailpk_$ts.sql"
New-Item -ItemType Directory -Force -Path "C:\inetpub\_backups" | Out-Null

# ปรับ path mysqldump / รหัสตามเครื่อง (XAMPP มัก C:\xampp\mysql\bin)
& "C:\xampp\mysql\bin\mysqldump.exe" -u root -p --routines --triggers --single-transaction rac > $bk

# ตรวจว่า dump สมบูรณ์ (ต้องเจอ DROP TABLE / มีขนาด > 0)
Get-Item $bk | Select-Object Length
Select-String -Path $bk -Pattern "Dump completed" | Select-Object -Last 1
```

- [ ] ไฟล์ dump ขนาด > 0 และมีบรรทัด `Dump completed`
- [ ] **copy dump ออกนอก server** (อีก 1 ที่ — Tailscale / cloud) เผื่อเครื่องพัง

**Baseline metrics (ก่อน migrate):**

```powershell
cd C:\inetpub\ResearchRecord
& "C:\xampp\mysql\bin\mysql.exe" -u root -p rac -e "SELECT COUNT(*) users FROM user; SELECT COUNT(*) dup FROM (SELECT email FROM user GROUP BY email HAVING COUNT(*)>1) x;"
# integrity ก่อน migrate (สคริปต์นี้ join บน u.uid — ต้องรัน *ก่อน* uid ถูกลบ)
& "C:\xampp\mysql\bin\mysql.exe" -u root -p rac < scripts\verify-schema-integrity.sql
```

- [ ] `dup` = **0** (ถ้าไม่ 0 → หยุด, email ซ้ำทำให้ promote PK ล้มเหลว)
- [ ] orphan checks ทุกตัว = 0 (จดค่า user count ไว้เทียบหลัง migrate)

---

## Phase 1 — Deploy โค้ด (branch email-identity)

จาก Mac (git pull บน server — แนะนำ):

```bash
cd /Users/boobee/Docker/projects/ResearchRecord
WIN_KC_BRANCH=feature/rr-email-identity WIN_KC_PASS='<Administrator>' ./scripts/git-pull-win-kc-rr.sh
```

หรือบน server โดยตรง:

```powershell
cd C:\inetpub\ResearchRecord
git fetch origin
git checkout feature/rr-email-identity
git pull
composer install --no-dev --optimize-autoloader
```

- [ ] `git log -1` ตรง commit `cc43969` (logout flow fix) หรือใหม่กว่า
- [ ] เว็บยังเปิดได้ก่อน migrate: `curl -I https://sci.uru.ac.th/ResearchRecord/index.php/auth/login` → `200/302`

> โค้ดถูกเขียนแบบ **email-first + uid fallback** อยู่แล้ว จึงยังทำงานได้ทั้งก่อน/หลัง migrate (ช่วงคาบเกี่ยวไม่พัง)

---

## Phase 2 — รัน migration (จุด irreversible)

```powershell
cd C:\inetpub\ResearchRecord

# วิธีหลัก
php scripts\run-email-pk-migration.php

# ถ้าถูกขัดจังหวะกลางคัน (รันต่อทีละ step, idempotent)
#   1) ลบ orphan rows ตามที่ log แจ้ง (teacher_curriculum / user_profile)
#   2) php scripts\continue-email-pk-migration.php
```

- [ ] output จบด้วย `Done.` ไม่มี SQL error
- [ ] `php spark migrate:status` แสดง `20260530180000` เป็น migrated

---

## Phase 3 — Verify หลัง migrate

```powershell
# โครงสร้างใหม่: email เป็น PK, ไม่มี uid
& "C:\xampp\mysql\bin\mysql.exe" -u root -p rac -e "SHOW KEYS FROM user WHERE Key_name='PRIMARY'; SHOW COLUMNS FROM user LIKE 'uid';"
# verify email-identity (สคริปต์ post-migration — parity ของ publications/cv)
& "C:\xampp\mysql\bin\mysql.exe" -u root -p rac < scripts\verify-email-identity-migration.sql
```

- [ ] PRIMARY KEY ของ `user` = `email`
- [ ] `SHOW COLUMNS ... LIKE 'uid'` → **ว่าง** (ลบแล้ว)
- [ ] views `publication_view`, `teacher_curriculum_view` query ได้ (ใช้ `author_emails` / `teacher_email`)
- [ ] user count = baseline จาก Phase 0

**Smoke test ทาง UI/API:**

- [ ] login (SSO จาก newScience) → เข้า dashboard ได้
- [ ] `dashboard/cv` โหลด, section count เท่าเดิมของ test user
- [ ] เพิ่ม/แก้/ลบ CV entry → persist ถูก email
- [ ] `GET .../api/publications-sync-bundle-by-email` → 200, มี `contributors`
- [ ] newScience: `php spark publications:sync-rr --email=<test>` รัน **2 รอบ** → รอบ 2 = `skipped_unchanged` (idempotent)
- [ ] เฝ้า `writable/logs` 15 นาที — ไม่มี error uid/column

---

## Rollback (ถ้า Phase 2/3 พัง)

```powershell
# 1) restore DB จาก dump Phase 0
& "C:\xampp\mysql\bin\mysql.exe" -u root -p rac < C:\inetpub\_backups\rac_pre_emailpk_<ts>.sql
# 2) revert โค้ดกลับ commit ก่อน email-PK branch
cd C:\inetpub\ResearchRecord
git checkout master   # หรือ commit ก่อน migration
```

| อาการ | การแก้ |
|-------|--------|
| `dup email` > 0 ตอน promote PK | หยุดก่อน Phase 2 — merge/แก้ email ซ้ำใน `user` ก่อน |
| migration ค้างกลางคัน | ลบ orphan ตาม log → `continue-email-pk-migration.php` |
| NS sync พัง | restore DB + revert `CvSyncApiController` + `PublicationModel` ก่อน (coupling สูงสุด) |
| เว็บ 500 หลัง migrate | ดู `writable/logs`; ถ้าเป็น schema → restore DB |

---

## หลัง deploy สำเร็จ

- [ ] อัปเดต [EMAIL_PRIMARY_KEY_MIGRATION.md](EMAIL_PRIMARY_KEY_MIGRATION.md): เปลี่ยนสถานะ "Run on server" → done + วันที่
- [ ] เก็บ dump Phase 0 ไว้ ≥ 30 วัน
- [ ] บันทึก policy: **identity = email canonical**, การเปลี่ยน `user.email` ต้องผ่าน admin process (cascade `owner_email_norm` + `publication_authors.author_email`)
