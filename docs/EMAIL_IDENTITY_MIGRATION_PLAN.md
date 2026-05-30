# RR: Migrate Identity to Email — Plan, Test, Deploy

**Goal:** Use **normalized email** as the canonical identity for matching users, publications, CV sync with newScience — while keeping `user.uid` as the internal surrogate PK.

**Status:** Plan only (not deployed). Local clone DB: `rac` on `shared_mysql`.

---

## Current state (verified on production clone `rac`)

| Item | Finding |
|------|---------|
| `user.email` | NOT NULL, **UNIQUE**, 427 users, 0 duplicates |
| `cv_sections.user_uid` | 73 rows, all numeric `uid`, 0 orphans |
| `publication_authors` | 980 rows: **556 email-only**, 151 email+uid, **273 name-only** (no email/uid) |
| Publications query | Code already treats **`author_email` as PRIMARY** in `getPublicationsByAuthor()` |
| NS sync API | Already **email at boundary** (`*-by-email` + HMAC) |
| Gap | `cv_sections`, session, `DashboardController`, `CvSyncApiController` CV bundle still key off **`user_uid`** |

**Conclusion:** Migrate **identity resolution** to email; do **not** remove `user.uid` from schema.

---

## Architecture target

```
                    ┌─────────────────────────────────────┐
  API / SSO / NS    │  canonical: LOWER(TRIM(email))      │
  ───────────────►  │  UserIdentity::normalizeEmail()     │
                    └─────────────────┬───────────────────┘
                                      │ resolve once
                                      ▼
                    ┌─────────────────────────────────────┐
  Internal RR DB    │  user.uid (PK, session, created_by) │
                    │  cv_sections.owner_email_norm (new) │
                    │  publication_authors.author_email   │
                    └─────────────────────────────────────┘
```

---

## Phased rollout (safe, reversible)

### Phase 0 — Prep (no code)

| Step | Action | Verify |
|------|--------|--------|
| 0.1 | Branch `feature/rr-email-identity` | — |
| 0.2 | Full backup production `rac` | `mysqldump` saved off-server |
| 0.3 | Local DB = production clone (`rac` on Docker) | Row counts match server |
| 0.4 | Baseline metrics | Run `scripts/verify-email-identity-migration.sql` (section **BASELINE**) |

### Phase 1 — Code only (no schema change)

**Deliverables**

1. `app/Libraries/UserIdentity.php`
   - `normalizeEmail(string): string` → `strtolower(trim())`
   - `resolveUserByEmail(string): ?array` → row from `user` or null
   - `resolveUidFromEmail(string): ?int` → for legacy call sites

2. `PublicationModel`
   - Add `getPublicationsByCanonicalEmail(string $email, int $limit = 1000)` — move current multi-email logic here (from `getPublicationsByAuthor`).
   - Change `getPublicationsByAuthor($userId)` to: resolve uid → delegate to canonical email path (no behavior change).
   - Extend `getPublicationsByEmail()` to match feature parity (include `created_by`, `authors` alias emails) **or** deprecate in favor of canonical method.

3. Call sites (swap to email where caller already has email):
   - `CvSyncApiController::getPublicationsSyncBundleByEmail` → `getPublicationsByCanonicalEmail($v['email'])` directly (skip uid hop).

**Files touched (estimate):** `UserIdentity.php` (new), `PublicationModel.php`, `CvSyncApiController.php`, `ApiController.php` (optional).

| Verify | Command / check |
|--------|-----------------|
| Parity | SQL script section **PUBLICATION_PARITY** — same publication IDs for sample users via uid path vs email path |
| Unit | `vendor/bin/phpunit` if tests added for `UserIdentity` |
| Local manual | Login → My Publications count unchanged |

**Deploy Phase 1 alone:** Low risk (additive). Deploy PHP only, no migration.

---

### Phase 2 — Schema: `cv_sections.owner_email_norm`

**Migration:** `app/Database/Migrations/20260524180000_AddOwnerEmailToCvSections.php`

```sql
-- conceptual
ALTER TABLE cv_sections
  ADD COLUMN owner_email_norm VARCHAR(255) NULL AFTER user_uid,
  ADD INDEX idx_cv_sections_owner_email (owner_email_norm);

UPDATE cv_sections cs
JOIN user u ON u.uid = cs.user_uid
SET cs.owner_email_norm = LOWER(TRIM(u.email));

-- optional: enforce after backfill
-- ALTER TABLE cv_sections MODIFY owner_email_norm VARCHAR(255) NOT NULL;
```

| Verify | Expected |
|--------|----------|
| Backfill | `owner_email_norm` NULL count = 0 where `user_uid` joins `user` |
| Orphans | Script section **CV_ORPHANS** = 0 |

**Deploy:** `php spark migrate` on server **or** run SQL via phpMyAdmin with backup first.

---

### Phase 3 — Dual-write CV (read email, write both)

**`CvSectionModel`:** allow `owner_email_norm` in `$allowedFields`.

**`DashboardController`** (cv, cvManage, save/delete section & entry):

- On read: `where('owner_email_norm', $normEmail)` **or** legacy `where('user_uid', $uid)` until cutover.
- On write: set **both** `user_uid` and `owner_email_norm` from session user.

**`CvSyncApiController`:**

- `buildCvBundleForUser` → rename to `buildCvBundleForEmail($canonicalEmail)`; load sections by `owner_email_norm`.
- `replaceCvFromBundle` → same; dual-write `user_uid` + `owner_email_norm`.

| Verify | Check |
|--------|-------|
| Dashboard | Existing user sees same CV sections/entries |
| NS pull | `GET cv-bundle-by-email` returns same `content_hash` as before (or document acceptable diff) |
| NS push | `POST cv-bundle-by-email` idempotent round 2 |

---

### Phase 4 — Admin / education joins (lower priority)

- `EducationController`, `AdminController` joins on `cs.user_uid` → add `owner_email_norm` path or keep uid join (internal only).
- `UserProfileModel::getByUserUid` — add `getByEmail()`; sync code uses email first.

Can ship after Phase 3 stabilizes.

---

### Phase 5 — Deprecation (future, optional)

- Stop writing `cv_sections.user_uid` (read-only legacy).
- Document: changing `user.email` requires admin process + update `owner_email_norm` + `publication_authors.author_email`.

**Not in first deploy.**

---

## Test matrix

### Tier 1 — Local (`rac` clone)

```bash
# From host, after Phase 1 code + composer in container
docker exec -w /var/www/html/ResearchRecord shared_php php spark migrate  # Phase 2+

docker exec shared_mysql mysql -uroot -prootpass rac < scripts/verify-email-identity-migration.sql
```

| ID | Test | Pass criteria |
|----|------|----------------|
| T1 | Publication parity | Same `COUNT(*)` and same set of `publication_id` for 3 sample emails (see SQL) |
| T2 | CV backfill | 0 `cv_sections` with NULL `owner_email_norm` (after Phase 2) |
| T3 | Dashboard CV page | Loads, section count unchanged for test user |
| T4 | CV manage CRUD | Add/edit/delete entry → persists under correct email |
| T5 | API publications bundle | `GET publications-sync-bundle-by-email` → 200, `contributors` present |
| T6 | API CV bundle | `GET cv-bundle-by-email` → 200, `sections` match pre-migration hash or documented delta |
| T7 | Co-author | User B sees publication where only `author_email` = B (no `pa.uid`) |

### Tier 2 — Staging / pre-prod (if available)

Repeat T5–T6 with production HMAC secret against RR staging URL.

### Tier 3 — Production smoke (after deploy)

| Step | Action |
|------|--------|
| 1 | Deploy with backup script (see below) |
| 2 | `php spark migrate` on server |
| 3 | Run verification SQL on server (read-only) |
| 4 | One faculty test account: open `dashboard/cv` |
| 5 | newScience: `publications:sync-rr --email=<test>` twice → second run `skipped_unchanged` |
| 6 | Monitor `writable/logs` for 15 min |

---

## Deploy to RR production

### Order of operations

1. **Backup DB** (full `rac` dump).
2. **Deploy PHP** (Phase 1 → then Phase 3 files after migration ready).
3. **Run migration** Phase 2 (`spark migrate` one batch).
4. **Deploy remaining PHP** (Phase 3).
5. **Smoke tests** Tier 3.

### Files per phase (minimum)

**Phase 1 deploy**

```bash
./scripts/deploy-rr-with-backup.sh --file app/Libraries/UserIdentity.php
./scripts/deploy-rr-with-backup.sh --file app/Models/PublicationModel.php
./scripts/deploy-rr-with-backup.sh --file app/Controllers/CvSyncApiController.php
```

**Phase 2 deploy**

```bash
./scripts/deploy-rr-with-backup.sh --file app/Database/Migrations/20260524180000_AddOwnerEmailToCvSections.php
# SSH/server: php spark migrate
```

**Phase 3 deploy**

```bash
./scripts/deploy-rr-with-backup.sh --file app/Models/CvSectionModel.php
./scripts/deploy-rr-with-backup.sh --file app/Controllers/DashboardController.php
./scripts/deploy-rr-with-backup.sh --file app/Controllers/CvSyncApiController.php
```

See `scripts/README_RR_DEPLOY.md` for FTP env and rollback (`.bak.<timestamp>`).

### Rollback

| Failure | Action |
|---------|--------|
| PHP regression | `./scripts/deploy-rr-with-backup.sh --restore <file> --backup-suffix .bak.TIMESTAMP` |
| Migration issue | Restore DB from dump; drop column `owner_email_norm` if partial |
| NS sync broken | Revert `CvSyncApiController` + `PublicationModel` first (highest coupling) |

---

## Risk register

| Risk | Mitigation |
|------|------------|
| Email change on `user` row | UNIQUE constraint; migration script to cascade update `owner_email_norm` + `publication_authors` |
| 273 authors with name only | Unchanged; still need manual ORCID/import |
| Session still stores `uid` | OK — resolve email at controller entry |
| `user_profile` keyed by uid | Phase 4; keep uid FK |
| NS expects email | Already aligned — reduces risk |

---

## Success criteria

- [ ] All Tier 1 tests pass on local `rac`.
- [ ] Production smoke: CV page + sync API 200 for pilot email.
- [ ] newScience reconcile idempotent (2nd run unchanged).
- [ ] No increase in duplicate `cv_sections` per user.
- [ ] Documentation: RR identity policy = email canonical, uid internal.

---

## Suggested implementation order (sprints)

| Sprint | Content | Deployable |
|--------|---------|------------|
| S1 | Phase 0 + Phase 1 + T1,T5 | Yes (PHP only) |
| S2 | Phase 2 migration + T2 | Yes (SQL + migrate) |
| S3 | Phase 3 + T3,T4,T6,T7 | Yes |
| S4 | Phase 4 admin/education | Optional |

---

## References

- Local analysis DB: `rac` (clone from `202.29.52.124`)
- NS identity rule: `newScience/.cursor/skills/login-email-primary-key/SKILL.md`
- Deploy: `scripts/README_RR_DEPLOY.md`
- Verification SQL: `scripts/verify-email-identity-migration.sql`
