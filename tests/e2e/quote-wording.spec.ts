import { test, expect } from './support/fixtures';
import { wpCli } from './support/wp-cli';

const CART_WORD = /\bcarts?\b/i;

test.describe( 'Quote mode leaves no cart wording', () => {
	test.beforeEach( ( { options } ) => {
		options.set( 'woocommerce_coming_soon', 'no' );
		options.set( 'jps_cs_module_quote', 'yes' );
	} );

	test( 'the variable product button and the classic cart use the inquiry wording', async ( { page } ) => {
		await page.goto( '/product/length-rail/' );
		await expect( page.locator( '.single_add_to_cart_button' ) ).toHaveText( 'Add to inquiry' );

		const productId = wpCli( 'post', 'list', '--post_type=product', '--meta_key=_sku', '--meta_value=QP-2', '--field=ID' );
		await page.goto( '/?add-to-cart=' + productId );
		await page.goto( '/classic-cart/' );
		await expect( page.locator( '.woocommerce-cart-form' ) ).not.toContainText( CART_WORD );
		await expect( page.locator( '.cart_totals' ) ).not.toContainText( CART_WORD );
		await expect( page.locator( 'button[name="update_cart"]' ) ).toHaveText( /inquiry basket/i );
	} );

	test( 'the mini-cart drawer and its labels use the inquiry wording', async ( { page } ) => {
		const productId = wpCli( 'post', 'list', '--post_type=product', '--meta_key=_sku', '--meta_value=QP-2', '--field=ID' );
		await page.goto( '/?add-to-cart=' + productId );
		await page.goto( '/product/tab-demo/' );
		await page.locator( '.wc-block-mini-cart__button' ).click();

		const drawer = page.getByRole( 'dialog' );
		await expect( drawer ).toContainText( 'Inquiry basket' );
		await expect( drawer ).not.toContainText( CART_WORD );
		await expect( drawer.getByLabel( CART_WORD ) ).toHaveCount( 0 );
	} );
} );
