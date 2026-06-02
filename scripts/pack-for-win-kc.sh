#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="${ROOT}/deploy/win-kc-rr.tgz"
mkdir -p "${ROOT}/deploy"
"${ROOT}/scripts/build-win-kc-env.sh"
tar -C "${ROOT}" -czf "${OUT}" \
  --exclude='./vendor' \
  --exclude='./writable/cache' \
  --exclude='./writable/logs' \
  --exclude='./writable/session' \
  --exclude='./.git' \
  --exclude='./node_modules' \
  --exclude='./deploy' \
  --exclude='./scripts/ftp_rr.env' \
  --exclude='./.env' \
  .
echo "Packed: ${OUT} ($(wc -c < "${OUT}" | tr -d ' ') bytes)"
