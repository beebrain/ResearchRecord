# แผนแก้ SSO — ชื่อผู้ใช้ไม่ sync จาก newScience → RR

## อาการ

- User login ผ่าน newScience → RR สำเร็จ แต่ `rac.user` มี `gf_name=User`, `thai_name=User`
- Portal/newScience log มีชื่อจริง (เช่น กฤษณา คิดดี)
- ตัวอย่าง: `kritsana.kid@live.uru.ac.th`, `benjamard.rat@live.uru.ac.th`, `sasikan.mou@live.uru.ac.th`

## สาเหตุหลัก (newScience)

`ResearchRecordSsoBridge::displayNameFromUser()` อ่านเฉพาะ:

```php
$user['tf_name'] ?? $user['first_name_th'] ?? $user['first_name_en'] ?? 'User'
```

แต่ตอน redirect ไป RR ใช้ **row จากตาราง user ของ newScience** (`userModel->find()`) ไม่ใช่ raw Portal response

| ฟิลด์ใน Portal log | มีค่า | ใช้ใน displayNameFromUser? |
|---|---|---|
| `gf_name` / `gl_name` | ✅ (ชื่อไทย/อังกฤษ) | ❌ ไม่อ่าน |
| `tf_name` / `tl_name` | มักว่างตอน user ใหม่ | ✅ อ่านก่อน → ว่าง → fallback `"User"` |

**Timeline kritsana (30 มิ.ย.):**

1. 18:33:57 — Portal ส่ง `gf_name=กฤษณา`, `gl_name=คิดดี`
2. 18:33:57 — newScience สร้าง user uid=687 (tf_name อาจยังว่าง)
3. 18:33:57 — `entryUrlForUser($user)` → ชื่อใน token = `"User"`
4. 18:33:58 — RR `ssoEntry` insert ล้มเหลว (ไม่มี `created_at`)
5. 18:36:46 — RR `ssoEntry` สำเร็จ → บันทึก `gf_name=User`

## สาเหตุรอง (RR)

1. `ssoEntry()` ไม่ set `created_at` → insert fail ครั้งแรก (`created_at NOT NULL`)
2. รับแค่ `email` + `name` จาก token — ไม่มี `login_uid`, `gf_name`, `gl_name` แยก
3. ไม่ refresh user เดิมถ้า `gf_name=User` แล้ว login ซ้ำ

---

## แผนแก้ (เรียงตามความสำคัญ)

### Phase 1 — Backfill ข้อมูลที่เสียแล้ว (ทันที)

รัน `scripts/backfill-sso-user-names-from-ns-logs.php` บน win-kc:

```powershell
cd C:\inetpub\ResearchRecord
php scripts/backfill-sso-user-names-from-ns-logs.php --dry-run
php scripts/backfill-sso-user-names-from-ns-logs.php
```

- อ่าน `C:\inetpub\newscience\writable\logs\oauth_login-*.log`
- ดึง `[callback_user]` → `email`, `login_uid`, `gf_name`, `gl_name`
- อัปเดตเฉพาะ `rac.user` ที่ `gf_name='User'` และ `profile_customer='newscience_sso'`

### Phase 2 — แก้ newScience (ป้องกันคน�ใหม่)

**ไฟล์:** `app/Libraries/ResearchRecordSsoBridge.php`

```php
private static function displayNameFromUser(array $user): string
{
    $thai = trim(($user['tf_name'] ?? '') . ' ' . ($user['tl_name'] ?? ''));
    if ($thai !== '') {
        return $thai;
    }
    $fromGfGl = trim(($user['gf_name'] ?? '') . ' ' . ($user['gl_name'] ?? ''));
    if ($fromGfGl !== '' && strcasecmp($fromGfGl, 'User') !== 0) {
        return $fromGfGl;
    }
    return (string) ($user['first_name_th'] ?? $user['first_name_en'] ?? 'User');
}
```

**แนะนำเพิ่ม (ดีกว่า):** ส่ง fields เพิ่มใน SSO payload จาก Portal response ตอน OAuth callback (ก่อน destroy session):

```json
{
  "email": "...",
  "name": "กฤษณา คิดดี",
  "login_uid": "kritsana.kid",
  "gf_name": "กฤษณา",
  "gl_name": "คิดดี",
  "exp": 1234567890
}
```

### Phase 3 — แก้ RR `ssoEntry()` (รับ payload รวย + insert ถูก)

- set `created_at` / `updated_at` ตอนสร้าง user
- อ่าน `login_uid`, `gf_name`, `gl_name` จาก payload ถ้ามี
- ถ้า user มีอยู่และ `gf_name=User` แต่ payload มีชื่อจริง → update
- log ชื่อที่รับ: `ssoEntry success email=... name=...`

### Phase 4 — Verify

- [ ] login user ใหม่ผ่าน RR → `rac.user` มีชื่อจริง + `login_uid` จาก Portal
- [ ] RR log ไม่มี `failed to create user`
- [ ] backfill script dry-run = 0 rows เหลือ `gf_name=User`

---

## ไฟล์ที่เกี่ยวข้อง

| Repo | ไฟล์ |
|---|---|
| newScience | `app/Libraries/ResearchRecordSsoBridge.php` |
| newScience | `app/Controllers/OAuthController.php` (intent=researchrecord redirect) |
| RR | `app/Controllers/AuthenController.php` → `ssoEntry()` |
| RR | `scripts/backfill-sso-user-names-from-ns-logs.php` |
