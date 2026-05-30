#!/usr/bin/env bash
# Pull full `rac` database from production MySQL into local Docker (shared_mysql).
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKUP_DIR="${ROOT}/deploy-backups/db"
TIMESTAMP="${TIMESTAMP:-$(date +%Y%m%d_%H%M%S)}"

# Production (override via env)
PROD_HOST="${DB_PROD_HOST:-202.29.52.124}"
PROD_PORT="${DB_PROD_PORT:-3306}"
PROD_USER="${DB_PROD_USER:-rac}"
PROD_PASS="${DB_PROD_PASS:-}"
PROD_DB="${DB_PROD_NAME:-rac}"

# Local Docker MySQL
DOCKER_MYSQL="${DOCKER_MYSQL_CONTAINER:-shared_mysql}"
LOCAL_ROOT_USER="${LOCAL_MYSQL_USER:-root}"
LOCAL_ROOT_PASS="${LOCAL_MYSQL_PASS:-rootpass}"
LOCAL_DB="${LOCAL_MYSQL_DATABASE:-rac}"

if [[ -z "${PROD_PASS}" ]] && [[ -f "${ROOT}/scripts/ftp_rr.env" ]]; then
  # shellcheck disable=SC1090
  source "${ROOT}/scripts/ftp_rr.env" 2>/dev/null || true
fi
PROD_PASS="${DB_PROD_PASS:-${PROD_PASS:-}}"

if [[ -z "${PROD_PASS}" ]]; then
  read -r -s -p "Production MySQL password (${PROD_USER}@${PROD_HOST}): " PROD_PASS
  echo
fi

if [[ -z "${PROD_PASS}" ]]; then
  echo "Set DB_PROD_PASS or scripts/ftp_rr.env" >&2
  exit 2
fi

if ! docker ps --format '{{.Names}}' | grep -qx "${DOCKER_MYSQL}"; then
  echo "Docker container not running: ${DOCKER_MYSQL}" >&2
  exit 2
fi

mkdir -p "${BACKUP_DIR}"
PROD_DUMP="${BACKUP_DIR}/rac_prod_${TIMESTAMP}.sql"
LOCAL_BEFORE="${BACKUP_DIR}/rac_local_before_${TIMESTAMP}.sql"

echo "==> Backup local ${LOCAL_DB} -> ${LOCAL_BEFORE}"
docker exec "${DOCKER_MYSQL}" mysqldump \
  -u"${LOCAL_ROOT_USER}" -p"${LOCAL_ROOT_PASS}" \
  --single-transaction --routines --triggers --events \
  --set-gtid-purged=OFF --no-tablespaces \
  "${LOCAL_DB}" >"${LOCAL_BEFORE}"

echo "==> Dump production ${PROD_DB}@${PROD_HOST} -> ${PROD_DUMP}"
docker exec "${DOCKER_MYSQL}" mysqldump \
  -h"${PROD_HOST}" -P"${PROD_PORT}" -u"${PROD_USER}" -p"${PROD_PASS}" \
  --single-transaction --routines --triggers --events \
  --set-gtid-purged=OFF --no-tablespaces \
  "${PROD_DB}" >"${PROD_DUMP}"

BYTES=$(wc -c <"${PROD_DUMP}" | tr -d ' ')
if [[ "${BYTES}" -lt 1000 ]]; then
  echo "Dump too small (${BYTES} bytes) — check connection or credentials." >&2
  exit 1
fi

echo "==> Replace local ${LOCAL_DB} with production data"
docker exec "${DOCKER_MYSQL}" mysql \
  -u"${LOCAL_ROOT_USER}" -p"${LOCAL_ROOT_PASS}" \
  -e "DROP DATABASE IF EXISTS \`${LOCAL_DB}\`; CREATE DATABASE \`${LOCAL_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

docker exec -i "${DOCKER_MYSQL}" mysql \
  -u"${LOCAL_ROOT_USER}" -p"${LOCAL_ROOT_PASS}" \
  "${LOCAL_DB}" <"${PROD_DUMP}"

TABLES=$(docker exec "${DOCKER_MYSQL}" mysql -N \
  -u"${LOCAL_ROOT_USER}" -p"${LOCAL_ROOT_PASS}" \
  -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${LOCAL_DB}';" 2>/dev/null)

echo "Done. Local ${LOCAL_DB}: ${TABLES} tables."
echo "  Production dump: ${PROD_DUMP}"
echo "  Local backup:    ${LOCAL_BEFORE}"
