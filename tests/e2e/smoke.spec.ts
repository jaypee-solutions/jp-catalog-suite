import { test, expect, type Page } from '@playwright/test';

const ADMIN_USER = process.env.WP_ADMIN_USER ?? 'admin';
const ADMIN_PASS = process.env.WP_ADMIN_PASS ?? 'password';

async function login( page: Page ): Promise<void> {
	await page.context().clearCookies();
	await page.request.get( '/wp-login.php' );
	const response = await page.request.post( '/wp-login.php', {
		form: {
			log: ADMIN_USER,
			pwd: ADMIN_PASS,
			'wp-submit': 'Log In',
			redirect_to: '/wp-admin/',
			testcookie: '1',
		},
		maxRedirects: 0,
	} );
	expect( response.status() ).toBe( 302 );
	await page.goto( '/wp-admin/' );
	await expect( page ).toHaveURL( /wp-admin/ );
}

test.describe( 'JayPee Solutions Catalog Suite for WooCommerce — smoke', () => {
	test( 'the plugin is active and its Updates page loads', async ( { page } ) => {
		await login( page );

		await page.goto( '/wp-admin/plugins.php' );
		await expect(
			page.locator( 'tr.active', { hasText: 'JayPee Solutions Catalog Suite for WooCommerce' } )
		).toBeVisible();

		await page.goto( '/wp-admin/options-general.php?page=jp-catalog-suite-updates' );
		await expect( page.getByLabel( 'GitHub Token' ) ).toBeVisible();
	} );

	test( 'the front page renders without a PHP error', async ( { page } ) => {
		const response = await page.goto( '/' );

		expect( response?.status() ).toBe( 200 );
		await expect( page.locator( 'body' ) ).not.toContainText( 'Fatal error' );
	} );
} );
