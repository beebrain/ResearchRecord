import { expect, test } from '@playwright/test';
import { appRoute } from './helpers';

test.describe('Admin dashboard (god mode)', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(appRoute('dev/login?god=1'));
    await expect(page).toHaveURL(/admin\/dashboard/, { timeout: 20_000 });
  });

  test('admin dashboard page loads', async ({ page }) => {
    await expect(page.locator('body')).not.toContainText('404 Not Found');
    const title = await page.title();
    expect(title.length).toBeGreaterThan(0);
  });

  test('dashboard API statistics responds when logged in', async ({ page }) => {
    const res = await page.request.get(appRoute('api/dashboard/stats'));
    expect(res.status()).toBeLessThan(500);
    expect(res.ok()).toBeTruthy();
    const json = await res.json();
    expect(json.success).toBe(true);
    expect(json.data?.totalPublications).toBeGreaterThanOrEqual(0);
  });

  test('dashboard shows loaded statistics (not spinners)', async ({ page }) => {
    await expect(page.locator('#total-publications .loading-spinner')).toHaveCount(0, { timeout: 15_000 });
    const pubText = await page.locator('#total-publications').innerText();
    expect(pubText.trim()).toMatch(/^\d+/);
    await expect(page.locator('#faculty-summary-body tr').first()).not.toContainText('กำลังโหลดข้อมูล', {
      timeout: 15_000,
    });
  });
});
