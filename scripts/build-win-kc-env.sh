#!/usr/bin/env bash
# สร้าง .env สำหรับ win-kc (sci.uru.ac.th/Research) — ไม่ commit
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="${ROOT}/.env.win-kc"
KEY="$(openssl rand -hex 32)"

cat >"$OUT" <<EOF
CI_ENVIRONMENT = production

app.baseURL = 'https://sci.uru.ac.th/recordresearch/'
app.indexPage = 'index.php'
app.appTimezone = 'Asia/Bangkok'
app.defaultLocale = 'th'

database.default.hostname = localhost
database.default.database = rac
database.default.username = rac
database.default.password = rac@URU@2026
database.default.DBDriver = MySQLi
database.default.port = 3306
database.default.DBDebug = false

encryption.key = hex:${KEY}

newscience.baseUrl = "https://sci.uru.ac.th"
newscience_sso.enabled = true
newscience_sso.sharedSecret = "pisit_secret"
uruoauth.enabled = false

RESEARCH_API_KEY = URU_RESEARCH
RESEARCH_SYNC_HMAC_SECRET = "hello URU"
CURRICULUM_API_TOKEN = $(openssl rand -hex 24)
EOF

echo "Wrote ${OUT}"
