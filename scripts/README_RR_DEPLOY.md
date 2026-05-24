# Research Record Safe FTP Deploy

Use these scripts when uploading Research Record changes to production. They
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
