#!/usr/bin/env sh
# Run chair-selection tests against local Docker stack.
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"

echo "== PHPUnit (unit, SQLite) =="
docker exec shared_php sh -c "cd /var/www/html/ResearchRecord && vendor/bin/phpunit tests/unit/ChairSelectionServiceTest.php tests/unit/ChairAdmissionScopeTest.php --no-coverage"

echo ""
echo "== PHPUnit (integration, MySQL via INTEGRATION_DB=1) =="
docker exec -e INTEGRATION_DB=1 shared_php sh -c "cd /var/www/html/ResearchRecord && vendor/bin/phpunit tests/unit/ChairSelectionServiceTest.php tests/unit/ChairAdmissionScopeTest.php --no-coverage"

echo ""
echo "== Playwright E2E =="
cd "$ROOT" && npx playwright test e2e/chair-selection.spec.ts e2e/manage-user-curriculum.spec.ts --project=chromium
