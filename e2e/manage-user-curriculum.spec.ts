import { expect, test } from '@playwright/test';
import { appRoute } from './helpers';

test.describe('Dynamic Verification — User Curriculum Management', () => {
  test.beforeEach(async ({ page }) => {
    // Login as admin/god
    await page.goto(appRoute('dev/login?god=1'), { waitUntil: 'domcontentloaded' });
    await expect(page).toHaveURL(/admin\/dashboard/, { timeout: 20_000 });
  });

  test('should load page, search, filter, and open chair modal without errors', async ({ page }) => {
    const consoleErrors: string[] = [];
    const badRequests: string[] = [];

    // Capture console errors
    page.on('pageerror', (err) => {
      const msg = err.message || '';
      // Ignore debugbar syntax errors
      if (msg.includes('debugbar') || msg.includes("Unexpected token '<'")) {
        return;
      }
      consoleErrors.push(msg);
    });

    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        const text = msg.text() || '';
        // Ignore debugbar and chrome extension noise
        if (
          text.includes('debugbar') ||
          text.includes('chrome-extension') ||
          text.includes("Unexpected token '<'") ||
          text.includes('404')
        ) {
          return;
        }
        consoleErrors.push(text);
      }
    });

    // Capture response errors
    page.on('response', (res) => {
      const status = res.status();
      const url = res.url();
      if (url.includes('index.php') && !url.includes('debugbar') && status >= 400) {
        badRequests.push(`[${status}] ${url}`);
      }
    });

    // Navigate to manage-user-curriculum
    console.log('Navigating to manage-user-curriculum page...');
    await page.goto(appRoute('admin/manage-user-curriculum'), { waitUntil: 'networkidle' });

    // Verify page title/content
    await expect(page.locator('h2')).toContainText('จัดการหลักสูตรผู้ใช้');

    // 1. Test Chair Modal Selection (clicks "เลือกประธานหลักสูตร" button)
    console.log('Testing open chair modal on initial load...');
    // Wait for the button to be visible and click it
    const chairBtn = page.locator('button:has-text("เลือกประธานหลักสูตร")').first();
    await expect(chairBtn).toBeVisible({ timeout: 10_000 });

    const deanSelectionPromise = page.waitForResponse(
      (res) => res.url().includes('getUsersForDeanSelection') && res.status() === 200,
      { timeout: 10_000 }
    );
    await chairBtn.click();
    await deanSelectionPromise;

    // Verify modal is visible
    await expect(page.locator('#chairModal')).toBeVisible();
    
    // Select an option from chairSelect (excluding empty)
    const select = page.locator('#chairSelect');
    const count = await select.locator('option').count();
    console.log(`Chair select options count: ${count}`);
    
    if (count > 1) {
      await select.selectOption({ index: 1 });
    }
    
    // Close the modal
    await page.locator('button:has-text("ยกเลิก")').first().click();
    await expect(page.locator('#chairModal')).toBeHidden();

    // 2. Test search user filtering
    console.log('Testing user search input...');
    const searchPromise = page.waitForResponse(
      (res) => res.url().includes('getAllUsersForCurriculumManagement') && res.status() === 200,
      { timeout: 10_000 }
    );
    await page.locator('#user-search').fill('test');
    await searchPromise;

    // 3. Test user faculty filter
    console.log('Testing user faculty filter...');
    const userFacultyPromise = page.waitForResponse(
      (res) => res.url().includes('getAllUsersForCurriculumManagement') && res.status() === 200,
      { timeout: 10_000 }
    );
    await page.locator('#user-faculty-filter').selectOption({ index: 1 }); // Select second option ("null")
    await userFacultyPromise;

    // 4. Test curriculum faculty filter
    console.log('Testing curriculum faculty filter...');
    const curriculumFacultyPromise = page.waitForResponse(
      (res) => res.url().includes('getCurriculumsByFacultyWithMembers') && res.status() === 200,
      { timeout: 10_000 }
    );
    await page.locator('#curriculum-faculty-filter').selectOption({ index: 1 }); // Select second option ("null")
    await curriculumFacultyPromise;

    // Asserts
    expect(consoleErrors, `Found console/page errors:\n${consoleErrors.join('\n')}`).toEqual([]);
    expect(badRequests, `Found API request failures:\n${badRequests.join('\n')}`).toEqual([]);
  });
});
