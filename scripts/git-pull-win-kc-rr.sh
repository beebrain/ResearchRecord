#!/usr/bin/env bash
# git pull Research Record บน win-kc (C:\inetpub\ResearchRecord) ผ่าน Tailscale SSH
#
# Usage:
#   WIN_KC_PASS='...' ./scripts/git-pull-win-kc-rr.sh
#   WIN_KC_PASS='...' ./scripts/git-pull-win-kc-rr.sh --init   # clone ครั้งแรก
#   WIN_KC_BRANCH=feature/rr-email-identity WIN_KC_PASS='...' ./scripts/git-pull-win-kc-rr.sh
#
set -euo pipefail

HOST="${WIN_KC_HOST:-100.74.66.65}"
USER="${WIN_KC_USER:-Administrator}"
REPO="${WIN_KC_RR_REPO:-C:/inetpub/ResearchRecord}"
BRANCH="${WIN_KC_BRANCH:-master}"
REPO_URL="${WIN_KC_RR_GIT:-https://github.com/beebrain/ResearchRecord.git}"
IIS_APP="${WIN_KC_IIS_APP:-recordresearch}"
IIS_SITE="${WIN_KC_IIS_SITE:-sci.uru.ac.th}"
IIS_APPPOOL="${WIN_KC_IIS_APPPOOL:-DefaultAppPool}"
PASS="${SSHPASS:-${WIN_KC_PASS:-${FTP_PASS:-}}}"
INIT=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --init) INIT=1; shift ;;
    -h|--help)
      echo "Usage: WIN_KC_PASS='...' $0 [--init]"
      exit 0
      ;;
    *) echo "Unknown: $1" >&2; exit 2 ;;
  esac
done

if ! command -v tailscale >/dev/null 2>&1; then
  echo "ไม่พบ tailscale CLI" >&2
  exit 1
fi

if ! tailscale status 2>/dev/null | grep -q '100.74.66.65'; then
  echo "win-kc (100.74.66.65) ไม่อยู่ใน tailnet — เปิด Tailscale ก่อน" >&2
  exit 1
fi

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || { echo "Missing command: $1" >&2; exit 2; }
}

mkdir -p ~/.ssh
ssh-keyscan -t ed25519,rsa,ecdsa -H "$HOST" win-kc49a7sh1gd.tail08d9fa.ts.net 2>/dev/null >> ~/.ssh/known_hosts || true

REPO_WIN="${REPO//\//\\\\}"

SSH_OPTS=(
  -F /dev/null
  -o StrictHostKeyChecking=accept-new
  -o UserKnownHostsFile="${HOME}/.ssh/known_hosts"
  -o ProxyCommand="tailscale nc %h 22"
  -o ConnectTimeout=30
)

SSH_BASE=()
if [[ -n "$PASS" ]]; then
  require_cmd sshpass
  export SSHPASS="$PASS"
  SSH_BASE=(
    sshpass -e ssh
    "${SSH_OPTS[@]}"
    -o PubkeyAuthentication=no
    -o PreferredAuthentications=password,keyboard-interactive
    "${USER}@${HOST}"
  )
else
  SSH_BASE=(
    ssh
    "${SSH_OPTS[@]}"
    "${USER}@${HOST}"
  )
fi

if [[ "$INIT" -eq 1 ]]; then
  echo "=== init: clone repo (ครั้งแรก) ==="
  # NOTE: IIS app creation varies by server/site naming; keep init minimal and safe.
  # If directory already has files (from tar/scp deploy), `git clone .` will fail.
  # In that case, initialize git in-place and hard reset to remote branch.
  REMOTE_INIT="if not exist ${REPO_WIN//\//\\\\} mkdir ${REPO_WIN//\//\\\\} && cd /d ${REPO_WIN//\//\\\\} && if not exist .git (git init && git remote add origin ${REPO_URL} && git fetch origin && git checkout -B ${BRANCH} origin/${BRANCH}) && if not exist .env if exist .env.production.example copy /Y .env.production.example .env"
  "${SSH_BASE[@]}" "cmd /c \"${REMOTE_INIT}\""
fi

REMOTE_PULL="cd /d ${REPO_WIN} && git rev-parse --short HEAD && git fetch origin && git checkout ${BRANCH} && git pull origin ${BRANCH} && git rev-parse --short HEAD && git log -1 --oneline"

echo "=== git pull RR บน ${USER}@${HOST} (${REPO} branch ${BRANCH}) ==="
"${SSH_BASE[@]}" "${REMOTE_PULL}"

echo "=== composer + cache + migrate ==="
"${SSH_BASE[@]}" "cd /d ${REPO_WIN} && (composer install --no-dev --no-interaction 2>nul || echo skip-composer) && (php spark cache:clear 2>nul || echo skip-cache) && (php spark migrate --all 2>nul || echo skip-migrate)"

echo "=== verify URL (จาก Mac) ==="
curl -sS -o /dev/null -w "ResearchRecord HTTP %{http_code}\n" --max-time 15 -L "https://sci.uru.ac.th/${IIS_APP}/index.php/auth/login" || true

echo "=== done ==="
