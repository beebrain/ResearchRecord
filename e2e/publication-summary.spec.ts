import { expect, test } from '@playwright/test';
import { appRoute } from './helpers';

test.describe('Publication summary by curriculum', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(appRoute('dev/login?god=1'));
    await expect(page).toHaveURL(/admin\/dashboard/, { timeout: 20_000 });
  });

  test('summary page loads faculty list and stats', async ({ page }) => {
    await page.goto(appRoute('admin/publications/summary'));
    await expect(page.locator('body')).toContainText('สรุปผลงานวิจัยตามหลักสูตร');

    await expect(page.locator('#faculty-list')).toContainText('ทุกคณะ', { timeout: 15_000 });
    await expect(page.locator('#faculty-list')).not.toContainText('ไม่สามารถโหลดข้อมูล');
    await expect(page.locator('#curriculum-grid .curriculum-card').first()).toBeVisible({ timeout: 15_000 });
  });

  test('pdfmake scripts load', async ({ page }) => {
    const pdfmakeOk = page.waitForResponse(
      (res) => res.url().includes('pdfmake/build/pdfmake.min.js') && res.status() === 200,
      { timeout: 15_000 }
    );
    await page.goto(appRoute('admin/publications/summary'));
    await pdfmakeOk;
  });
});
