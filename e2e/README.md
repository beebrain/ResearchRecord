# E2E tests (Playwright)

Requires local app on Docker/nginx and `CI_ENVIRONMENT=development`.

## Setup

```bash
npm install
npx playwright install chromium
```

## Run

```bash
npm run test:e2e
# UI mode
npm run test:e2e:ui
```

Optional env:

- `PLAYWRIGHT_BASE_URL` — default `http://127.0.0.1/ResearchRecord/public/index.php`
- `PLAYWRIGHT_TEST_EMAIL` — user for dev login test (default `acakpong@live.uru.ac.th`)

## What is covered

- Dev login shortcuts on `/auth/login`
- `/dev/login` → user dashboard
- `/dev/login?god=1` → admin dashboard
- Backdoor portal quick admin
- Admin dashboard API smoke (statistics)
- **`e2e/all-menus.spec.ts`** — คลิกทุกเมนู admin sidebar + user dashboard

```bash
npm run test:e2e -- e2e/all-menus.spec.ts
```
