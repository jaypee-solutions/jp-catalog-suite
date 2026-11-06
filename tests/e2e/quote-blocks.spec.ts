import { basename } from 'node:path';
import { execFileSync } from 'node:child_process';
import { test, expect, type Page } from '@playwright/test';

function wpCli( ...args: string[] ): string {
	const output = execFileSync( 'npx', [ 'wp-env', 'run', 'cli', '--', 'wp', ...args ], { encoding: 'utf8', env: { ...process.env, WP_ENV_HOME: './.wp-env' } } );
	const numeric = output.split( /\r?\n/ ).map( ( line ) => line.trim() ).filter( ( line ) => /^\d+$/.test( line ) );
	return numeric.length ? numeric[ numeric.length - 1 ] : output.split( /\r?\n/ ).map( ( line ) => line.trim() ).filter( ( line ) => line && ! line.startsWith( 'ℹ' ) && ! line.startsWith( '✔' ) && ! line.startsWith( 'Deprecated:' ) ).join( '\n' ).trim();
}

function compileGermanCatalog(): void {
	execFileSync( 'npx', [ 'wp-env', 'run', 'cli', `--env-cwd=wp-content/plugins/${ basename( process.cwd() ) }`, 'wp', 'i18n', 'make-mo', 'languages' ], { encoding: 'utf8', env: { ...process.env, WP_ENV_HOME: './.wp-env' } } );
}


function setSiteLocale( locale: string ): void {
	wpCli( 'eval', `add_filter( 'sanitize_option_WPLANG', static fn() => '${ locale }', PHP_INT_MAX ); update_option( 'WPLANG', '${ locale }' );` );
}
const ADMIN_USER = process.env.WP_ADMIN_USER ?? 'admin';
const ADMIN_PASS = process.env.WP_ADMIN_PASS ?? 'password';

async function login( page: Page ): Promise<void> {
	await page.context().clearCookies();
	await page.request.get( '/wp-login.php' );
	const response = await page.request.post( '/wp-login.php', { form: { log: ADMIN_USER, pwd: ADMIN_PASS, 'wp-submit': 'Log In', redirect_to: '/wp-admin/', testcookie: '1' }, maxRedirects: 0 } );
	expect( response.status() ).toBe( 302 );
}

test( 'guest sends a Blocks inquiry which stays listed when Quote mode is off', async ( { page } ) => {
	const oldQuote = wpCli( 'option', 'get', 'jps_cs_module_quote' );
	const oldComingSoon = wpCli( 'option', 'get', 'woocommerce_coming_soon' );
	const oldPhone = wpCli( 'eval', "echo get_option( 'jps_cs_checkout_phone', 'optional' );" );
	const orderLabel = `Blocks-${ Date.now() }`;
	try {
		wpCli( 'option', 'update', 'woocommerce_coming_soon', 'no' );
		wpCli( 'option', 'update', 'jps_cs_module_quote', 'yes' );
		const quoteProfile = wpCli( 'post', 'list', '--post_type=product', '--meta_key=_sku', '--meta_value=QP-1', '--field=ID' );
		const pricedBolt = wpCli( 'post', 'list', '--post_type=product', '--meta_key=_sku', '--meta_value=QP-2', '--field=ID' );
		await page.goto( '/shop/' );
		await expect( page.locator( 'body' ) ).not.toContainText( '€' );
		await page.goto( wpCli( 'post', 'url', pricedBolt ) );
		await expect( page.locator( 'body' ) ).not.toContainText( '€' );
		await expect( page.locator( '.single_add_to_cart_button' ) ).toBeVisible();
		await page.goto( '/?add-to-cart=' + quoteProfile );
		await page.goto( '/?add-to-cart=' + pricedBolt );
		await page.goto( '/cart/' );
		await expect( page.locator( 'body' ) ).not.toContainText( '€' );
		await expect( page.getByRole( 'link', { name: /continue to inquiry|weiter zur anfrage/i } ) ).toBeVisible();
		wpCli( 'option', 'update', 'jps_cs_checkout_phone', 'required' );
		await page.goto( '/checkout/' );
		await expect( page.getByLabel( /country\/region|land\/region/i ) ).toBeVisible();
		await expect( page.getByRole( 'textbox', { name: 'Address', exact: true } ) ).toHaveCount( 0 );
		await expect( page.locator( 'body' ) ).not.toContainText( '€' );
		await expect( page.locator( '.wc-block-checkout__payment-method' ) ).toHaveCount( 0 );
		await expect( page.locator( '.wc-block-checkout__payment-method' ) ).toHaveCount( 0 );
		await expect( page.locator( 'body' ) ).not.toContainText( /zahlungspflichtig/i );
		await expect( page.getByLabel( /company|firma/i ) ).toBeVisible();
		await expect( page.getByLabel( /phone|telefon/i ) ).toHaveAttribute( 'required', '' );
		await expect( page.getByLabel( /phone|telefon/i ) ).toBeVisible();
		await page.getByLabel( /phone|telefon/i ).fill( '030123456' );
		await page.getByLabel( /email address|e-?mail-adresse/i ).fill( `blocks-${ Date.now() }@example.com` );
		await page.getByLabel( /first name|vorname/i ).fill( 'Guest' );
		await expect( page.getByLabel( /first name|vorname/i ) ).toHaveAttribute( 'required', '' );
		await expect( page.getByLabel( /last name|nachname/i ) ).toHaveAttribute( 'required', '' );
		await expect( page.getByLabel( /email address|e-?mail-adresse/i ) ).toHaveAttribute( 'required', '' );
		await page.getByLabel( /last name|nachname/i ).fill( orderLabel );
		const termsCheckbox = page.getByRole( 'checkbox', { name: /Terms and Conditions|Bedingungen|AGB/i } );
		if ( await termsCheckbox.count() ) {
			await termsCheckbox.check();
		}
		await expect( page.locator( '.wc-block-components-checkout-place-order-button' ) ).toBeVisible();
		await page.locator( '.wc-block-components-checkout-place-order-button' ).click();
		await expect( page ).toHaveURL( /order-received/ );

        await expect( page.locator( 'body' ) ).not.toContainText( '€' );

		await login( page );
        await page.goto( '/my-account/' );
        await expect( page.getByRole( 'link', { name: 'Inquiries' } ) ).toBeVisible();
		await page.goto( '/wp-admin/admin.php?page=wc-orders' );
		let order = page.locator( '#the-list tr' ).filter( { hasText: orderLabel } );
		await expect( order ).toContainText( /Inquiry|Anfrage/i );
		wpCli( 'option', 'update', 'jps_cs_module_quote', 'no' );
		await page.reload();
		order = page.locator( '#the-list tr' ).filter( { hasText: orderLabel } );
		await expect( order ).toContainText( /Inquiry|Anfrage/i );
	} finally {
		wpCli( 'option', 'update', 'jps_cs_checkout_phone', oldPhone );
		wpCli( 'option', 'update', 'woocommerce_coming_soon', oldComingSoon );
	}
} );

test( 'Blocks labels follow German locale and custom checkout settings', async ( { page } ) => {
	const oldQuote = wpCli( 'option', 'get', 'jps_cs_module_quote' );
	const oldLocale = wpCli( 'option', 'get', 'WPLANG' );
	const oldPlaceOrder = wpCli( 'option', 'list', '--search=jps_cs_quote_place_order_label', '--field=option_value' );
	try {
		wpCli( 'option', 'update', 'jps_cs_module_quote', 'yes' );
		compileGermanCatalog();
		setSiteLocale( 'de_DE' );
		const pricedBolt = wpCli( 'post', 'list', '--post_type=product', '--meta_key=_sku', '--meta_value=QP-2', '--field=ID' );
		await page.goto( wpCli( 'post', 'url', pricedBolt ) );
		await expect( page.getByRole( 'button', { name: 'Zur Anfrage hinzufügen' } ) ).toBeVisible();
		await page.goto( '/?add-to-cart=' + pricedBolt );
		await page.goto( '/checkout/' );
		await expect( page.getByRole( 'button', { name: 'Anfrage absenden' } ) ).toBeVisible();

		setSiteLocale( oldLocale );
		wpCli( 'option', 'update', 'jps_cs_quote_place_order_label', 'Submit request' );
		await page.goto( '/?add-to-cart=' + pricedBolt );
		await page.goto( '/checkout/' );
		await expect( page.getByRole( 'button', { name: 'Submit request' } ) ).toBeVisible();
	} finally {
		wpCli( 'option', 'update', 'jps_cs_module_quote', oldQuote );
		setSiteLocale( oldLocale );
		if ( oldPlaceOrder ) {
			wpCli( 'option', 'update', 'jps_cs_quote_place_order_label', oldPlaceOrder );
		} else {
			wpCli( 'option', 'delete', 'jps_cs_quote_place_order_label' );
		}
	}
} );
