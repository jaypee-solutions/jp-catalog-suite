<?php
/**
 * Inquiry status and quote-mode integration behavior.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

final class Test_Inquiries extends WP_UnitTestCase {

    public function test_price_less_simple_product_and_variation_are_purchasable(): void {
        $product = new WC_Product_Simple();
        $product->set_name( 'Price-less' );
        $product->set_status( 'publish' );
        $product->save();
        $this->assertTrue( $product->is_purchasable() );

        $parent = new WC_Product_Variable();
        $parent->set_name( 'Variable' );
        $parent->set_status( 'publish' );
        $parent->save();
        $variation = new WC_Product_Variation();
        $variation->set_parent_id( $parent->get_id() );
        $variation->set_status( 'publish' );
        $variation->save();
        $this->assertTrue( $variation->is_purchasable() );
    }

    public function test_marked_order_completes_as_inquiry_without_paid_date_or_stock_reduction(): void {
        $product = new WC_Product_Simple();
        $product->set_name( 'Managed stock' );
        $product->set_regular_price( '9.99' );
        $product->set_manage_stock( true );
        $product->set_stock_quantity( 5 );
        $product->save();
        $order = wc_create_order();
        $order->add_product( $product );
        $order->update_meta_data( '_jps_cs_inquiry', 'yes' );
        $order->set_status( 'pending' );
        $order->save();
        $created = 0;
        $listener = static function ( $created_order ) use ( &$created, $order ): void {
            if ( $created_order->get_id() === $order->get_id() ) {
                ++$created;
            }
        };
        add_action( 'jps_cs_inquiry_created', $listener );

        $order->payment_complete();

        remove_action( 'jps_cs_inquiry_created', $listener );
        $order = wc_get_order( $order->get_id() );
        $this->assertTrue( $order->has_status( 'jps-inquiry' ), 'Status: ' . $order->get_status() . '; flag: ' . $order->get_meta( '_jps_cs_inquiry' ) . '; events: ' . $created );
        $this->assertEmpty( $order->get_date_paid() );
        $this->assertSame( 5, (int) wc_get_product( $product->get_id() )->get_stock_quantity() );
        $this->assertSame( 1, $created );
    }

    public function test_inquiry_status_is_registered_and_excluded_from_analytics(): void {
        $this->assertArrayHasKey( 'wc-jps-inquiry', wc_get_order_statuses() );
        $this->assertContains( 'jps-inquiry', apply_filters( 'woocommerce_analytics_settings_default_excluded_order_statuses', [] ) );
    }
}
