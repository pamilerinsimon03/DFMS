import { test, expect } from '@playwright/test';

test.describe('NDPR Data Portability & Privacy Rights', () => {

  test('should allow authenticated student to download personal data JSON export', async ({ page }) => {
    // 1. Login
    await page.goto('http://localhost:9000/public/index.php');
    await page.fill('input[name="matric_number"]', 'E2E/2026/001');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');

    // 2. Navigate to Data Subject Rights page
    await page.goto('http://localhost:9000/app/controllers/data_subject_rights.php');

    // 3. Trigger JSON Data Download
    const downloadPromise = page.waitForEvent('download');
    await page.click('button:has-text("Export My Personal Data")');
    const download = await downloadPromise;

    // 4. Verify Download File Properties
    expect(download.suggestedFilename()).toBe('personal_data_export.json');
  });

});
