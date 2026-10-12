import { test, expect } from '@playwright/test';

test('login page has title', async ({ page }) => {
  await page.goto('http://localhost:9000/public/index.php');

  // Expect a title "to contain" a substring.
  await expect(page).toHaveTitle(/Login/);
});
