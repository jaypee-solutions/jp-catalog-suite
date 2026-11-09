import { expect, test } from '@playwright/test';
import { execFileSync } from 'node:child_process';

test('product page displays public and protected product tab content by access', async ({ page }) => {
  await page.goto('/product/tab-demo/');
  await expect(page.getByRole('tab', { name: 'Downloads' })).toBeVisible();
  await page.getByRole('tab', { name: 'Downloads' }).click();
  await expect(page.getByText('Public info')).toBeVisible();
  await expect(page.getByText('Protected Downloads content.')).toHaveCount(0);
  await expect(page.locator('.jps-cs-protected-notice a[href*="redirect_to"]')).toBeVisible();

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
function wpCli( ...args: string[] ): string {
  const output = execFileSync( 'npx', [ 'wp-env', 'run', 'cli', '--', 'wp', ...args ], { encoding: 'utf8', env: { ...process.env, WP_ENV_HOME: './.wp-env' } } );
  return output.split( /\r?\n/ ).map( ( line ) => line.trim() ).filter( ( line ) => /^\d+$/.test( line ) ).pop() || '';
}

test('product editor exposes the Tabs panel controls to an administrator', async ({ page }) => {
  await page.request.get('/wp-login.php');
  const login = await page.request.post('/wp-login.php', { form: { log: process.env.WP_ADMIN_USER ?? 'admin', pwd: process.env.WP_ADMIN_PASS ?? 'password', 'wp-submit': 'Log In', redirect_to: '/wp-admin/', testcookie: '1' }, maxRedirects: 0 });
  expect(login.status()).toBe(302);
  const productId = wpCli( 'post', 'list', '--post_type=product', '--name=tab-demo', '--field=ID' );
  await page.goto(`/wp-admin/post.php?post=${productId}&action=edit`);
  await page.locator('a[href="#jps-cs-product-tabs"]').click();
  await expect(page.locator('#jps-cs-product-tabs input[name="_jps_cs_hidden_tabs[]"]')).toBeVisible();
  await expect(page.locator('#jps-cs-product-tabs .jps-cs-tab-open', { hasText: 'Add tab' })).toBeVisible();
});
