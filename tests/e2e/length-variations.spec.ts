import { test, expect } from '@playwright/test';

test( 'variable length limits follow the selected variation', async ( { page } ) => {
	await page.goto( '/product/length-rail/' );
	const colour = page.locator( 'select[name="attribute_colour"]' );
	const length = page.locator( 'input[name="jps_cs_length"]' );
	await expect( length ).toBeVisible();
	await colour.selectOption( 'Blue' );
	await expect( length ).toHaveAttribute( 'max', '200' );
	await length.fill( '250' );
	expect( await length.evaluate( ( input: HTMLInputElement ) => input.checkValidity() ) ).toBe( false );
	await length.fill( '120' );
	expect( await length.evaluate( ( input: HTMLInputElement ) => input.checkValidity() ) ).toBe( true );
	const addToCart = page.locator( '.variations_form .single_add_to_cart_button' );
	await expect( addToCart ).toBeEnabled();
	await addToCart.click();
	await expect( page.locator( '.woocommerce-notices-wrapper' ) ).toContainText( /added to your cart|added to the inquiry/i );
	await page.goto( '/cart/' );
	await expect( page.locator( '.wp-block-woocommerce-cart' ) ).toContainText( '120 cm' );
	await page.goto( '/product/length-rail/' );
	await colour.selectOption( 'Blue' );
	await expect( length ).toHaveAttribute( 'max', '200' );
	await page.getByRole( 'link', { name: /clear/i } ).click();
	await expect( length ).toHaveAttribute( 'max', '300' );
} );
