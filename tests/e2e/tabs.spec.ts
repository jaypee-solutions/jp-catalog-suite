import { expect, test } from '@playwright/test';

test('product page displays a global product tab', async ({ page }) => {
  await page.goto('/product/tab-demo/');
  await expect(page.getByRole('tab', { name: 'Downloads' })).toBeVisible();
  await page.getByRole('tab', { name: 'Downloads' }).click();
  await expect(page.getByText('Public info')).toBeVisible();
});
