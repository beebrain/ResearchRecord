#!/usr/bin/env bash
# Pull full `rac` database from win-kc MySQL (localhost) into local Docker.
#
# Usage:
#   ./scripts/pull-db-from-win-kc.sh
#   WIN_KC_HOST=100.74.66.65 LOCAL_DB=rac_winkc ./scripts/pull-db-from-win-kc.sh
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKUP_DIR="${ROOT}/deploy-backups/db"
TIMESTAMP="${TIMESTAMP:-$(date +%Y%m%d_%H%M%S)}"
ENV_FILE="${ROOT}/scripts/ftp_rr.env"
EXAMPLE_ENV_FILE="${ROOT}/scripts/ftp_rr.example.env"

[[ -f "${EXAMPLE_ENV_FILE}" ]] && source "${EXAMPLE_ENV_FILE}"
[[ -f "${ENV_FILE}" ]] && source "${ENV_FILE}"

WIN_KC_HOST="${WIN_KC_HOST:-100.74.66.65}"
WIN_KC_USER="${WIN_KC_USER:-Administrator}"
WIN_KC_DB_USER="${WIN_KC_DB_USER:-rac}"
WIN_KC_DB_PASS="${WIN_KC_DB_PASS:-${DB_PROD_PASS:-}}"
WIN_KC_DB_NAME="${WIN_KC_DB_NAME:-rac}"

if [[ -z "${WIN_KC_DB_PASS}" ]]; then
  echo "Set WIN_KC_DB_PASS or DB_PROD_PASS in scripts/ftp_rr.env (never commit secrets)." >&2
  exit 2
fi

DOCKER_MYSQL="${DOCKER_MYSQL_CONTAINER:-shared_mysql}"
LOCAL_ROOT_USER="${LOCAL_MYSQL_USER:-root}"
LOCAL_ROOT_PASS="${LOCAL_MYSQL_PASS:-}"
LOCAL_DB="${LOCAL_MYSQL_DATABASE:-rac_winkc}"

if [[ -z "${LOCAL_ROOT_PASS}" ]]; then
  echo "Set LOCAL_MYSQL_PASS in scripts/ftp_rr.env (never commit secrets)." >&2
  exit 2
fi

MYSQLDUMP='C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe'

SSH_OPTS=(
  -F /dev/null
  -o StrictHostKeyChecking=accept-new
  -o UserKnownHostsFile="${HOME}/.ssh/known_hosts"
  -o ProxyCommand="tailscale nc %h 22"
  -o ConnectTimeout=60
)

command -v tailscale >/dev/null || { echo "tailscale required" >&2; exit 1; }

if ! docker ps --format '{{.Names}}' | grep -qx "${DOCKER_MYSQL}"; then
  echo "Docker container not running: ${DOCKER_MYSQL}" >&2
  exit 2
fi

mkdir -p "${BACKUP_DIR}"
DUMP="${BACKUP_DIR}/rac_winkc_${TIMESTAMP}.sql"

echo "==> Dump win-kc ${WIN_KC_DB_NAME} via SSH (${WIN_KC_HOST})"
ssh "${SSH_OPTS[@]}" "${WIN_KC_USER}@${WIN_KC_HOST}" \
  "powershell -NoProfile -Command \"& '${MYSQLDUMP}' -u ${WIN_KC_DB_USER} -p${WIN_KC_DB_PASS} --single-transaction --routines --triggers --events --set-gtid-purged=OFF --no-tablespaces ${WIN_KC_DB_NAME}\"" \
  >"${DUMP}"

BYTES=$(wc -c <"${DUMP}" | tr -d ' ')
if [[ "${BYTES}" -lt 1000 ]]; then
  echo "Dump too small (${BYTES} bytes) — check SSH / MySQL credentials." >&2
  head -20 "${DUMP}" >&2 || true
  exit 1
fi

echo "==> Dump saved: ${DUMP} (${BYTES} bytes)"

if docker exec "${DOCKER_MYSQL}" mysql -N \
  -u"${LOCAL_ROOT_USER}" -p"${LOCAL_ROOT_PASS}" \
  -e "SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name='${LOCAL_DB}';" 2>/dev/null | grep -qx '1'; then
  BEFORE="${BACKUP_DIR}/${LOCAL_DB}_before_${TIMESTAMP}.sql"
  echo "==> Backup existing local ${LOCAL_DB} -> ${BEFORE}"
  docker exec "${DOCKER_MYSQL}" mysqldump \
    -u"${LOCAL_ROOT_USER}" -p"${LOCAL_ROOT_PASS}" \
    --single-transaction --routines --triggers --events \
    --set-gtid-purged=OFF --no-tablespaces \
    "${LOCAL_DB}" >"${BEFORE}"
fi

echo "==> Import into local Docker database: ${LOCAL_DB}"
docker exec "${DOCKER_MYSQL}" mysql \
  -u"${LOCAL_ROOT_USER}" -p"${LOCAL_ROOT_PASS}" \
  -e "DROP DATABASE IF EXISTS \`${LOCAL_DB}\`; CREATE DATABASE \`${LOCAL_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

docker exec -i "${DOCKER_MYSQL}" mysql \
  -u"${LOCAL_ROOT_USER}" -p"${LOCAL_ROOT_PASS}" \
  "${LOCAL_DB}" <"${DUMP}"

TABLES=$(docker exec "${DOCKER_MYSQL}" mysql -N \
  -u"${LOCAL_ROOT_USER}" -p"${LOCAL_ROOT_PASS}" \
  -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${LOCAL_DB}';" 2>/dev/null)

echo "Done. Local ${LOCAL_DB}: ${TABLES} tables."
echo "  win-kc dump: ${DUMP}"
