# Email as primary key — migration status

> ✅ **DEPLOYED TO PRODUCTION 2026-06-01** (win-kc, migration batch 1, `Migrated On 2026-06-01 08:27:18`).
> Verified healthy 2026-06-04: `user` PK = `email`, `uid` column dropped, 429 users, 0 duplicate emails, views OK, site live at `https://sci.uru.ac.th/recordresearch/` (200).
> **ห้ามรัน `run-email-pk-migration.php` / `php spark migrate` ซ้ำบน production** — migration นี้ทำลายล้างและกู้ได้ทาง restore เท่านั้น. Health check แบบ read-only: `php scripts/verify-email-pk-state.php`.

## Done (local `rac`)

- `user.email` is **PRIMARY KEY**; column `user.uid` **removed**
- Child tables use `*_email` columns (e.g. `teacher_email`, `created_by_email`, `chair_email`)
- Views recreated: `teacher_curriculum_view`, `publication_view` (uses `author_emails`, not `author_uids`)
- Session: `user_email` + `user_id` (same normalized email string)
- Helpers: `UserIdentity`, `identity_helper` (`current_user_email()`)

## Run on server — ✅ เสร็จแล้ว (อย่ารันซ้ำ)

รันบน production ไปแล้ว 2026-06-01 (ดู banner ด้านบน). คำสั่งด้านล่างเก็บไว้เป็นประวัติ/สำหรับ environment ใหม่เท่านั้น:

```bash
# historical — DO NOT re-run on prod (already migrated)
php scripts/run-email-pk-migration.php
# if interrupted: DELETE orphan rows then
php scripts/continue-email-pk-migration.php
```

## Code status — ✅ migrated (email-first)

> **อัปเดต 2026-06-04:** หัวข้อเดิม "Code still using uid (must fix before production)" **ล้าสมัยแล้ว**
> สแกนโค้ดจริงทั้ง `app/` แล้ว — controllers/models/views resolve identity ด้วย **email เป็นหลัก** และเก็บ `uid` ไว้เป็น fallback กันพลาดเท่านั้น **ไม่มีบั๊กพังจริง**

ref `uid` / `user_uid` / `teacher_uid` ที่ยังเหลือ จัดประเภทได้ดังนี้ (ทั้งหมด **ปลอดภัย**):

| ประเภท | ตัวอย่าง | สถานะ |
|--------|----------|-------|
| Output alias | `'uid' => $user['email']` | ตั้งใจ — backward-compat API |
| ชื่อ field เดิม (ค่าเป็น email) | `name="user_uid"`, `getPost('user_uid')` → `UserIdentity::normalizeEmail()` | ตั้งใจ |
| Fallback กันพลาด | `currentUserEmail()` ลอง `email` ก่อน แล้วค่อย `uid` | ปลอดภัย |
| ไฟล์ migration เก่า | `app/Database/Migrations/*` | ห้ามแก้ (ประวัติ) |

ยืนยัน: **ไม่พบ** cast `(int)`/`intval` บน uid และ **ไม่พบ** SQL join/where บนคอลัมน์ `uid` ที่ถูกลบ (ที่เจอคือ `login_uid` — คอลัมน์ LINE login คนละตัว)

**สิ่งที่เหลือจริงอย่างเดียว = รัน migration บน production** → ดู [DEPLOY_EMAIL_PK_PRODUCTION.md](DEPLOY_EMAIL_PK_PRODUCTION.md)

## API breaking change

Curriculum detail API **no longer returns `uid`** on teachers/chair — **email only**.

## Rollback

Restore MySQL dump from before migration. Code revert to commit before email-PK branch.
