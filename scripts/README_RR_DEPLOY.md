# Research Record Deploy

## Production (sci.uru.ac.th/recordresearch) — win-kc

Path: `C:\inetpub\ResearchRecord` → IIS app **`/recordresearch`** → `public/`

### วิธี A — ส่ง tarball ผ่าน Tailscale (ไม่ต้อง SSH)

**Mac:**

```bash
cd /Users/boobee/Docker/projects/ResearchRecord
./scripts/pack-for-win-kc.sh
tailscale file cp deploy/win-kc-rr.tgz win-kc49a7sh1gd:
```

**win-kc (RDP / PowerShell บนเครื่อง):**

```powershell
powershell -ExecutionPolicy Bypass -File C:\inetpub\ResearchRecord\scripts\win-kc-fetch-from-tailscale-and-install.ps1
```

(ครั้งแรกถ้ายังไม่มีโฟลเดอร์: `tailscale file get` แล้ว extract ด้วยมือ หรือ clone repo ก่อน)

### วิธี B — SCP ผ่าน SSH (ต้องมีรหัส Administrator)

```bash
WIN_KC_PASS='...' ./scripts/scp-deploy-win-kc-rr.sh --init-iis
WIN_KC_PASS='...' ./scripts/scp-deploy-win-kc-rr.sh
```

### วิธี C — git pull บน server (แนะนำ)

```bash
WIN_KC_PASS='...' ./scripts/git-pull-win-kc-rr.sh --init
WIN_KC_BRANCH=feature/rr-email-identity WIN_KC_PASS='...' ./scripts/git-pull-win-kc-rr.sh
```

Server-side setup: `scripts/win-kc-on-server-install.ps1`  
Full checklist: [docs/SERVER_DEPLOY.md](../docs/SERVER_DEPLOY.md)

Verify:

```bash
curl -I https://sci.uru.ac.th/recordresearch/index.php/auth/login
```

---

## Legacy FTP (research.academic / 202.29.52.124)

Use these scripts when uploading Research Record changes to the **old** FTP host. They
create a local backup and a remote `.bak.<timestamp>` copy before replacing any
file.

## One-Time Setup

```bash
cp scripts/ftp_rr.example.env scripts/ftp_rr.env
```

Edit `scripts/ftp_rr.env`:

```bash
FTP_HOST=202.29.52.124
FTP_PORT=21
FTP_USER=rac
FTP_PASS=your-password
FTP_REMOTE_ROOT=research_academic
```

Never commit `scripts/ftp_rr.env`.

## Deploy

From the repository root:

```bash
chmod +x scripts/deploy-rr-with-backup.sh
./scripts/deploy-rr-with-backup.sh --file app/Controllers/CvSyncApiController.php
```

The script will:

1. Download the current remote file to `deploy-backups/RR/<timestamp>/...`.
2. Upload that same file to production as `<filename>.bak.<timestamp>`.
3. Upload the new local file.
4. Download the uploaded file and compare SHA-256 with the local file.

If any backup or verification step fails, the script exits before continuing.

## Windows Alternative

```powershell
powershell -ExecutionPolicy Bypass -File scripts/deploy-rr-with-backup.ps1 `
  -Files app/Controllers/CvSyncApiController.php
```

## Rollback

Use the backup suffix printed by the deploy command.

```bash
./scripts/deploy-rr-with-backup.sh \
  --restore app/Controllers/CvSyncApiController.php \
  --backup-suffix .bak.20260524_143000
```

Or manually upload the local copy from:

```text
deploy-backups/RR/<timestamp>/app/Controllers/CvSyncApiController.php
```

## Post-Deploy Smoke Test

After deploying `CvSyncApiController.php`, verify:

1. `GET /index.php/api/cv-sync/publications-sync-bundle-by-email` returns `contributors[]` and bibliographic fields.
2. `POST /index.php/api/cv-sync/publications-sync-bundle-by-email` accepts an empty payload and returns 200.
3. A single-person sync on newScience is idempotent on the second run.
