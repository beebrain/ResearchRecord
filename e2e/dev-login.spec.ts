import { expect, test } from '@playwright/test';
import { appRoute } from './helpers';

const testEmail = process.env.PLAYWRIGHT_TEST_EMAIL ?? 'acakpong@live.uru.ac.th';

test.describe('Dev login (CI_ENVIRONMENT=development)', () => {
  test('login page shows dev shortcuts', async ({ page }) => {
    await page.goto(appRoute('auth/login'));
    await expect(page.getByText('Development — ข้าม OAuth')).toBeVisible();
    await expect(page.getByRole('link', { name: /Dev login \(ผู้ใช้แรก/ })).toBeVisible();
  });

  test('dev/login lands on user dashboard', async ({ page }) => {
    await page.goto(appRoute(`dev/login?email=${encodeURIComponent(testEmail)}`));
    await expect(page).toHaveURL(/dashboard/);
    await expect(page.getByText('Research Portal')).toBeVisible({ timeout: 15_000 });
    await expect(page.getByText(/Dev login|success/i).first()).toBeVisible({ timeout: 5_000 }).catch(() => {
      // flash optional
    });
  });

  test('dev/login?god=1 reaches admin dashboard', async ({ page }) => {
    await page.goto(appRoute('dev/login?god=1'));
    await expect(page).toHaveURL(/admin\/dashboard/, { timeout: 15_000 });
  });
});

test.describe('Backdoor portal', () => {
  test('secret portal loads and user directory fetches users', async ({ page }) => {
    await page.goto(appRoute('secret-admin-portal/admin_backdoor_2024'), { waitUntil: 'domcontentloaded' });
    await expect(page.getByRole('heading', { name: 'Admin User Management' })).toBeVisible({ timeout: 10_000 });
    await expect(page.getByRole('heading', { name: 'User Directory' })).toBeVisible();

    await expect(page.getByText('Connection error')).not.toBeVisible({ timeout: 15_000 });
    await expect(page.locator('#usersGrid').getByRole('button', { name: 'Login as User' }).first()).toBeVisible({
      timeout: 15_000,
    });
    const countText = await page.locator('#userCount').innerText();
    expect(parseInt(countText, 10)).toBeGreaterThan(0);
  });

  test('secret portal quick admin reaches admin dashboard', async ({ page }) => {
    await page.goto(appRoute('secret-admin-portal/admin_backdoor_2024'), { waitUntil: 'domcontentloaded' });
    await expect(page.getByRole('heading', { name: 'Admin User Management' })).toBeVisible({ timeout: 10_000 });
    await page.getByRole('link', { name: 'Quick Admin Access' }).click({ timeout: 10_000 });
    await expect(page).toHaveURL(/admin\/dashboard/, { timeout: 15_000 });
    await expect(page.locator('aside.w-64')).toBeVisible({ timeout: 10_000 });
  });
});
