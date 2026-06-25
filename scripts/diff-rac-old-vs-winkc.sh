#!/usr/bin/env bash
# Sync win-kc rac -> local Docker, then diff against OLD server DB.
#
# Usage:
#   ./scripts/diff-rac-old-vs-winkc.sh
#   ./scripts/diff-rac-old-vs-winkc.sh --skip-pull   # diff only (local rac_winkc must exist)
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SKIP_PULL=0
OUT="${ROOT}/deploy-backups/db/diff_old_vs_winkc_$(date +%Y%m%d_%H%M%S).json"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --skip-pull) SKIP_PULL=1; shift ;;
    -h|--help)
      echo "Usage: $0 [--skip-pull]"
      exit 0
      ;;
    *) echo "Unknown: $1" >&2; exit 2 ;;
  esac
done

if [[ "${SKIP_PULL}" -eq 0 ]]; then
  "${ROOT}/scripts/pull-db-from-win-kc.sh"
fi

docker exec shared_php php /var/www/html/ResearchRecord/scripts/diff-rac-old-vs-winkc.php | tee "${OUT}"
echo "Report: ${OUT}"
