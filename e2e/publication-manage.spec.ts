import { expect, test } from '@playwright/test';
import { appRoute } from './helpers';

test.describe('Admin manage publications', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(appRoute('dev/login?god=1'));
    await expect(page).toHaveURL(/admin\/dashboard/, { timeout: 20_000 });
  });

  test('manage page loads publications list via API', async ({ page }) => {
    const apiPromise = page.waitForResponse(
      (res) => res.url().includes('api/dashboard/publications') && res.status() === 200,
      { timeout: 20_000 }
    );

    await page.goto(appRoute('admin/publications/manage'));

    const apiRes = await apiPromise;
    const json = await apiRes.json();
    expect(json.success).toBe(true);
    expect(Array.isArray(json.data)).toBe(true);

    expect(json.data.length).toBeGreaterThan(0);

    await expect(page.locator('#publications-table tr').first()).toBeVisible({ timeout: 15_000 });
    await expect(page.locator('body')).not.toContainText('Unexpected token');
  });
});
