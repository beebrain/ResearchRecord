#!/usr/bin/env bash
# Deploy Curriculum Detail API + Swagger docs (/docs)
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEPLOY="${ROOT}/scripts/deploy-rr-with-backup.sh"

FILES=(
  app/Config/Filters.php
  app/Config/Routes.php
  app/Controllers/ApiController.php
  app/Controllers/ApiDocsController.php
  app/Filters/CurriculumApiTokenFilter.php
  app/Libraries/RrOpenApiSpec.php
  app/Libraries/UserIdentity.php
  app/Models/CurriculumModel.php
  app/Models/PublicationModel.php
  app/Models/UserModel.php
  app/Views/api_docs/swagger.php
)

args=()
for f in "${FILES[@]}"; do
  args+=(--file "$f")
done

exec "${DEPLOY}" "${args[@]}"
