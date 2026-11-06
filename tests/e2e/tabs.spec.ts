import { expect, test } from '@playwright/test';

test('product page displays public and protected product tab content by access', async ({ page }) => {
  await page.goto('/product/tab-demo/');
  await expect(page.getByRole('tab', { name: 'Downloads' })).toBeVisible();
  await page.getByRole('tab', { name: 'Downloads' }).click();
  await expect(page.getByText('Public info')).toBeVisible();
  await expect(page.getByText('Protected Downloads content.')).toHaveCount(0);
  await expect(page.getByText('Please')).toBeVisible();

  await page.context().clearCookies();
  await page.request.get('/wp-login.php');
  const response = await page.request.post('/wp-login.php', {
    form: { log: 'customer1', pwd: 'password', 'wp-submit': 'Log In', redirect_to: '/', testcookie: '1' },
    maxRedirects: 0,
  });
  expect(response.status()).toBe(302);
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
