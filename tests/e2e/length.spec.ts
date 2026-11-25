import { test, expect } from '@playwright/test';
import { loginAs } from './support/auth';

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
	await page.getByLabel( /first name|vorname/i ).fill( 'Length' );
	await page.getByLabel( /last name|nachname/i ).fill( orderLabel );
	await page.getByLabel( /email address|e-?mail-adresse/i ).fill( `length-${ Date.now() }@example.com` );
	const termsCheckbox = page.getByRole( 'checkbox', { name: /Terms and Conditions|Bedingungen|AGB/i } );
	if ( await termsCheckbox.count() ) {
		await termsCheckbox.check();
	}
	await page.locator( '.wc-block-components-checkout-place-order-button:visible' ).first().click();
	await expect( page ).toHaveURL( /order-received/ );
	const orderId = page.url().match( /order-received\/(\d+)/ )?.[ 1 ];
	expect( orderId ).toBeDefined();
	await loginAs( page );
	await page.goto( '/wp-admin/admin.php?page=wc-orders' );
	const order = page.locator( '#the-list tr' ).filter( { hasText: orderLabel } );
	await expect( order ).toContainText( 'Inquiry' );
	await page.goto( `/wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }` );
	await expect( page.locator( 'body' ) ).toContainText( 'Length: 120 cm' );
} );

test( 'listing buttons keep WooCommerce button styling', async ( { page } ) => {
	await page.goto( '/?s=Length&post_type=product' );
	const listingButton = ( name: string ) => page.locator( 'li', { has: page.getByRole( 'link', { name, exact: true } ) } ).locator( '.wp-block-button__link' );
	await expect( listingButton( 'Length Bar' ) ).toHaveText( 'Choose length' );
	await expect( listingButton( 'Length Bar' ) ).toHaveAttribute( 'href', /\/product\/length-bar\/$/ );
	await expect( listingButton( 'Length Rail' ) ).toHaveText( 'Select options' );
} );
