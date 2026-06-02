import { expect, test } from '@playwright/test';
import { appRoute } from './helpers';

const pages = [
  { name: 'login', route: 'auth/login', css: /tailwind\.min\.css$/ },
  { name: 'dashboard', route: 'dev/login', css: /tailwind\.min\.css$/, followDashboard: true },
  { name: 'admin-dashboard', route: 'dev/login?god=1', css: /tailwind\.min\.css$/, followAdmin: true },
];

for (const { name, route, css, followDashboard, followAdmin } of pages) {
  test(`${name}: stylesheets load (200)`, async ({ page }) => {
    await page.goto(appRoute(route), { waitUntil: 'domcontentloaded' });
    if (followDashboard) {
      await expect(page).toHaveURL(/dashboard/, { timeout: 15_000 });
    }
    if (followAdmin) {
      await expect(page).toHaveURL(/admin\/dashboard/, { timeout: 15_000 });
    }

    const links = await page.locator('link[rel="stylesheet"]').evaluateAll((els) =>
      els.map((el) => (el as HTMLLinkElement).href),
    );

    const tailwind = links.filter((href) => css.test(href));
    expect(tailwind.length, `expected tailwind link on ${name}`).toBeGreaterThan(0);

    for (const href of tailwind) {
      expect(href, 'must not double public/ in path').not.toMatch(/public\/public\//);
      expect(href, 'must not glue index.php to assets').not.toMatch(/index\.phpassets/);
      expect(href, 'must not glue index.php to public').not.toMatch(/index\.phppublic/);

      const res = await page.request.get(href);
      expect(res.status(), href).toBe(200);
    }
  });
}

test('admin dashboard has sidebar layout (tailwind)', async ({ page }) => {
  await page.goto(appRoute('dev/login?god=1'));
  await expect(page).toHaveURL(/admin\/dashboard/, { timeout: 15_000 });
  const aside = page.locator('aside.w-64');
  await expect(aside).toBeVisible();
  const box = await aside.boundingBox();
  expect(box?.width ?? 0).toBeGreaterThan(200);
  await expect(page.locator('nav.bg-white.shadow-sm').first()).toBeVisible();
});

test('login page layout (card + dev box)', async ({ page }) => {
  await page.goto(appRoute('auth/login'));
  await expect(page.locator('.gradient-bg .bg-white.rounded-2xl')).toBeVisible();
  await expect(page.getByText('Development — ข้าม OAuth')).toBeVisible();
  const box = await page.locator('.gradient-bg .bg-white.rounded-2xl').boundingBox();
  expect(box?.width ?? 0).toBeGreaterThan(200);
});
