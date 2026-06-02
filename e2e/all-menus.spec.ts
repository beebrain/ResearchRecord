import { expect, test, type Page } from '@playwright/test';
import { appRoute } from './helpers';

/** Super-admin sidebar entries (god mode / dev login?god=1) */
const adminMenus = [
  { linkName: '📊 แดชบอร์ด', urlPattern: /admin\/dashboard/, bodyHint: /ภาพรวมระบบ|Admin Dashboard/i },
  { linkName: '📚 จัดการผลงานวิจัย', urlPattern: /publications\/manage/, bodyHint: /ผลงาน|publication|จัดการ/i },
  { linkName: '📊 สรุปผลงานวิจัย', urlPattern: /publications\/summary/, bodyHint: /สรุป|summary/i },
  { linkName: '👥 ผู้แต่ง', urlPattern: /manageEmails/, bodyHint: /อีเมล|email|ผู้แต่ง/i },
  { linkName: '🎓 หลักสูตรของผู้ใช้', urlPattern: /manage-user-curriculum|curiculum/, bodyHint: /หลักสูตร|curriculum/i },
  { linkName: '🏛️ คณะและหลักสูตร', urlPattern: /faculty-curriculum/, bodyHint: /คณะ|หลักสูตร|faculty/i },
  { linkName: '📋 แบบฟอร์มเปิดรับ นศ.', urlPattern: /admin\/admission/, bodyHint: /รับนักศึกษา|admission|แบบฟอร์ม/i },
  { linkName: '🎓 ประวัติการศึกษา', urlPattern: /admin\/education/, bodyHint: /การศึกษา|education/i },
  { linkName: '🔐 บทบาทผู้ใช้', urlPattern: /user-roles/, bodyHint: /บทบาท|role/i },
];

/** User dashboard top bar links */
const userMenus = [
  { name: 'เพิ่มผลงาน', route: 'publications/create', urlPattern: /publications\/create/, bodyHint: /ผลงาน|publication|เพิ่ม/i },
  { name: 'ดู CV', route: 'dashboard/cv', urlPattern: /dashboard\/cv/, bodyHint: /CV|curriculum vitae/i },
  { name: 'จัดการ CV', route: 'dashboard/cv-manage', urlPattern: /cv-manage/, bodyHint: /CV|จัดการ/i },
  { name: 'ORCID', route: 'dashboard/orcid', urlPattern: /orcid/, bodyHint: /ORCID|orcid/i },
];

async function assertPageHealthy(page: Page, label: string): Promise<void> {
  const body = await page.locator('body').innerText();
  expect(body, `${label}: nginx 404`).not.toMatch(/^404 Not Found$/m);
  expect(body, `${label}: CI error page`).not.toContain('Whoops!');
  expect(body, `${label}: PHP fatal`).not.toMatch(/Fatal error|Uncaught Error/i);
  expect(body, `${label}: PHP exception`).not.toMatch(/ErrorException|Undefined array key/i);

  const tailwindHref = await page.locator('link[rel="stylesheet"][href*="tailwind"]').first().getAttribute('href');
  if (tailwindHref) {
    const res = await page.request.get(tailwindHref);
    expect(res.status(), `${label}: tailwind.css`).toBe(200);
  }
}

test.describe.configure({ mode: 'serial' });

test.describe('Admin sidebar — every menu (god mode)', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(appRoute('dev/login?god=1'), { waitUntil: 'domcontentloaded' });
    await expect(page).toHaveURL(/admin\/dashboard/, { timeout: 20_000 });
    await expect(page.locator('aside.w-64')).toBeVisible();
  });

  for (const menu of adminMenus) {
    test(`menu: ${menu.linkName}`, async ({ page }) => {
      const link = page.locator('aside nav').getByRole('link', { name: menu.linkName, exact: true });
      await expect(link).toBeVisible({ timeout: 10_000 });
      await link.click();
      await page.waitForLoadState('domcontentloaded');
      await expect(page).toHaveURL(menu.urlPattern, { timeout: 20_000 });

      await assertPageHealthy(page, menu.linkName);
      await expect(page.locator('body')).toContainText(menu.bodyHint, { timeout: 15_000 });
    });
  }

  test('menu: กลับไปยังแดชบอร์ดผู้ใช้', async ({ page }) => {
    await page.locator('aside').getByRole('link', { name: /กลับไปยังแดชบอร์ดผู้ใช้/ }).click();
    await expect(page).toHaveURL(/dashboard/, { timeout: 15_000 });
    await expect(page).not.toHaveURL(/admin\/dashboard/);
    await expect(page.getByText('Research Portal')).toBeVisible({ timeout: 15_000 });
    await assertPageHealthy(page, 'user dashboard');
  });
});

test.describe('User dashboard — top menus', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(appRoute('dev/login'), { waitUntil: 'domcontentloaded' });
    await expect(page).toHaveURL(/dashboard/, { timeout: 20_000 });
    await expect(page.getByText('Research Portal')).toBeVisible();
  });

  for (const menu of userMenus) {
    test(`link: ${menu.name}`, async ({ page }) => {
      await page.getByRole('link', { name: menu.name }).click();
      await page.waitForLoadState('domcontentloaded');
      await expect(page).toHaveURL(menu.urlPattern, { timeout: 20_000 });
      await assertPageHealthy(page, menu.name);
      await expect(page.locator('body')).toContainText(menu.bodyHint, { timeout: 15_000 });
    });
  }

  test('link: settings (⚙️)', async ({ page }) => {
    await page.goto(appRoute('dashboard/settings'), { waitUntil: 'domcontentloaded' });
    await expect(page).toHaveURL(/dashboard\/settings/, { timeout: 15_000 });
    await assertPageHealthy(page, 'settings');
  });

  test('link: publications list', async ({ page }) => {
    await page.goto(appRoute('publications'), { waitUntil: 'domcontentloaded' });
    await expect(page).toHaveURL(/publications/, { timeout: 15_000 });
    await assertPageHealthy(page, 'publications');
  });
});
