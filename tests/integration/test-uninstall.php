<?php
/**
 * Integration tests for uninstall behavior.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

final class Test_Uninstall extends WP_UnitTestCase {

    public function test_uninstall_removes_options_without_changing_orders(): void {
        global $wpdb;
        $order = wc_create_order();
        $order->set_status( 'processing' );
        $order->save();
        update_option( 'jps_cs_module_quote', 'yes' );
        update_option( 'jp_catalog_suite_github_token', 'secret' );

        define( 'WP_UNINSTALL_PLUGIN', true );
        require dirname( __DIR__, 2 ) . '/uninstall.php';

        $names = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'jps\\_cs\\_%'" );
        $this->assertSame( [], $names );
        $this->assertFalse( get_option( 'jp_catalog_suite_github_token', false ) );
        $this->assertSame( 'processing', wc_get_order( $order->get_id() )->get_status() );
    }
}
