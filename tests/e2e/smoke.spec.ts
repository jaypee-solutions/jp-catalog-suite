import { test, expect } from './support/fixtures';
import { login } from './support/auth';

test.describe( 'JayPee Solutions Catalog Suite for WooCommerce — smoke', () => {
	test( 'the plugin is active and its Updates page loads', async ( { page } ) => {
		await login( page );

		await page.goto( '/wp-admin/plugins.php' );
		// `:not(.plugin-update-tr)`: an available update adds a second, also `.active` row.
		await expect(
			page.locator( 'tr.active:not(.plugin-update-tr)', { hasText: 'JayPee Solutions Catalog Suite for WooCommerce' } )
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
