<?php
/**
 * Quote sending, price visibility and customer acceptance integration behavior.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

final class Test_Quotes_Redirect extends RuntimeException {}

final class Test_Quotes extends WP_UnitTestCase {

    /** @var array<int, array<string, mixed>> */
    private array $sent_mail = [];

    public function set_up(): void {
        parent::set_up();
        WC()->initialize_session();
        $this->sent_mail = [];
        add_filter(
            'pre_wp_mail',
            function ( $pre_wp_mail, array $attributes ) {
                $attributes['to'] = (array) $attributes['to'];
                $this->sent_mail[] = $attributes;
                return true;
            },
            10,
            2
        );
        add_filter(
            'wp_redirect',
            static function ( $location ) {
                throw new Test_Quotes_Redirect( (string) $location );
            }
        );
        $this->reload_mailer();
    }

    public function tear_down(): void {
        unset( $_POST['jps_cs_accept_quote'], $_POST['_jps_cs_quote_nonce'] );
        parent::tear_down();
    }

    public function test_quote_status_is_registered_and_excluded_from_reports(): void {
        $this->assertArrayHasKey( 'wc-jps-quote', wc_get_order_statuses() );
        delete_option( 'woocommerce_excluded_report_order_statuses' );
        $this->assertContains( 'jps-quote', get_option( 'woocommerce_excluded_report_order_statuses', [ 'pending', 'failed', 'cancelled' ] ) );
    }

    public function test_inquiry_is_editable_until_it_leaves_the_quote_workflow(): void {
        $order = $this->create_inquiry();
        $this->assertTrue( $order->is_editable() );
        $order->set_status( 'jps-quote' );
        $this->assertTrue( $order->is_editable() );
        $order->set_status( 'processing' );
        $this->assertFalse( $order->is_editable() );
    }

    public function test_send_quote_action_moves_inquiry_to_quote_and_mails_priced_html_and_plain_email(): void {
        foreach ( [ 'html', 'plain' ] as $email_type ) {
            $this->sent_mail = [];
            update_option( 'woocommerce_jps_cs_quote_settings', [ 'email_type' => $email_type ] );
            $this->reload_mailer();
            $customer = self::factory()->user->create();
            $order    = $this->create_inquiry( $customer, gmdate( 'Y-m-d', strtotime( '+7 days' ) ) );

            do_action( 'woocommerce_order_action_jps_cs_send_quote', $order );

            $order = wc_get_order( $order->get_id() );
            $this->assertSame( 'jps-quote', $order->get_status() );
            $this->assertNotSame( '', (string) $order->get_meta( '_jps_cs_quote_sent' ) );
            $this->assertCount( 1, $this->sent_mail );
            $mail = $this->sent_mail[0];
            $this->assertSame( [ 'buyer@example.test' ], $mail['to'] );
            $this->assertStringContainsString( 'Your quote', $mail['subject'] );
            $this->assertStringContainsString( '12.34', $mail['message'] );
            $this->assertStringContainsString( 'Delivery in 2 weeks.', $mail['message'] );
            $this->assertStringContainsString( wp_date( wc_date_format(), strtotime( '+7 days' ) ), $mail['message'] );
            $this->assertStringContainsString( 'html' === $email_type ? esc_url( $order->get_view_order_url() ) : $order->get_view_order_url(), $mail['message'] );
            if ( 'plain' === $email_type ) {
                $this->assertStringNotContainsString( '<html', $mail['message'] );
            }
        }
    }

    public function test_disabled_quote_email_keeps_inquiry_status_and_sends_nothing(): void {
        update_option( 'woocommerce_jps_cs_quote_settings', [ 'enabled' => 'no' ] );
        $this->reload_mailer();
        $order = $this->create_inquiry();

        do_action( 'woocommerce_order_action_jps_cs_send_quote', $order );

        $order = wc_get_order( $order->get_id() );
        $this->assertSame( 'jps-inquiry', $order->get_status() );
        $this->assertSame( '', (string) $order->get_meta( '_jps_cs_quote_sent' ) );
        $this->assertCount( 0, $this->sent_mail );
    }

    public function test_frontend_prices_appear_only_after_the_quote_was_sent(): void {
        $order = $this->create_inquiry();
        $this->assertSame( '', wc_get_order( $order->get_id() )->get_formatted_order_total() );

        do_action( 'woocommerce_order_action_jps_cs_send_quote', $order );

        $this->assertStringContainsString( '12.34', wc_get_order( $order->get_id() )->get_formatted_order_total() );
    }

    public function test_customer_accepts_quote_once_and_admin_is_notified(): void {
        $customer = self::factory()->user->create();
        $order    = $this->quoted_order( $customer );
        $accepted = 0;
        add_action(
            'jps_cs_quote_accepted',
            static function () use ( &$accepted ): void {
                ++$accepted;
            }
        );

        $this->submit_acceptance( $customer, $order, $location );
        $this->assertSame( $order->get_view_order_url(), $location );
        $this->assertSame( 'on-hold', wc_get_order( $order->get_id() )->get_status() );
        $this->assertCount( 1, $this->sent_mail );
        $this->assertContains( get_option( 'admin_email' ), $this->sent_mail[0]['to'] );
        $this->assertStringContainsString( 'accepted', $this->sent_mail[0]['subject'] );
        $this->assertSame( 1, $accepted );

        $this->submit_acceptance( $customer, $order, $location );
        $this->assertSame( 'on-hold', wc_get_order( $order->get_id() )->get_status() );
        $this->assertCount( 1, $this->sent_mail );
        $this->assertSame( 1, $accepted );
    }

    public function test_expired_quote_and_foreign_order_cannot_be_accepted(): void {
        $customer = self::factory()->user->create();
        $expired  = $this->quoted_order( $customer, gmdate( 'Y-m-d', strtotime( '-2 days' ) ) );
        $this->submit_acceptance( $customer, $expired, $location );
        $this->assertSame( 'jps-quote', wc_get_order( $expired->get_id() )->get_status() );

        $owned   = $this->quoted_order( $customer );
        $stranger = self::factory()->user->create();
        $this->submit_acceptance( $stranger, $owned, $location );
        $this->assertSame( 'jps-quote', wc_get_order( $owned->get_id() )->get_status() );

        $this->assertCount( 0, $this->sent_mail_to_admin() );
    }

    public function test_priced_view_keeps_taxes_enabled_between_order_table_hooks(): void {
        update_option( 'woocommerce_calc_taxes', 'yes' );
        $quoted = $this->quoted_order( self::factory()->user->create() );
        $this->assertFalse( wc_tax_enabled() );

        do_action( 'woocommerce_order_details_before_order_table', $quoted );
        $this->assertTrue( wc_tax_enabled() );

        do_action( 'woocommerce_order_details_after_order_table', $quoted );
        $this->assertFalse( wc_tax_enabled() );
    }

    private function reload_mailer(): void {
        $mailer         = WC()->mailer();
        $mailer->emails = [];
        $mailer->init();
    }

    private function create_inquiry( int $customer_id = 0, string $valid_until = '' ): WC_Order {
        $product = new WC_Product_Simple();
        $product->set_name( 'Quoted product' );
        $product->set_regular_price( '12.34' );
        $product->save();
        $order = wc_create_order();
        $order->add_product( $product, 1 );
        $order->set_customer_id( $customer_id );
        $order->set_billing_email( 'buyer@example.test' );
        $order->update_meta_data( '_jps_cs_inquiry', 'yes' );
        $order->update_meta_data( '_jps_cs_quote_message', 'Delivery in 2 weeks.' );
        if ( '' !== $valid_until ) {
            $order->update_meta_data( '_jps_cs_quote_valid_until', $valid_until );
        }
        $order->calculate_totals();
        $order->set_status( 'jps-inquiry' );
        $order->save();
        $this->sent_mail = [];
        return $order;
    }

    private function quoted_order( int $customer_id, string $valid_until = '' ): WC_Order {
        $order = $this->create_inquiry( $customer_id, $valid_until );
        do_action( 'woocommerce_order_action_jps_cs_send_quote', $order );
        $this->sent_mail = [];
        return wc_get_order( $order->get_id() );
    }

    private function submit_acceptance( int $user_id, WC_Order $order, ?string &$location ): void {
        wp_set_current_user( $user_id );
        $_POST['jps_cs_accept_quote'] = (string) $order->get_id();
        $_POST['_jps_cs_quote_nonce'] = wp_create_nonce( 'jps_cs_accept_quote_' . $order->get_id() );
        $location                     = null;
        try {
            do_action( 'template_redirect' );
        } catch ( Test_Quotes_Redirect $redirect ) {
            $location = $redirect->getMessage();
        }
        $this->assertNotNull( $location, 'Acceptance request must redirect.' );
    }

    /** @return array<int, array<string, mixed>> */
    private function sent_mail_to_admin(): array {
        return array_filter(
            $this->sent_mail,
            static fn ( array $mail ): bool => in_array( get_option( 'admin_email' ), $mail['to'], true )
        );
    }
}
