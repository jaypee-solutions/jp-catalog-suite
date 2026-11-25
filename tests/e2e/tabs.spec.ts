import { expect, test } from '@playwright/test';
import { loginAs } from './support/auth';
import { wpCli } from './support/wp-cli';

test('product page displays public and protected product tab content by access', async ({ page }) => {
  await page.goto('/product/tab-demo/');
  await expect(page.getByRole('tab', { name: 'Downloads' })).toBeVisible();
  await page.getByRole('tab', { name: 'Downloads' }).click();
  await expect(page.getByText('Public info')).toBeVisible();
  await expect(page.getByText('Protected Downloads content.')).toHaveCount(0);
  await expect(page.locator('.jps-cs-protected-notice a[href*="redirect_to"]')).toBeVisible();

  await loginAs( page, 'customer1', 'password' );
  await page.goto('/product/tab-demo/');
  await page.getByRole('tab', { name: 'Downloads' }).click();
  await expect(page.getByText('Protected Downloads content.')).toBeVisible();
  const fileEntry = page.getByRole( 'link', { name: /part\.stp/ } );
  await expect( fileEntry ).toBeVisible();
  await expect( fileEntry ).toContainText( 'STP' );
  await expect( fileEntry ).toContainText( '36 B' );
  await expect( fileEntry ).toHaveAttribute( 'href', /jps_cs_file=/ );
  await expect( fileEntry ).toHaveAttribute( 'href', /product=/ );
  const fileResponse = await page.request.get( await fileEntry.getAttribute( 'href' ) || '' );
  expect( fileResponse.status() ).toBe( 200 );
});

test('product editor exposes the Tabs panel controls to an administrator', async ({ page }) => {
  await loginAs( page );
  const productId = wpCli( 'post', 'list', '--post_type=product', '--name=tab-demo', '--field=ID' );
  await page.goto(`/wp-admin/post.php?post=${productId}&action=edit`);
  await page.locator('a[href="#jps-cs-product-tabs"]').click();
  await expect(page.locator('#jps-cs-product-tabs input[name="_jps_cs_hidden_tabs[]"]')).toBeVisible();
  await expect(page.locator('#jps-cs-product-tabs .jps-cs-tab-open', { hasText: 'Add tab' })).toBeVisible();
});
