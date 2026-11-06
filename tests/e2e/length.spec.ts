import { test, expect, type Page } from '@playwright/test';

const ADMIN_USER = process.env.WP_ADMIN_USER ?? 'admin';
const ADMIN_PASS = process.env.WP_ADMIN_PASS ?? 'password';

async function login( page: Page ): Promise<void> {
	await page.context().clearCookies();
	await page.request.get( '/wp-login.php' );
	const response = await page.request.post( '/wp-login.php', { form: { log: ADMIN_USER, pwd: ADMIN_PASS, 'wp-submit': 'Log In', redirect_to: '/wp-admin/', testcookie: '1' }, maxRedirects: 0 } );
	expect( response.status() ).toBe( 302 );
}

test( 'simple variable length product keeps lengths as distinct inquiry lines', async ( { page } ) => {
	await page.goto( '/product/length-bar/' );
	const length = page.locator( 'input[name="jps_cs_length"]' );
	await expect( length ).toBeVisible();
	await length.fill( '120' );
	await page.getByRole( 'button', { name: /add to inquiry|add to cart/i } ).click();
	await expect( page.locator( 'body' ) ).toContainText( '120 cm' );
	await page.goto( '/product/length-bar/' );
	await length.fill( '150' );
	await page.getByRole( 'button', { name: /add to inquiry|add to cart/i } ).click();
	await page.goto( '/cart/' );
	await expect( page.locator( '.wp-block-woocommerce-cart' ) ).toBeVisible();
	await expect( page.locator( '.wp-block-woocommerce-cart' ) ).toContainText( '120 cm' );
	await expect( page.locator( '.wp-block-woocommerce-cart' ) ).toContainText( '150 cm' );
	await page.goto( '/checkout/' );
	await page.getByLabel( /first name/i ).fill( 'Length' );
	await page.getByLabel( /last name/i ).fill( 'Customer' );
	await page.getByLabel( /email address/i ).fill( 'length@example.com' );
	await page.getByRole( 'button', { name: /place order|send inquiry/i } ).click();
	await expect( page.locator( 'body' ) ).toContainText( /order received|inquiry received/i );
	await login( page );
	await page.goto( '/wp-admin/edit.php?post_type=shop_order' );
	await page.getByRole( 'link', { name: /length customer/i } ).first().click();
	await expect( page.locator( 'body' ) ).toContainText( 'Length: 120 cm' );
} );
