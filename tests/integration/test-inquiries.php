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

        delete_option( 'woocommerce_excluded_report_order_statuses' );
        $this->assertContains( 'jps-inquiry', get_option( 'woocommerce_excluded_report_order_statuses', [ 'pending', 'failed', 'cancelled' ] ) );

        update_option( 'woocommerce_excluded_report_order_statuses', [ 'pending' ] );
        $this->assertContains( 'jps-inquiry', get_option( 'woocommerce_excluded_report_order_statuses', [] ) );
        $this->assertContains( 'pending', get_option( 'woocommerce_excluded_report_order_statuses', [] ) );
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

    private function confirmation_order( bool $inquiry ): WC_Order {
        $order = wc_create_order();
        if ( $inquiry ) {
            $order->update_meta_data( '_jps_cs_inquiry', 'yes' );
        }
        $order->save();
        return $order;
    }

    /** Makes WooCommerce treat the request as the order-received endpoint for $order. */
    private function open_confirmation_page( ?WC_Order $order ): void {
        global $wp;
        unset( $wp->query_vars['order-received'] );
        if ( $order ) {
            $wp->query_vars['order-received'] = (string) $order->get_id();
        }
    }

    public function tear_down(): void {
        $this->open_confirmation_page( null );
        parent::tear_down();
    }

    public function test_inquiry_confirmation_heading_says_inquiry_received(): void {
        $inquiry = $this->confirmation_order( true );
        $purchase = $this->confirmation_order( false );

        $this->assertSame( 'Inquiry received', apply_filters( 'woocommerce_thankyou_order_received_title', 'Order received', $inquiry ) );
        $this->assertSame( 'Order received', apply_filters( 'woocommerce_thankyou_order_received_title', 'Order received', $purchase ) );
        foreach ( [ false, null ] as $no_order ) {
            $this->assertSame( 'Order received', apply_filters( 'woocommerce_thankyou_order_received_title', 'Order received', $no_order ) );
        }

        $this->open_confirmation_page( $inquiry );
        $this->assertSame( 'Inquiry received', apply_filters( 'woocommerce_endpoint_order-received_title', 'Order received', 'order-received', '' ) );
        $this->open_confirmation_page( $purchase );
        $this->assertSame( 'Order received', apply_filters( 'woocommerce_endpoint_order-received_title', 'Order received', 'order-received', '' ) );
    }

    public function test_inquiry_confirmation_rows_use_inquiry_wording_only_on_an_inquiry_page(): void {
        $inquiry = $this->confirmation_order( true );
        $purchase = $this->confirmation_order( false );
        $original = [ 'Order #:', 'Order number:', 'Order details' ];

        // phpcs:disable WordPress.WP.I18n.TextDomainMismatch, WordPress.WP.I18n.NonSingularStringLiteralText -- The test reads WooCommerce's own strings.
        $this->open_confirmation_page( $inquiry );
        $this->assertSame( 'Inquiry #:', __( 'Order #:', 'woocommerce' ) );
        $this->assertSame( 'Inquiry number:', __( 'Order number:', 'woocommerce' ) );
        $this->assertSame( 'Inquiry details', __( 'Order details', 'woocommerce' ) );
        $this->assertSame( 'Date:', __( 'Date:', 'woocommerce' ), 'Other WooCommerce strings stay untouched.' );

        foreach ( [ $purchase, null ] as $page ) {
            $this->open_confirmation_page( $page );
            foreach ( $original as $text ) {
                $this->assertSame( $text, __( $text, 'woocommerce' ) );
            }
        }
        // phpcs:enable WordPress.WP.I18n.TextDomainMismatch, WordPress.WP.I18n.NonSingularStringLiteralText
    }

    public function test_order_details_heading_of_the_blocks_confirmation_page_says_inquiry_details(): void {
        // WooCommerce bakes this heading into a block pattern at registration, before any order is known.
        $heading  = '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Order details</h2><!-- /wp:heading -->';
        $inquiry  = $this->confirmation_order( true );
        $purchase = $this->confirmation_order( false );

        $this->open_confirmation_page( $inquiry );
        $this->assertStringContainsString( '>Inquiry details<', do_blocks( $heading ) );
        $this->assertStringContainsString( '>Shipping<', do_blocks( '<!-- wp:heading --><h2 class="wp-block-heading">Shipping</h2><!-- /wp:heading -->' ) );

        $this->open_confirmation_page( $purchase );
        $this->assertStringContainsString( '>Order details<', do_blocks( $heading ) );
    }
}
