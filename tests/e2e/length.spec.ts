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
	const orderLabel = `Length-${ Date.now() }`;
	await page.goto( '/product/length-bar/' );
	const length = page.locator( 'input[name="jps_cs_length"]' );
	await expect( length ).toBeVisible();
	await length.fill( '120' );
	await page.locator( '.single_add_to_cart_button' ).click();
	await page.goto( '/cart/' );
	await expect( page.locator( '.wp-block-woocommerce-cart' ) ).toContainText( '120 cm' );
	await page.goto( '/product/length-bar/' );
	await length.fill( '150' );
	await page.locator( '.single_add_to_cart_button' ).click();
	await page.goto( '/cart/' );
	await expect( page.locator( '.wp-block-woocommerce-cart' ) ).toBeVisible();
	await expect( page.locator( '.wp-block-woocommerce-cart' ) ).toContainText( '120 cm' );
	await expect( page.locator( '.wp-block-woocommerce-cart' ) ).toContainText( '150 cm' );
	await page.goto( '/checkout/' );
	await page.locator( 'select[name="billing_country"]' ).selectOption( 'DE' );
	await page.getByLabel( /first name|vorname/i ).fill( 'Length' );
	await page.getByLabel( /last name|nachname/i ).fill( orderLabel );
	await page.getByLabel( /email address|e-?mail-adresse/i ).fill( `length-${ Date.now() }@example.com` );
	await page.getByRole( 'checkbox', { name: /Terms and Conditions|Bedingungen|AGB/i } ).check();
	await page.locator( '.wc-block-components-checkout-place-order-button' ).click();
	await expect( page ).toHaveURL( /order-received/ );
	const orderId = page.url().match( /order-received\/(\d+)/ )?.[ 1 ];
	expect( orderId ).toBeDefined();
	await login( page );
	await page.goto( '/wp-admin/admin.php?page=wc-orders' );
	const order = page.locator( '#the-list tr' ).filter( { hasText: orderLabel } );
	await expect( order ).toContainText( 'Inquiry' );
	await page.goto( `/wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }` );
	await expect( page.locator( 'body' ) ).toContainText( 'Length: 120 cm' );
} );
