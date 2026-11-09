import { test, expect, type Page } from './support/fixtures';
import { wpCli } from './support/wp-cli';

const ADMIN_USER = process.env.WP_ADMIN_USER ?? 'admin';
const ADMIN_PASS = process.env.WP_ADMIN_PASS ?? 'password';

let orderId = '';
let editUrl = '';
let viewUrl = '';

async function login( page: Page, user: string, password: string ): Promise<void> {
	await page.context().clearCookies();
	await page.request.get( '/wp-login.php' );
	const response = await page.request.post( '/wp-login.php', { form: { log: user, pwd: password, 'wp-submit': 'Log In', redirect_to: '/', testcookie: '1' }, maxRedirects: 0 } );
	expect( response.status() ).toBe( 302 );
}

function inDays( days: number ): string {
	const date = new Date();
	date.setDate( date.getDate() + days );
	return date.toISOString().slice( 0, 10 );
}

test.beforeAll( () => {
	const created = wpCli(
		'eval',
		`$customer = get_user_by( 'login', 'customer1' );
		$order = wc_create_order( [ 'customer_id' => $customer->ID ] );
		$order->add_product( wc_get_product( wc_get_product_id_by_sku( 'QP-2' ) ), 2 );
		$order->set_billing_email( $customer->user_email );
		$order->update_meta_data( '_jps_cs_inquiry', 'yes' );
		$order->calculate_totals( false );
		$order->set_status( 'jps-inquiry' );
		$order->save();
		echo wp_json_encode( [ $order->get_id(), $order->get_edit_order_url(), wc_get_endpoint_url( 'view-order', $order->get_id(), wc_get_page_permalink( 'myaccount' ) ) ] );`
	);
	[ orderId, editUrl, viewUrl ] = ( JSON.parse( created ) as [ number, string, string ] ).map( String );
} );

test.afterAll( () => {
	if ( orderId ) {
		wpCli( 'eval', `wc_get_order( ${ orderId } )->delete( true );` );
	}
} );

test( 'shop manager sends a quote and the customer accepts it in My Account', async ( { page } ) => {
	await login( page, ADMIN_USER, ADMIN_PASS );
	await page.goto( editUrl );
	await expect( page.locator( 'a.edit-order-item' ).first() ).toBeVisible();

	await page.locator( '#jps_cs_quote_valid_until' ).fill( inDays( 7 ) );
	await page.locator( '#jps_cs_quote_message' ).fill( 'Delivery in 2 weeks.' );
	await page.locator( 'select[name="wc_order_action"]' ).selectOption( 'jps_cs_send_quote' );
	await page.locator( 'button.save_order' ).click();
	await expect( page.locator( '#order_status' ) ).toHaveValue( 'wc-jps-quote' );

	await login( page, 'customer1', 'password' );
	await page.goto( viewUrl );
	const details = page.locator( '.woocommerce-order-details' );
	await expect( page.locator( '.jps-cs-quote' ) ).toContainText( 'Delivery in 2 weeks.' );
	await expect( details ).toContainText( '19.98' );

	await page.getByRole( 'button', { name: 'Accept quote' } ).click();
	await expect( page.getByRole( 'alert' ) ).toContainText( 'You have accepted the quote' );
	expect( wpCli( 'eval', `echo wc_get_order( ${ orderId } )->get_status();` ) ).toBe( 'on-hold' );
} );
