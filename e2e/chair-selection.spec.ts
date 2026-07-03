import { expect, test } from '@playwright/test';
import { appRoute } from './helpers';

test.describe('Chair selection autocomplete', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(appRoute('dev/login?god=1'), { waitUntil: 'domcontentloaded' });
    await expect(page).toHaveURL(/admin\/dashboard/, { timeout: 20_000 });
    await page.goto(appRoute('admin/manage-user-curriculum'), { waitUntil: 'networkidle' });
  });

  test('shows conflict warning when selecting teacher with existing role', async ({ page }) => {
    const chairBtn = page.locator('button:has-text("เลือกประธานหลักสูตร")').first();
    await expect(chairBtn).toBeVisible({ timeout: 10_000 });
    await chairBtn.click();

    await expect(page.locator('#chairModal')).toBeVisible();
    const searchInput = page.locator('#chairTeacherSearch');

    const searchPromise = page.waitForResponse(
      (res) => res.url().includes('searchTeachersForChairSelection') && res.status() === 200,
      { timeout: 15_000 },
    );
    await searchInput.fill('สม');
    const response = await searchPromise;
    const body = await response.json();

    expect(body.success).toBe(true);
    expect(Array.isArray(body.data)).toBe(true);

    const conflictTeacher = (body.data as Array<{ has_conflict?: boolean }>).find((t) => t.has_conflict);
    test.skip(!conflictTeacher, 'No conflict teacher in local database for this query');

    await page.locator('.chair-ac-option').first().click();

    await expect(page.locator('#chairConflictWarning')).toBeVisible();
    await expect(page.locator('#chairConflictList li')).not.toHaveCount(0);
  });

  test('clear button resets selection and hides warning', async ({ page }) => {
    await page.locator('button:has-text("เลือกประธานหลักสูตร")').first().click();
    await page.locator('#chairTeacherSearch').fill('สม');
    await page.waitForResponse(
      (res) => res.url().includes('searchTeachersForChairSelection') && res.status() === 200,
    );

    const options = page.locator('.chair-ac-option');
    if (await options.count() > 0) {
      await options.first().click();
    }

    await page.locator('button:has-text("ล้าง / ไม่ระบุประธาน")').click();
    await expect(page.locator('#chairTeacherSearch')).toHaveValue('');
    await expect(page.locator('#chairConflictWarning')).toBeHidden();
  });
});
