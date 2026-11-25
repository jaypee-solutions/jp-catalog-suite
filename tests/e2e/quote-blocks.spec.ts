import { basename } from 'node:path';
import { test, expect } from '@playwright/test';
import { loginAs } from './support/auth';
import { wpCli } from './support/wp-cli';

function compileGermanCatalog(): void {
	wpCli( 'i18n', 'make-mo', `/var/www/html/wp-content/plugins/${ basename( process.cwd() ) }/languages` );
}

function setSiteLocale( locale: string ): void {
	wpCli( 'eval', `add_filter( 'sanitize_option_WPLANG', static fn() => '${ locale }', PHP_INT_MAX ); update_option( 'WPLANG', '${ locale }' );` );
}

test( 'guest sends a Blocks inquiry which stays listed when Quote mode is off', async ( { page } ) => {
	const oldQuote = wpCli( 'option', 'get', 'jps_cs_module_quote' );
	const oldComingSoon = wpCli( 'option', 'get', 'woocommerce_coming_soon' );
	const oldPhone = wpCli( 'eval', "echo get_option( 'jps_cs_quote_field_phone', 'optional' );" );
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
		await page.goto( '/checkout/' );
		await expect( page.getByLabel( /phone|telefon/i ) ).not.toHaveAttribute( 'required', '' );
		wpCli( 'option', 'update', 'jps_cs_quote_field_phone', 'required' );
		await page.reload();
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
		await expect( page.locator( '.wc-block-components-checkout-place-order-button:visible' ).first() ).toBeVisible();
		await page.locator( '.wc-block-components-checkout-place-order-button:visible' ).first().click();
		await expect( page ).toHaveURL( /order-received/ );
		await expect( page.locator( 'body' ) ).toContainText( /Inquiry received|Anfrage eingegangen/ );
		await expect( page.locator( 'body' ) ).not.toContainText( /Order received|Bestellung eingegangen|Order #|Bestell-Nr/ );

        await expect( page.locator( 'body' ) ).not.toContainText( '€' );

		await loginAs( page );
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
		wpCli( 'option', 'update', 'jps_cs_module_quote', oldQuote );
		wpCli( 'option', 'update', 'jps_cs_quote_field_phone', oldPhone );
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
test( 'administrator can view Blocks cart and checkout with a price-free inquiry basket', async ( { page } ) => {
	const oldQuote = wpCli( 'option', 'get', 'jps_cs_module_quote' );
	const oldComingSoon = wpCli( 'option', 'get', 'woocommerce_coming_soon' );
	try {
		wpCli( 'option', 'update', 'woocommerce_coming_soon', 'no' );
		wpCli( 'option', 'update', 'jps_cs_module_quote', 'yes' );
		await loginAs( page );
		const productId = wpCli( 'post', 'list', '--post_type=product', '--meta_key=_sku', '--meta_value=QP-2', '--field=ID' );
		await page.goto( '/?add-to-cart=' + productId );
		await page.goto( '/cart/' );
		await expect( page.locator( '.wc-block-components-error' ) ).toHaveCount( 0 );
		await expect( page.locator( 'body' ) ).not.toContainText( '€' );
		await page.goto( '/checkout/' );
		await expect( page.locator( '.wc-block-components-error' ) ).toHaveCount( 0 );
		await expect( page.locator( 'body' ) ).not.toContainText( '€' );
	} finally {
		wpCli( 'option', 'update', 'jps_cs_module_quote', oldQuote );
		wpCli( 'option', 'update', 'woocommerce_coming_soon', oldComingSoon );
	}
} );
test( 'mini-cart labels use Quote mode options on ordinary product pages', async ( { page } ) => {
	const oldQuote = wpCli( 'option', 'get', 'jps_cs_module_quote' );
	const oldComingSoon = wpCli( 'option', 'get', 'woocommerce_coming_soon' );
	const oldProceed = wpCli( 'eval', "echo get_option( 'jps_cs_quote_proceed_label', '__missing__' );" );
	const customLabel = `Proceed-${ Date.now() }`;
	try {
		wpCli( 'option', 'update', 'woocommerce_coming_soon', 'no' );
		wpCli( 'option', 'update', 'jps_cs_module_quote', 'yes' );
		wpCli( 'option', 'update', 'jps_cs_quote_proceed_label', customLabel );
		const productId = wpCli( 'post', 'list', '--post_type=product', '--meta_key=_sku', '--meta_value=QP-2', '--field=ID' );
		await page.goto( '/?add-to-cart=' + productId );
		await page.goto( '/product/tab-demo/' );
		await page.locator( '.wc-block-mini-cart__button' ).click();
		await expect( page.locator( 'body' ) ).toContainText( customLabel );
	} finally {
		wpCli( 'option', 'update', 'jps_cs_module_quote', oldQuote );
		wpCli( 'option', 'update', 'woocommerce_coming_soon', oldComingSoon );
		if ( '__missing__' === oldProceed ) {
			wpCli( 'option', 'delete', 'jps_cs_quote_proceed_label' );
		} else {
			wpCli( 'option', 'update', 'jps_cs_quote_proceed_label', oldProceed );
		}
	}
} );

test( 'My Account keeps standard order labels when Quote mode is disabled', async ( { page } ) => {
	const oldQuote = wpCli( 'option', 'get', 'jps_cs_module_quote' );
	try {
		wpCli( 'option', 'update', 'jps_cs_module_quote', 'no' );
		const customerId = wpCli( 'user', 'get', 'customer1', '--field=ID' );
		const productId = wpCli( 'post', 'list', '--post_type=product', '--meta_key=_sku', '--meta_value=QP-2', '--field=ID' );
		wpCli( 'eval', `$order = wc_create_order( [ 'customer_id' => ${ customerId } ] ); $order->add_product( wc_get_product( ${ productId } ), 1 ); $order->save(); $order->update_status( 'processing' );` );
		await loginAs( page, 'customer1', 'password' );
		await page.goto( '/my-account/' );
		await expect( page.getByRole( 'link', { name: 'Orders', exact: true } ) ).toBeVisible();
		await expect( page.locator( 'body' ) ).not.toContainText( 'Inquiries' );
	} finally {
		wpCli( 'option', 'update', 'jps_cs_module_quote', oldQuote );
	}
} );
