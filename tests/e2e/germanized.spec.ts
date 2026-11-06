import { execFileSync } from 'node:child_process';
import { test, expect } from '@playwright/test';

function wpCli( ...args: string[] ): string {
	const output = execFileSync( 'npx', [ 'wp-env', 'run', 'cli', '--', 'wp', ...args ], { encoding: 'utf8', env: { ...process.env, WP_ENV_HOME: './.wp-env' } } );
	const lines = output.split( /\r?\n/ ).map( ( line ) => line.trim() ).filter( ( line ) => line && ! line.startsWith( 'ℹ' ) && ! line.startsWith( '✔' ) && ! line.startsWith( 'Deprecated:' ) );
	return lines.join( '\n' );
}

test( 'Germanized keeps quote-mode product and checkout views price- and checkbox-clean', async ( { page } ) => {
	const activePlugins = wpCli( 'plugin', 'list', '--status=active', '--field=name' ).split( /\r?\n/ );
	const hadGermanized = activePlugins.includes( 'woocommerce-germanized.latest-stable' );
	const oldQuote = wpCli( 'option', 'get', 'jps_cs_module_quote' );
	const oldComingSoon = wpCli( 'option', 'get', 'woocommerce_coming_soon' );
	const oldGuestCheckout = wpCli( 'option', 'get', 'woocommerce_enable_guest_checkout' );
	const oldSignupAtCheckout = wpCli( 'option', 'get', 'woocommerce_enable_signup_and_login_from_checkout' );
	const oldCheckboxSettings = wpCli( 'eval', 'echo base64_encode( wp_json_encode( get_option( "woocommerce_gzd_legal_checkboxes_settings", null ) ) );' );
	try {
		wpCli( 'plugin', 'activate', 'woocommerce-germanized.latest-stable' );
		wpCli( 'option', 'update', 'woocommerce_coming_soon', 'no' );
		wpCli( 'option', 'update', 'jps_cs_module_quote', 'yes' );
		wpCli( 'option', 'update', 'woocommerce_enable_guest_checkout', 'no' );
		wpCli( 'option', 'update', 'woocommerce_enable_signup_and_login_from_checkout', 'yes' );
		wpCli( 'eval', '$options = get_option( "woocommerce_gzd_legal_checkboxes_settings", [] ); $options["privacy"]["is_enabled"] = "yes"; $options["privacy"]["locations"] = [ "checkout" ]; update_option( "woocommerce_gzd_legal_checkboxes_settings", $options );' );
		const productId = wpCli( 'post', 'list', '--post_type=product', '--meta_key=_sku', '--meta_value=QP-2', '--field=ID' ).split( /\r?\n/ ).filter( Boolean ).pop();
		expect( productId ).toBeTruthy();
		await page.goto( wpCli( 'post', 'url', productId! ) );
		await expect( page.locator( 'body' ) ).not.toContainText( /inkl\.\s*MwSt|incl\.\s*VAT/i );
		await expect( page.locator( 'body' ) ).not.toContainText( /zzgl\.\s*Versand|shipping costs/i );

		await page.goto( '/?add-to-cart=' + productId );
		await page.goto( '/checkout/' );
		await page.getByLabel( 'Country/Region' ).selectOption( 'DE' );
		await expect( page.getByRole( 'button', { name: 'Send inquiry' } ) ).toBeVisible();
		await expect( page.getByRole( 'checkbox', { name: /terms and conditions|cancellation policy/i } ) ).toHaveCount( 0 );
		await expect( page.getByRole( 'checkbox', { name: /privacy/i } ) ).toBeVisible();
	} finally {
		wpCli( 'option', 'update', 'jps_cs_module_quote', oldQuote );
		wpCli( 'option', 'update', 'woocommerce_coming_soon', oldComingSoon );
		wpCli( 'option', 'update', 'woocommerce_enable_guest_checkout', oldGuestCheckout );
		wpCli( 'option', 'update', 'woocommerce_enable_signup_and_login_from_checkout', oldSignupAtCheckout );
		wpCli( 'eval', `if ( "${ oldCheckboxSettings }" === base64_encode( wp_json_encode( null ) ) ) { delete_option( "woocommerce_gzd_legal_checkboxes_settings" ); } else { update_option( "woocommerce_gzd_legal_checkboxes_settings", json_decode( base64_decode( "${ oldCheckboxSettings }" ), true ) ); }` );
		if ( ! hadGermanized ) {
			wpCli( 'plugin', 'deactivate', 'woocommerce-germanized.latest-stable' );
		}
	}
} );
