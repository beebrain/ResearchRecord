#!/usr/bin/env bash
# อัปโหลด Research Record จากเครื่อง local ไป win-kc (ครั้งแรกหรือ full sync)
# ไม่ต้อง git push ก่อน — ใช้ tar+scp ผ่าน Tailscale SSH
#
# Usage:
#   WIN_KC_PASS='...' ./scripts/scp-deploy-win-kc-rr.sh
#   WIN_KC_PASS='...' ./scripts/scp-deploy-win-kc-rr.sh --init-iis
#
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
HOST="${WIN_KC_HOST:-100.74.66.65}"
USER="${WIN_KC_USER:-Administrator}"
REPO_WIN="${WIN_KC_RR_REPO:-C:/inetpub/ResearchRecord}"
IIS_APP="${WIN_KC_IIS_APP:-recordresearch}"
IIS_SITE="${WIN_KC_IIS_SITE:-sci.uru.ac.th}"
PASS="${SSHPASS:-${WIN_KC_PASS:-${FTP_PASS:-}}}"
INIT_IIS=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --init-iis) INIT_IIS=1; shift ;;
    -h|--help) echo "Usage: WIN_KC_PASS='...' $0 [--init-iis]"; exit 0 ;;
    *) echo "Unknown: $1" >&2; exit 2 ;;
  esac
done

[[ -n "$PASS" ]] || { echo "WIN_KC_PASS required" >&2; exit 1; }
command -v sshpass >/dev/null || { echo "sshpass required" >&2; exit 1; }
command -v tailscale >/dev/null || { echo "tailscale required" >&2; exit 1; }

REPO_PS="${REPO_WIN//\//\\}"
ARCHIVE="$(mktemp /tmp/rr-deploy.XXXXXX.tgz)"
trap 'rm -f "${ARCHIVE}"' EXIT

mkdir -p ~/.ssh
ssh-keyscan -t ed25519,rsa,ecdsa -H "$HOST" win-kc49a7sh1gd.tail08d9fa.ts.net 2>/dev/null >> ~/.ssh/known_hosts || true
export SSHPASS="$PASS"

SSH=(sshpass -e ssh -F /dev/null -o StrictHostKeyChecking=accept-new
  -o UserKnownHostsFile="${HOME}/.ssh/known_hosts"
  -o PubkeyAuthentication=no -o PreferredAuthentications=password,keyboard-interactive
  -o ProxyCommand="tailscale nc %h 22" -o ConnectTimeout=60 "${USER}@${HOST}")

SCP=(sshpass -e scp -F /dev/null -o StrictHostKeyChecking=accept-new
  -o UserKnownHostsFile="${HOME}/.ssh/known_hosts"
  -o PubkeyAuthentication=no -o PreferredAuthentications=password,keyboard-interactive
  -o ProxyCommand="tailscale nc %h 22" -o ConnectTimeout=120)

echo "=== packing local tree ==="
tar -C "${ROOT_DIR}" -czf "${ARCHIVE}" \
  --exclude='./vendor' \
  --exclude='./writable/cache' \
  --exclude='./writable/logs' \
  --exclude='./writable/session' \
  --exclude='./writable/debugbar' \
  --exclude='./writable/uploads' \
  --exclude='./.git' \
  --exclude='./node_modules' \
  --exclude='./deploy-backups' \
  --exclude='./scripts/ftp_rr.env' \
  --exclude='./.env' \
  .

echo "=== ensure target dir on server ==="
"${SSH[@]}" "powershell -NoProfile -Command \"New-Item -ItemType Directory -Force -Path '${REPO_PS}' | Out-Null\""

echo "=== upload archive ==="
REMOTE_TGZ='C:/inetpub/ResearchRecord/_deploy_upload.tgz'
"${SCP[@]}" "${ARCHIVE}" "${USER}@${HOST}:${REMOTE_TGZ}"

echo "=== extract on server ==="
"${SSH[@]}" "cmd /c \"cd /d ${REPO_WIN//\//\\\\} && tar -xzf _deploy_upload.tgz && del /f _deploy_upload.tgz && if not exist .env if exist .env.win-kc copy /Y .env.win-kc .env\""

if [[ "$INIT_IIS" -eq 1 ]]; then
  echo "=== create IIS application ==="
  "${SSH[@]}" "powershell -NoProfile -ExecutionPolicy Bypass -File '${REPO_PS}\\scripts\\win-kc-on-server-install.ps1' -ProjectRoot '${REPO_PS}' -IisAppName '${IIS_APP}'"
fi

echo "=== composer + migrate on server ==="
"${SSH[@]}" "cmd /c \"cd /d ${REPO_WIN//\//\\\\} && (composer install --no-dev --no-interaction || echo skip-composer) && (php spark cache:clear || echo skip-cache) && (php spark migrate --all || echo skip-migrate)\""

echo "=== verify ==="
curl -sS -o /dev/null -w "HTTP %{http_code}\n" --max-time 20 -L "https://sci.uru.ac.th/${IIS_APP}/index.php/auth/login" || true
echo "=== done — edit ${REPO_WIN}/.env on server (DB, encryption.key, secrets) ==="
