#!/usr/bin/env bash
# Deploy Thai name sync: newScience PortalPersonNames + RR git pull
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
NS_LIB="C:/inetpub/newscience/app/Libraries/PortalPersonNames.php"
RR_REPO="C:/inetpub/ResearchRecord"

SSH_OPTS=(
  -F /dev/null
  -o StrictHostKeyChecking=accept-new
  -o UserKnownHostsFile="${HOME}/.ssh/known_hosts"
  -o ProxyCommand="tailscale nc %h 22"
  -o ConnectTimeout=30
)

HOST="${WIN_KC_HOST:-100.74.66.65}"
USER="${WIN_KC_USER:-Administrator}"

run_ssh() {
  ssh "${SSH_OPTS[@]}" "${USER}@${HOST}" "$@"
}

run_scp() {
  scp "${SSH_OPTS[@]}" "$@"
}

echo "=== 1. git pull ResearchRecord ==="
run_ssh "cd /d ${RR_REPO//\//\\\\} && git pull origin master && php spark cache:clear"

echo "=== 2. PortalPersonNames.php → newScience ==="
run_scp "${ROOT}/scripts/patches/newscience/PortalPersonNames.php" "${USER}@${HOST}:${NS_LIB}"

echo "=== 3. Patch newScience UserModel ==="
run_ssh "php ${RR_REPO}/scripts/patches/newscience/apply-ns-portal-thai-patch.php"

echo "=== done ==="
