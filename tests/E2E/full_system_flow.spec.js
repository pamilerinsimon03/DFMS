import { test, expect } from '@playwright/test';
import path from 'path';

test.describe('Full System Lifecycle', () => {
    
  test('should handle upload, rejection, and verification loop', async ({ page }) => {
    test.setTimeout(60000); // Allow more time for full flow

    // --- PHASE 1: Student Upload ---
    await page.goto('http://localhost:9000/public/index.php');

    await page.fill('input[name="matric_number"]', 'E2E/2026/001');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await expect(page.locator('body')).toContainText('E2E Student');

    // Navigate to Dashboard to access Upload Form
    await page.goto('http://localhost:9000/app/controllers/dashboard.php');
    
    // Select File Requirement (Dropdown)
    // The seeded one is "E2E Upload Project"
    const reqOption = await page.getByRole('option', { name: 'E2E Upload Project' }).first();
    // Or if standard select
    await page.selectOption('select[name="document_name"]', { label: 'E2E Upload Project' });

    // Upload File
    const fileInput = page.locator('input[type="file"]');
    await fileInput.setInputFiles('tests/fixtures/dummy.pdf');

    // Submit
    await page.click('button:has-text("Upload")');
    
    // Verify Success or Debug Failure
    // Wait for either success or specific error to fail fast with useful info
    try {
        await expect(page.locator('body')).toContainText('successfully uploaded');
    } catch (e) {
        // If success message not found, log everything visible
        const bodyText = await page.locator('body').innerText();
        console.log('--- FAILURE DEBUG START ---');
        console.log(bodyText);
        console.log('--- FAILURE DEBUG END ---');
        throw e;
    }
    
    // Logout
    await page.goto('http://localhost:9000/app/controllers/logout.php');


    // --- PHASE 2: Admin Rejection ---
    await page.goto('http://localhost:9000/public/index.php');
    await page.fill('input[name="matric_number"]', 'ADMIN/001');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');

    // Navigate to Verifications
    await page.goto('http://localhost:9000/app/controllers/verifications.php');
    
    // Find the uploaded document
    // Should see "E2E Student" and "E2E Upload Project"
    await expect(page.locator('body')).toContainText('E2E Upload Project');
    
    // Click "View" action
    // Assuming a table with "View" link. Usually View button goes to verification_detail.php?id=...
    // We can click the first "View" text
    await page.click('text=View'); // or .btn-info

    // In Detail View: Reject
    // Click "Reject" button to open modal
    await page.click('#reject-trigger-btn'); 
    
    // Wait for modal visibility if needed, but fill should wait
    // Fill comment in the modal textarea
    await page.fill('#rejection-textarea', 'Please fix formatting');
    
    // Submit Rejection (Click "Reject Document" in modal)
    await page.click('#confirm-rejection');

    // Verify status updated
    await expect(page.locator('body')).toContainText('Rejected');
    
    // Logout
    await page.goto('http://localhost:9000/app/controllers/logout.php');


    // --- PHASE 3: Student Correction ---
    await page.goto('http://localhost:9000/public/index.php');
    await page.fill('input[name="matric_number"]', 'E2E/2026/001');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');

    // Check Notifications or Status
    // Should see "Rejected" status somewhere, e.g. "My Documents"
    await page.click('text=My Documents');
    await expect(page.locator('body')).toContainText('Rejected');
    await expect(page.locator('body')).toContainText('Please fix formatting');

    // Re-upload (Action button?)
    await page.click('text=Re-upload');
    
    // Upload again
    await page.setInputFiles('input[type="file"]', 'tests/fixtures/dummy.pdf');
    await page.click('button:has-text("Upload")');
    
    await expect(page.locator('body')).toContainText('successfully uploaded'); // or updated
    await page.goto('http://localhost:9000/app/controllers/logout.php');


    // --- PHASE 4: Admin Verification ---
    await page.goto('http://localhost:9000/public/index.php');
    await page.fill('input[name="matric_number"]', 'ADMIN/001');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');

    await page.click('text=Verifications');
    await page.click('text=View'); // Open detail
    
    // Approve
    await page.click('text=Approve'); // or Verify
    // Confirm?
    await page.click('button:has-text("Confirm")'); // if modal

    await expect(page.locator('body')).toContainText('Verified');
  });
});
