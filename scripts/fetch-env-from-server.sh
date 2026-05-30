#!/usr/bin/env bash
# Download production .env to .envserver (read-only; does not upload).
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${ROOT_DIR}/scripts/ftp_rr.env"
EXAMPLE_ENV_FILE="${ROOT_DIR}/scripts/ftp_rr.example.env"
DEST="${ROOT_DIR}/.envserver"

if [[ -f "${EXAMPLE_ENV_FILE}" ]]; then
  # shellcheck disable=SC1090
  source "${EXAMPLE_ENV_FILE}"
fi
if [[ -f "${ENV_FILE}" ]]; then
  # shellcheck disable=SC1090
  source "${ENV_FILE}"
fi

FTP_HOST="${FTP_HOST:-202.29.52.124}"
FTP_PORT="${FTP_PORT:-21}"
FTP_USER="${FTP_USER:-rac}"
FTP_REMOTE_ROOT="${FTP_REMOTE_ROOT:-research_academic}"

if [[ -z "${FTP_PASS:-}" ]] && [[ -t 0 ]]; then
  read -r -s -p "FTP password for ${FTP_USER}@${FTP_HOST}: " FTP_PASS
  echo
fi

if [[ -z "${FTP_PASS:-}" ]]; then
  echo "FTP_PASS is required. Set it in scripts/ftp_rr.env or export FTP_PASS." >&2
  exit 2
fi

NETRC_FILE="$(mktemp)"
trap 'rm -f "${NETRC_FILE}"' EXIT
chmod 600 "${NETRC_FILE}"
cat >"${NETRC_FILE}" <<EOF
machine ${FTP_HOST}
login ${FTP_USER}
password ${FTP_PASS}
EOF

root="${FTP_REMOTE_ROOT#/}"
root="${root%/}"
url="ftp://${FTP_HOST}:${FTP_PORT}/${root}/.env"

curl --fail --silent --show-error --netrc-file "${NETRC_FILE}" "${url}" --output "${DEST}"
echo "Downloaded ${url} -> .envserver"
