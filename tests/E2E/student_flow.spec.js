import { test, expect } from '@playwright/test';

test.describe('Student Authentication Flow', () => {
    
  test('should allow student to login with valid credentials', async ({ page }) => {
    // 1. Go to Login
    await page.goto('http://localhost:9000/public/index.php');
    
    // 2. Check we are on login page
    await expect(page).toHaveTitle(/Login/);
    
    // 3. Fill Form
    // Assuming input names based on typical forms, or viewed earlier login.php
    // Viewed login.php earlier: name="matric_number", name="password"
    await page.fill('input[name="matric_number"]', 'E2E/2026/001');
    await page.fill('input[name="password"]', 'password123');
    
    // 4. Submit
    await page.click('button[type="submit"]');
    
    // 5. Verify Redirect
    // Wait for navigation or specific element
    // await page.waitForLoadState('networkidle'); // Can be flaky
    
    // Check if we are redirected to student dashboard
    // Attempt to wait for a known dashboard element or the body to NOT contain "Login" (if successful)
    // Or check URL
    try {
      await expect(page).toHaveURL(/.*dashboard.*/, { timeout: 5000 });
    } catch (e) {
      console.log('URL check failed. Current URL:', page.url());
      const bodyText = await page.innerText('body');
      console.log('Body Text Snippet:', bodyText.substring(0, 500));
      throw e;
    }
    
    await expect(page.locator('body')).toContainText('E2E Student');
  });

  test('should show error for invalid credentials', async ({ page }) => {
    await page.goto('http://localhost:9000/public/index.php');
    await page.fill('input[name="matric_number"]', 'E2E/2026/001');
    await page.fill('input[name="password"]', 'wrongpass');
    await page.click('button[type="submit"]');
    
    await expect(page.locator('body')).toContainText('Incorrect password. Please check your password and try again.');
  });

  test('should show error for non-existent user', async ({ page }) => {
    await page.goto('http://localhost:9000/public/index.php');
    await page.fill('input[name="matric_number"]', 'NON/EXISTENT/001');
    await page.fill('input[name="password"]', 'anypass');
    await page.click('button[type="submit"]');
    
    await expect(page.locator('body')).toContainText('Matric number not found');
  });
});
