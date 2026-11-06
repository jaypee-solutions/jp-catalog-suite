import { execFileSync } from 'node:child_process';
import { test, expect } from '@playwright/test';

function wpCli( ...args: string[] ): string {
	const output = execFileSync( 'npx', [ 'wp-env', 'run', 'cli', '--', 'wp', ...args ], { encoding: 'utf8', env: { ...process.env, WP_ENV_HOME: './.wp-env' } } );
	return output.split( /\r?\n/ ).map( ( line ) => line.trim() ).filter( ( line ) => line && ! line.startsWith( 'ℹ' ) && ! line.startsWith( '✔' ) && ! line.startsWith( 'Deprecated:' ) ).join( '\n' ).trim();
}

test( 'length pricing multiplies by the entered length and displays the unit', async ( { page } ) => {
	const productId = wpCli( 'post', 'list', '--post_type=product', '--meta_key=_sku', '--meta_value=LBP-1', '--field=ID' ).split( /\r?\n/ ).filter( Boolean ).pop();
	expect( productId ).toBeTruthy();
	const state = wpCli( 'eval', `echo base64_encode( wp_json_encode( [ 'pricing' => get_option( 'jps_cs_length_pricing_enabled', '__missing__' ), 'quote' => get_option( 'jps_cs_module_quote', '__missing__' ) ] ) );` );
	try {
		wpCli( 'option', 'update', 'jps_cs_length_pricing_enabled', 'yes' );
		wpCli( 'option', 'update', 'jps_cs_module_quote', 'no' );
		await page.goto( wpCli( 'post', 'url', productId! ) );
		await expect( page.locator( 'main' ) ).toContainText( /\/ cm/ );
		const length = page.locator( 'input[name="jps_cs_length"]' );
		await length.fill( '120' );
		await page.getByRole( 'button', { name: 'Add to cart', exact: true } ).click();
		await page.goto( '/cart/' );
		const cart = page.locator( '.wp-block-woocommerce-cart' );
		await expect( cart ).toContainText( '120 cm' );
		await expect( cart ).toContainText( '240' );
	} finally {
		wpCli( 'eval', `$state = json_decode( base64_decode( '${ state }' ), true ); foreach ( [ 'jps_cs_length_pricing_enabled' => 'pricing', 'jps_cs_module_quote' => 'quote' ] as $option => $key ) { if ( '__missing__' === $state[ $key ] ) { delete_option( $option ); } else { update_option( $option, $state[ $key ] ); } }` );
	}
} );
