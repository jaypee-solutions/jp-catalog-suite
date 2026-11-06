<?php
/**
 * Inquiry email delivery and content integration behavior.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

final class Test_Inquiry_Emails extends WP_UnitTestCase {

    /** @var array<int, array<string, mixed>> */
    private array $sent_mail = [];

    public function test_completed_inquiry_sends_price_free_emails_with_reply_to_and_item_details(): void {
        $this->capture_mail();
        $this->assertTrue( WC()->mailer()->get_emails()['JPS_CS_Email_New_Inquiry']->is_enabled() );
        $product = new WC_Product_Simple();
        $product->set_name( 'Price-free test product' );
        $product->set_sku( 'MAIL-1' );
        $product->set_regular_price( '12.34' );
        $product->save();

        $order = wc_create_order();
        $order->add_product( $product, 2 );
        $order->set_billing_email( 'buyer@example.test' );
        $order->set_billing_first_name( 'Buyer' );
        $order->set_billing_last_name( 'Person' );
        $order->set_customer_note( 'Please send details.' );
        $order->update_meta_data( '_jps_cs_inquiry', 'yes' );
        $order->set_status( 'pending' );
        $order->save();
        $item = current( $order->get_items() );
        $item->add_meta_data( 'Length', '120 cm' );
        $item->save();

        $order->payment_complete();

        $this->assertCount( 2, $this->sent_mail );
        $this->assertContains( get_option( 'admin_email' ), $this->sent_mail[0]['to'] );
        $this->assertStringContainsString( 'New inquiry', $this->sent_mail[0]['subject'] );
        $this->assertContains( 'buyer@example.test', $this->sent_mail[1]['to'] );
        $this->assertStringContainsString( 'Your inquiry at', $this->sent_mail[1]['subject'] );
        $this->assertStringContainsString( 'Thank you for your inquiry', $this->sent_mail[1]['message'] );
        foreach ( $this->sent_mail as $mail ) {
            $this->assertStringNotContainsString( '12.34', $mail['message'] );
            $this->assertStringNotContainsString( '12,34', $mail['message'] );
            $this->assertStringContainsString( 'MAIL-1', $mail['message'] );
            $this->assertStringContainsString( '120 cm', $mail['message'] );
            $this->assertStringContainsString( 'Buyer', $mail['message'] );
            $this->assertStringContainsString( 'Please send details.', $mail['message'] );
        }
        $this->assertStringContainsString( 'Reply-To: buyer@example.test', implode( "\n", $this->sent_mail[0]['headers'] ) );
    }


    public function test_plain_email_variants_include_price_free_item_details(): void {
        $this->capture_mail();
        update_option( 'woocommerce_jps_cs_new_inquiry_settings', [ 'enabled' => 'yes', 'email_type' => 'plain' ] );
        update_option( 'woocommerce_jps_cs_inquiry_confirmation_settings', [ 'enabled' => 'yes', 'email_type' => 'plain' ] );
        $mailer = WC()->mailer();
        $mailer->emails = [];
        $mailer->init();

        $product = new WC_Product_Simple();
        $product->set_name( 'Plain email product' );
        $product->set_sku( 'MAIL-PLAIN' );
        $product->set_regular_price( '12.34' );
        $product->save();
        $order = wc_create_order();
        $order->add_product( $product );
        $order->set_billing_email( 'plain@example.test' );
        $order->set_billing_first_name( 'Plain Buyer' );
        $order->set_customer_note( 'Plain message.' );
        $order->update_meta_data( '_jps_cs_inquiry', 'yes' );
        $order->set_status( 'pending' );
        $order->save();
        $item = current( $order->get_items() );
        $item->add_meta_data( 'Length', '120 cm' );
        $item->save();
        $order->payment_complete();

        $this->assertCount( 2, $this->sent_mail );
        foreach ( $this->sent_mail as $mail ) {
            $this->assertStringNotContainsString( '12.34', $mail['message'] );
            $this->assertStringNotContainsString( '12,34', $mail['message'] );
            $this->assertStringContainsString( 'MAIL-PLAIN', $mail['message'] );
            $this->assertStringNotContainsString( '<html', $mail['message'] );
            $this->assertStringContainsString( 'Length: 120 cm', $mail['message'] );
            $this->assertStringContainsString( 'Plain Buyer', $mail['message'] );
            $this->assertStringContainsString( 'Plain message.', $mail['message'] );
        }
    }
    public function test_email_settings_expose_both_emails_and_disabling_stops_delivery(): void {
        $this->capture_mail();
        $emails = WC()->mailer()->get_emails();
        $this->assertArrayHasKey( 'JPS_CS_Email_New_Inquiry', $emails );
        $this->assertArrayHasKey( 'JPS_CS_Email_Inquiry_Confirmation', $emails );

        update_option( 'woocommerce_jps_cs_new_inquiry_settings', [ 'enabled' => 'no' ] );
        update_option( 'woocommerce_jps_cs_inquiry_confirmation_settings', [ 'enabled' => 'no' ] );
        $mailer = WC()->mailer();
        $mailer->emails = [];
        $mailer->init();
        $product = new WC_Product_Simple();
        $product->set_name( 'Disabled mail product' );
        $product->save();
        $order = wc_create_order();
        $order->add_product( $product );
        $order->set_billing_email( 'buyer@example.test' );
        $order->update_meta_data( '_jps_cs_inquiry', 'yes' );
        $order->set_status( 'pending' );
        $order->save();
        $order->payment_complete();

        $this->assertCount( 0, $this->sent_mail );
    }

    private function capture_mail(): void {
        WC()->mailer()->get_emails();
        add_filter(
            'pre_wp_mail',
            function ( $pre_wp_mail, array $attributes ) {
                $attributes['to'] = (array) $attributes['to'];
                $attributes['headers'] = (array) $attributes['headers'];
                $this->sent_mail[] = $attributes;
                return true;
            },
            10,
            2
        );
    }
}
