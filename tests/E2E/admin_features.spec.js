import { test, expect } from '@playwright/test';

test.describe('Admin Features', () => {
    
  // Helper to Login
  async function adminLogin(page) {
    await page.goto('http://localhost:9000/public/index.php');
    await page.fill('input[name="matric_number"]', 'ADMIN/001');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await expect(page.locator('body')).toContainText('E2E Admin');
  }

  test('should add a new student', async ({ page }) => {
    await adminLogin(page);
    
    // Direct navigation is more robust
    await page.goto('http://localhost:9000/app/controllers/students.php');
    await page.goto('http://localhost:9000/app/controllers/add_student.php');
    
    // Fill Add Student Form (Unique to avoid conflict)
    const uniqueId = Date.now().toString().slice(-4);
    await page.fill('input[name="matric_number"]', `NEW/2026/${uniqueId}`);
    await page.fill('input[name="full_name"]', 'New Student User');
    await page.selectOption('select[name="department_id"]', { index: 1 }); // Select a department (0 is often "Select")
    
    // Missing Required Fields
    await page.selectOption('select[name="level"]', '100L');
    await page.fill('input[name="session"]', '2025/2026');
    await page.fill('input[name="programme"]', 'Computer Science');
    
    // Check for optional fields
    if (await page.isVisible('input[name="email"]')) {
        await page.fill('input[name="email"]', 'newstd@test.com');
    }
    
    // Submit
    await page.click('button[type="submit"]');
    
    // Verify
    await expect(page.locator('body')).toContainText('successfully');
    
    // Verify list update - Navigate to list
    await page.goto('http://localhost:9000/app/controllers/students.php');
    await expect(page.locator('body')).toContainText('New Student User');
  });

  test('should create a new file requirement', async ({ page }) => {
    await adminLogin(page);
    
    await page.goto('http://localhost:9000/app/controllers/file_management.php');
    
    // Form is inline, no "Add" button to click. No department selection (inferred).
    
    await page.fill('input[name="document_name"]', 'New E2E Requirement');
    await page.fill('textarea[name="description"]', 'Description');
    await page.fill('input[name="due_date"]', '2030-12-31');
    
    await page.click('button[type="submit"]');
    
    await expect(page.locator('body')).toContainText('successfully'); // or Check list
    await expect(page.locator('body')).toContainText('New E2E Requirement');
  });

  test('should update settings', async ({ page }) => {
    await adminLogin(page);
    
    await page.goto('http://localhost:9000/app/controllers/settings.php');
    
    // Update active session (Input name is session_year)
    await page.fill('input[name="session_year"]', '2028/2029');
    await page.click('button[value="update_preferences"]'); // Specific button
    
    // Check for success message or reload
    // Form usually redirects or shows message
    // Since we can't easily check flash message without reload persistence or specific selector,
    // we can check if the input value persisted or finding success alert
    await expect(page.locator('body')).toContainText('System preferences updated successfully'); // Matches controller msg
  });

  test('should load analytics', async ({ page }) => {
    await adminLogin(page);
    
    // Navigate to Admin Dashboard (Analytics)
    await page.goto('http://localhost:9000/app/controllers/admin.php');
    
    // Check for specific stats blocks from admin.php
    // Usually has "Total Documents", "Approved", "Pending"
    await expect(page.locator('body')).toContainText('Total Documents');
    await expect(page.locator('body')).toContainText('Pending Documents');
    await expect(page.locator('body')).toContainText('Approved');
  });

});
