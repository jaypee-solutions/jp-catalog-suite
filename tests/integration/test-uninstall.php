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
        $product_id = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        update_post_meta( $product_id, '_jps_cs_hidden_tabs', [ 123 ] );
        update_option( 'jps_cs_uninstall_delete_data', 'yes' );
        update_option( 'jps_cs_module_quote', 'yes' );
        update_option( 'jp_catalog_suite_github_token', 'secret' );
        update_option( 'woocommerce_jps_cs_new_inquiry_settings', [ 'enabled' => 'yes' ] );
        update_option( 'woocommerce_jps_cs_inquiry_confirmation_settings', [ 'enabled' => 'yes' ] );
        define( 'WP_UNINSTALL_PLUGIN', true );
        require dirname( __DIR__, 2 ) . '/uninstall.php';

        $names = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'jps\\_cs\\_%'" );
        $this->assertSame( [], $names );
        $this->assertFalse( get_option( 'jp_catalog_suite_github_token', false ) );
        $this->assertFalse( get_option( 'woocommerce_jps_cs_new_inquiry_settings', false ) );
        $this->assertFalse( get_option( 'woocommerce_jps_cs_inquiry_confirmation_settings', false ) );
        $this->assertSame( 'processing', wc_get_order( $order->get_id() )->get_status() );
        $this->assertSame( '', get_post_meta( $product_id, '_jps_cs_hidden_tabs', true ) );
    }
}
