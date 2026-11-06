import { execFileSync } from 'node:child_process';
import { test, expect, type Page } from '@playwright/test';

function wpCli( ...args: string[] ): string {
	const output = execFileSync( 'npx', [ 'wp-env', 'run', 'cli', '--', 'wp', ...args ], { encoding: 'utf8', env: { ...process.env, WP_ENV_HOME: './.wp-env' } } );
	const numeric = output.split( /\r?\n/ ).map( ( line ) => line.trim() ).filter( ( line ) => /^\d+$/.test( line ) );
	return numeric.length ? numeric[ numeric.length - 1 ] : output.split( /\r?\n/ ).map( ( line ) => line.trim() ).filter( ( line ) => line && ! line.startsWith( 'ℹ' ) && ! line.startsWith( '✔' ) && ! line.startsWith( 'Deprecated:' ) ).join( '\n' ).trim();
}

const ADMIN_USER = process.env.WP_ADMIN_USER ?? 'admin';
const ADMIN_PASS = process.env.WP_ADMIN_PASS ?? 'password';
let oldCart = '';
let oldCheckout = '';
let oldQuote = '';
let oldComingSoon = '';

async function login( page: Page ): Promise<void> {
	await page.context().clearCookies();
	await page.request.get( '/wp-login.php' );
	const response = await page.request.post( '/wp-login.php', { form: { log: ADMIN_USER, pwd: ADMIN_PASS, 'wp-submit': 'Log In', redirect_to: '/wp-admin/', testcookie: '1' }, maxRedirects: 0 } );
	expect( response.status() ).toBe( 302 );
}

test.beforeAll( () => {
	oldCart = wpCli( 'option', 'get', 'woocommerce_cart_page_id' );
	oldCheckout = wpCli( 'option', 'get', 'woocommerce_checkout_page_id' );
	oldQuote = wpCli( 'option', 'get', 'jps_cs_module_quote' );
	oldComingSoon = wpCli( 'option', 'get', 'woocommerce_coming_soon' );
	wpCli( 'option', 'update', 'woocommerce_coming_soon', 'no' );
	wpCli( 'option', 'update', 'jps_cs_module_quote', 'yes' );
	const cart = wpCli( 'post', 'list', '--post_type=page', '--name=classic-cart', '--field=ID' );
	const checkout = wpCli( 'post', 'list', '--post_type=page', '--name=classic-checkout', '--field=ID' );
	wpCli( 'option', 'update', 'woocommerce_cart_page_id', cart );
	wpCli( 'option', 'update', 'woocommerce_checkout_page_id', checkout );
} );

test.afterAll( () => {
	if ( oldCart ) wpCli( 'option', 'update', 'woocommerce_cart_page_id', oldCart );
	if ( oldCheckout ) wpCli( 'option', 'update', 'woocommerce_checkout_page_id', oldCheckout );
	wpCli( 'option', 'update', 'jps_cs_module_quote', oldQuote );
	wpCli( 'option', 'update', 'woocommerce_coming_soon', oldComingSoon );
} );

test( 'guest sends an inquiry on classic checkout', async ( { page } ) => {
	const orderLabel = `Classic-${ Date.now() }`;
	const quoteProfile = wpCli( 'post', 'list', '--post_type=product', '--meta_key=_sku', '--meta_value=QP-1', '--field=ID' );
	const pricedBolt = wpCli( 'post', 'list', '--post_type=product', '--meta_key=_sku', '--meta_value=QP-2', '--field=ID' );
	await page.goto( '/?add-to-cart=' + quoteProfile );
	await page.goto( '/?add-to-cart=' + pricedBolt );
	await page.goto( '/classic-checkout/' );
	await expect( page.locator( '#payment .wc_payment_methods li' ) ).toHaveCount( 0 );
	await page.locator( '#billing_email' ).fill( `classic-${ Date.now() }@example.com` );
	await page.locator( '#billing_first_name' ).fill( 'Guest' );
	await page.locator( '#billing_last_name' ).fill( orderLabel );
	await page.locator( '#billing_address_1' ).fill( '1 Main Street' );
	await page.locator( '#billing_city' ).fill( 'Berlin' );
	await page.locator( '#billing_postcode' ).fill( '10115' );
	await page.locator( '#billing_country' ).selectOption( 'DE' );
	await page.getByRole( 'checkbox', { name: /Terms and Conditions and Cancellation Policy/ } ).check();
	await page.locator( '#place_order' ).click();
	await expect( page ).toHaveURL( /order-received/ );
	await login( page );
	await page.goto( '/wp-admin/admin.php?page=wc-orders' );
	await expect( page.locator( '#the-list tr' ).filter( { hasText: orderLabel } ) ).toContainText( 'Inquiry' );
} );

