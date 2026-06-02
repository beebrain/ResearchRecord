import { expect, test } from '@playwright/test';
import { appRoute } from './helpers';

test.describe('Admin user roles', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(appRoute('dev/login?god=1'));
    await expect(page).toHaveURL(/admin\/dashboard/, { timeout: 20_000 });
  });

  test('user roles page loads faculties and users via API', async ({ page }) => {
    const facultiesPromise = page.waitForResponse(
      (res) => res.url().includes('admin/getFaculties') && res.status() === 200,
      { timeout: 20_000 }
    );
    const usersPromise = page.waitForResponse(
      (res) => res.url().includes('admin/getUsersWithRole') && res.status() === 200,
      { timeout: 20_000 }
    );

    await page.goto(appRoute('admin/user-roles'));

    const facultiesRes = await facultiesPromise;
    const facultiesJson = await facultiesRes.json();
    expect(facultiesJson.success).toBe(true);
    expect(Array.isArray(facultiesJson.data)).toBe(true);

    const usersRes = await usersPromise;
    const usersJson = await usersRes.json();
    expect(usersJson.success).toBe(true);
    expect(Array.isArray(usersJson.data)).toBe(true);

    await expect(page.locator('body')).not.toContainText('Unexpected token');
    await expect(page.locator('#usersTable')).toBeVisible({ timeout: 15_000 });
  });
});
