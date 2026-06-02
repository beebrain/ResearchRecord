import { expect, test, type Page, type Response } from '@playwright/test';
import { appRoute } from './helpers';

type ApiIssue = { url: string; status: number; detail: string };

const adminPages: { label: string; route: string }[] = [
  { label: '📊 แดชบอร์ด', route: 'admin/dashboard' },
  { label: '📚 จัดการผลงานวิจัย', route: 'admin/publications/manage' },
  { label: '📊 สรุปผลงานวิจัย', route: 'admin/publications/summary' },
  { label: '👥 ผู้แต่ง', route: 'admin/manageEmails' },
  { label: '🎓 หลักสูตรของผู้ใช้', route: 'admin/manage-user-curriculum' },
  { label: '🏛️ คณะและหลักสูตร', route: 'admin/faculty-curriculum' },
  { label: '📋 แบบฟอร์มเปิดรับ นศ.', route: 'admin/admission' },
  { label: '🎓 ประวัติการศึกษา', route: 'admin/education' },
  { label: '🔐 บทบาทผู้ใช้', route: 'admin/user-roles' },
];

const userPages: { label: string; route: string }[] = [
  { label: 'User dashboard', route: 'dashboard' },
  { label: 'เพิ่มผลงาน', route: 'publications/create' },
  { label: 'ดู CV', route: 'dashboard/cv' },
  { label: 'จัดการ CV', route: 'dashboard/cv-manage' },
  { label: 'ORCID', route: 'dashboard/orcid' },
  { label: 'Settings', route: 'dashboard/settings' },
  { label: 'Publications list', route: 'publications' },
];

function isAppApi(url: string): boolean {
  return url.includes('index.php') && !url.includes('debugbar') && !url.includes('cdn.');
}

function isWrongRoute(url: string): boolean {
  // nginx 404 pattern: index.php/admin/... without ?/
  return /index\.php\/[a-z]/i.test(url) && !/index\.php\?\//.test(url);
}

async function collectApiIssues(page: Page, waitMs = 4000): Promise<ApiIssue[]> {
  const issues: ApiIssue[] = [];

  const onResponse = async (res: Response) => {
    const url = res.url();
    if (!isAppApi(url)) return;

    const status = res.status();
    const ct = (res.headers()['content-type'] ?? '').toLowerCase();

    if (isWrongRoute(url)) {
      issues.push({ url, status, detail: 'wrong URL format (index.php/ without ?/)' });
      return;
    }

    if (status === 404) {
      issues.push({ url, status, detail: '404 Not Found' });
      return;
    }

    if (status >= 500) {
      issues.push({ url, status, detail: 'server error' });
      return;
    }

    if (ct.includes('json') && status === 200) {
      try {
        const json = await res.json();
        if (json && json.success === false && json.message) {
          issues.push({ url, status, detail: `API success=false: ${String(json.message).slice(0, 120)}` });
        }
      } catch {
        /* ignore parse errors */
      }
    } else if (status === 200 && ct.includes('html') && /\/index\.php\?\//.test(url)) {
      issues.push({ url, status, detail: 'expected JSON, got HTML' });
    }
  };

  page.on('response', onResponse);
  await page.waitForTimeout(waitMs);
  page.off('response', onResponse);

  return issues;
}

test.describe.configure({ mode: 'serial' });

test.describe('Data loading audit — admin pages (god mode)', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(appRoute('dev/login?god=1'), { waitUntil: 'domcontentloaded' });
    await expect(page).toHaveURL(/admin\/dashboard/, { timeout: 20_000 });
  });

  for (const { label, route } of adminPages) {
    test(`API audit: ${label}`, async ({ page }) => {
      await page.goto(appRoute(route), { waitUntil: 'domcontentloaded' });
      await page.waitForLoadState('networkidle', { timeout: 15_000 }).catch(() => {});

      const body = await page.locator('body').innerText();
      expect(body, `${label}: page 404`).not.toMatch(/^404 Not Found$/m);
      expect(body, `${label}: CI error`).not.toContain('Whoops!');

      const issues = await collectApiIssues(page, 2000);

      if (issues.length > 0) {
        console.log(`\n--- ${label} (${route}) ---`);
        for (const i of issues) {
          console.log(`  [${i.status}] ${i.detail}\n      ${i.url}`);
        }
      }

      expect(issues, `${label} API issues:\n${issues.map((i) => `- ${i.detail}: ${i.url}`).join('\n')}`).toEqual([]);
    });
  }
});

test.describe('Data loading audit — user pages', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(appRoute('dev/login'), { waitUntil: 'domcontentloaded' });
    await expect(page).toHaveURL(/dashboard/, { timeout: 20_000 });
  });

  for (const { label, route } of userPages) {
    test(`API audit: ${label}`, async ({ page }) => {
      await page.goto(appRoute(route), { waitUntil: 'domcontentloaded' });
      await page.waitForLoadState('networkidle', { timeout: 15_000 }).catch(() => {});

      const body = await page.locator('body').innerText();
      expect(body, `${label}: page 404`).not.toMatch(/^404 Not Found$/m);

      const issues = await collectApiIssues(page, 2000);

      if (issues.length > 0) {
        console.log(`\n--- ${label} (${route}) ---`);
        for (const i of issues) {
          console.log(`  [${i.status}] ${i.detail}\n      ${i.url}`);
        }
      }

      expect(issues, `${label} API issues:\n${issues.map((i) => `- ${i.detail}: ${i.url}`).join('\n')}`).toEqual([]);
    });
  }
});
