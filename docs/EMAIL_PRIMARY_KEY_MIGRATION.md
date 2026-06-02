# Email as primary key — migration status

## Done (local `rac`)

- `user.email` is **PRIMARY KEY**; column `user.uid` **removed**
- Child tables use `*_email` columns (e.g. `teacher_email`, `created_by_email`, `chair_email`)
- Views recreated: `teacher_curriculum_view`, `publication_view` (uses `author_emails`, not `author_uids`)
- Session: `user_email` + `user_id` (same normalized email string)
- Helpers: `UserIdentity`, `identity_helper` (`current_user_email()`)

## Run on server (after full DB backup)

```bash
./scripts/pull-db-from-server.sh   # optional: refresh local first
php scripts/run-email-pk-migration.php
# if interrupted:
#   DELETE orphan teacher_curriculum / user_profile rows per scripts in chat
php scripts/continue-email-pk-migration.php
```

## Code still using `uid` (must fix before production)

Many controllers/models/views still reference `uid`, `user_id` as integer, or `teacher_uid`. Search:

```bash
rg '\buid\b|teacher_uid|user_uid|created_by[^_]' app --glob '*.php'
```

Priority files: `DashboardController`, `PublicationController`, `AdminController`, `RoleHelper`, `AuthorModel::getAuthorlinkUser`.

## API breaking change

Curriculum detail API **no longer returns `uid`** on teachers/chair — **email only**.

## Rollback

Restore MySQL dump from before migration. Code revert to commit before email-PK branch.
