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
    public function test_disabled_variation_stays_hidden_and_unpurchasable_in_quote_mode(): void {
        $parent = new WC_Product_Variable();
        $parent->set_name( 'Published variable' );
        $parent->set_status( 'publish' );
        $parent->save();
        $variation = new WC_Product_Variation();
        $variation->set_parent_id( $parent->get_id() );
        $variation->set_status( 'draft' );
        $variation->save();
        $this->assertFalse( apply_filters( 'woocommerce_variation_is_visible', false, $variation->get_id(), $parent->get_id(), $variation ) );
        $this->assertFalse( apply_filters( 'woocommerce_variation_is_purchasable', false, $variation ) );
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
    public function test_product_prices_and_inquiry_totals_are_hidden(): void {
        $product = new WC_Product_Simple();
        $product->set_name( 'Priced inquiry product' );
        $product->set_regular_price( '12.34' );
        $product->save();
        $this->assertSame( '', $product->get_price_html() );
        $this->assertArrayNotHasKey( 'offers', apply_filters( 'woocommerce_structured_data_product', [ 'offers' => [ 'price' => '12.34' ] ], $product ) );

        $order = wc_create_order();
        $order->add_product( $product );
        $order->update_meta_data( '_jps_cs_inquiry', 'yes' );
        $order->save();
        $this->assertSame( [], apply_filters( 'woocommerce_get_order_item_totals', [ 'total' => [ 'value' => '$12.34' ] ], $order, '' ) );
        $this->assertSame( '', $order->get_formatted_order_total() );
        $item = current( $order->get_items() );
        $this->assertSame( '', apply_filters( 'woocommerce_order_formatted_line_subtotal', '$12.34', $item, $order ) );
        add_filter( 'jps_cs_quote_mode_active', '__return_false' );
        $this->assertSame( [], apply_filters( 'woocommerce_get_order_item_totals', [ 'total' => [ 'value' => '$12.34' ] ], $order, '' ) );
        $this->assertSame( '', $order->get_formatted_order_total() );
        remove_filter( 'jps_cs_quote_mode_active', '__return_false' );
    }

    public function test_account_orders_are_labeled_inquiries_without_total_column(): void {
        $items = apply_filters( 'woocommerce_account_menu_items', [ 'orders' => 'Orders' ] );
        $this->assertSame( 'Inquiries', $items['orders'] );
        $columns = apply_filters( 'woocommerce_account_orders_columns', [ 'order-number' => 'Order', 'order-total' => 'Total' ] );
        $this->assertArrayNotHasKey( 'order-total', $columns );
    }
    public function test_account_labels_and_total_column_are_unchanged_when_quote_mode_is_inactive(): void {
        add_filter( 'jps_cs_quote_mode_active', '__return_false' );
        $items = apply_filters( 'woocommerce_account_menu_items', [ 'orders' => 'Orders' ] );
        $columns = apply_filters( 'woocommerce_account_orders_columns', [ 'order-number' => 'Order', 'order-total' => 'Total' ] );
        $this->assertSame( 'Orders', $items['orders'] );
        $this->assertArrayHasKey( 'order-total', $columns );
        remove_filter( 'jps_cs_quote_mode_active', '__return_false' );
    }

    public function test_thankyou_filter_accepts_no_order(): void {
        foreach ( [ false, null ] as $order ) {
            $this->assertSame( 'Original confirmation', apply_filters( 'woocommerce_thankyou_order_received_text', 'Original confirmation', $order ) );
        }
    }

    public function test_quote_labels_use_translated_defaults_and_saved_overrides(): void {
        update_option( 'jps_cs_quote_place_order_label', '' );
        $this->assertSame( 'Send inquiry', apply_filters( 'woocommerce_order_button_text', 'Place order' ) );

        update_option( 'jps_cs_quote_place_order_label', 'Submit request' );
        $this->assertSame( 'Submit request', apply_filters( 'woocommerce_order_button_text', 'Place order' ) );
    }

    public function test_inquiry_thankyou_text_replaces_the_generic_confirmation(): void {
        $order = wc_create_order();
        $order->update_meta_data( '_jps_cs_inquiry', 'yes' );
        $order->save();

        $this->assertSame(
            'Thank you. Your inquiry has been received.',
            apply_filters( 'woocommerce_thankyou_order_received_text', 'Original confirmation', $order )
        );
    }
}
