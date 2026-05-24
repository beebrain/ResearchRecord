#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${ROOT_DIR}/scripts/ftp_rr.env"
EXAMPLE_ENV_FILE="${ROOT_DIR}/scripts/ftp_rr.example.env"

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
LOCAL_BACKUP_ROOT="${LOCAL_BACKUP_ROOT:-deploy-backups/RR}"
KEEP_LOCAL_BACKUPS="${KEEP_LOCAL_BACKUPS:-10}"
TIMESTAMP="${TIMESTAMP:-$(date +%Y%m%d_%H%M%S)}"
MODE="deploy"
RESTORE_PATH=""

MANIFEST=(
  "app/Controllers/CvSyncApiController.php"
)

usage() {
  cat <<'EOF'
Usage:
  scripts/deploy-rr-with-backup.sh [--file app/Controllers/CvSyncApiController.php]
  scripts/deploy-rr-with-backup.sh --restore app/Controllers/CvSyncApiController.php [--backup-suffix .bak.YYYYMMDD_HHMMSS]

Environment:
  Copy scripts/ftp_rr.example.env to scripts/ftp_rr.env and set FTP_PASS.

Safety:
  Deploy mode downloads the current remote file, stores a local backup,
  uploads the backup back to the server as <file>.bak.<timestamp>, then uploads
  the new file and verifies SHA-256 by downloading it again.
EOF
}

FILES=()
BACKUP_SUFFIX=""
while [[ $# -gt 0 ]]; do
  case "$1" in
    --file)
      [[ $# -ge 2 ]] || { echo "--file requires a path" >&2; exit 2; }
      FILES+=("$2")
      shift 2
      ;;
    --restore)
      [[ $# -ge 2 ]] || { echo "--restore requires a path" >&2; exit 2; }
      MODE="restore"
      RESTORE_PATH="$2"
      shift 2
      ;;
    --backup-suffix)
      [[ $# -ge 2 ]] || { echo "--backup-suffix requires a suffix" >&2; exit 2; }
      BACKUP_SUFFIX="$2"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
      usage >&2
      exit 2
      ;;
  esac
done

if [[ ${#FILES[@]} -eq 0 ]]; then
  FILES=("${MANIFEST[@]}")
fi

if [[ -z "${FTP_PASS:-}" ]]; then
  read -r -s -p "FTP password for ${FTP_USER}@${FTP_HOST}: " FTP_PASS
  echo
fi

if [[ -z "${FTP_PASS:-}" ]]; then
  echo "FTP_PASS is required." >&2
  exit 2
fi

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || { echo "Missing command: $1" >&2; exit 2; }
}

require_cmd curl
require_cmd shasum

NETRC_FILE="$(mktemp)"
VERIFY_TMP="$(mktemp -d)"
trap 'rm -f "${NETRC_FILE}"; rm -rf "${VERIFY_TMP}"' EXIT
chmod 600 "${NETRC_FILE}"
cat >"${NETRC_FILE}" <<EOF
machine ${FTP_HOST}
login ${FTP_USER}
password ${FTP_PASS}
EOF

remote_url_for() {
  local rel="${1#/}"
  local root="${FTP_REMOTE_ROOT#/}"
  root="${root%/}"
  printf 'ftp://%s:%s/%s/%s' "${FTP_HOST}" "${FTP_PORT}" "${root}" "${rel}"
}

curl_ftp() {
  curl --fail --silent --show-error --netrc-file "${NETRC_FILE}" "$@"
}

sha256_file() {
  shasum -a 256 "$1" | awk '{print $1}'
}

download_remote() {
  local rel="$1"
  local dest="$2"
  mkdir -p "$(dirname "${dest}")"
  curl_ftp "$(remote_url_for "${rel}")" --output "${dest}"
}

upload_file() {
  local src="$1"
  local rel="$2"
  curl_ftp --ftp-create-dirs -T "${src}" "$(remote_url_for "${rel}")"
}

verify_upload() {
  local local_file="$1"
  local rel="$2"
  local verify_file="${VERIFY_TMP}/$(basename "${rel}").verify"
  download_remote "${rel}" "${verify_file}"
  local local_hash remote_hash
  local_hash="$(sha256_file "${local_file}")"
  remote_hash="$(sha256_file "${verify_file}")"
  if [[ "${local_hash}" != "${remote_hash}" ]]; then
    echo "SHA-256 mismatch for ${rel}" >&2
    echo "  local : ${local_hash}" >&2
    echo "  remote: ${remote_hash}" >&2
    exit 1
  fi
  echo "  [OK] SHA-256 ${remote_hash}"
}

prune_local_backups() {
  local root="${ROOT_DIR}/${LOCAL_BACKUP_ROOT}"
  [[ -d "${root}" ]] || return 0
  local keep="${KEEP_LOCAL_BACKUPS}"
  [[ "${keep}" =~ ^[0-9]+$ ]] || keep=10
  find "${root}" -mindepth 1 -maxdepth 1 -type d | sort -r | tail -n "+$((keep + 1))" | while read -r old_dir; do
    rm -rf "${old_dir}"
  done
}

deploy_one() {
  local rel="${1#/}"
  local local_file="${ROOT_DIR}/${rel}"
  local backup_file="${ROOT_DIR}/${LOCAL_BACKUP_ROOT}/${TIMESTAMP}/${rel}"
  local remote_backup_rel="${rel}.bak.${TIMESTAMP}"

  if [[ ! -f "${local_file}" ]]; then
    echo "Local file not found: ${local_file}" >&2
    exit 1
  fi

  echo "Deploy ${rel}"
  echo "  Downloading current remote file..."
  download_remote "${rel}" "${backup_file}"
  echo "  [OK] Local backup: ${backup_file}"

  echo "  Creating remote backup: ${remote_backup_rel}"
  upload_file "${backup_file}" "${remote_backup_rel}"
  verify_upload "${backup_file}" "${remote_backup_rel}"

  echo "  Uploading new file..."
  upload_file "${local_file}" "${rel}"
  verify_upload "${local_file}" "${rel}"
}

restore_one() {
  local rel="${1#/}"
  local suffix="${BACKUP_SUFFIX}"
  local backup_rel
  if [[ -n "${suffix}" ]]; then
    backup_rel="${rel}${suffix}"
  else
    echo "Restore requires --backup-suffix .bak.YYYYMMDD_HHMMSS to avoid choosing the wrong backup." >&2
    exit 2
  fi

  local restore_tmp="${VERIFY_TMP}/restore-$(basename "${rel}")"
  echo "Restore ${rel} from ${backup_rel}"
  download_remote "${backup_rel}" "${restore_tmp}"
  upload_file "${restore_tmp}" "${rel}"
  verify_upload "${restore_tmp}" "${rel}"
}

if [[ "${MODE}" == "restore" ]]; then
  [[ -n "${RESTORE_PATH}" ]] || { echo "--restore path is required" >&2; exit 2; }
  restore_one "${RESTORE_PATH}"
else
  for rel in "${FILES[@]}"; do
    deploy_one "${rel}"
  done
  prune_local_backups
fi

echo "Done."
