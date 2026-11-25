<?php
/**
 * Inquiry checkout field integration behavior.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

final class Test_Checkout_Fields extends WP_UnitTestCase {

    public function test_default_checkout_fields_keep_identity_required_and_company_phone_optional(): void {
        $fields = apply_filters( 'woocommerce_checkout_fields', WC()->checkout()->get_checkout_fields() );
        $this->assertArrayHasKey( 'billing_company', $fields['billing'] );
        $this->assertFalse( $fields['billing']['billing_company']['required'] );
        $this->assertFalse( $fields['billing']['billing_phone']['required'] );
        foreach ( [ 'billing_first_name', 'billing_last_name', 'billing_email' ] as $key ) {
            $this->assertTrue( $fields['billing'][ $key ]['required'] );
        }
    }

    public function test_saved_field_preferences_change_classic_fields_and_address_locale(): void {
        update_option( 'jps_cs_quote_field_company', 'hidden' );
        update_option( 'jps_cs_quote_field_phone', 'required' );
        update_option( 'jps_cs_quote_field_address', 'required' );
        update_option( 'jps_cs_quote_field_message', 'no' );

        $fields = apply_filters( 'woocommerce_checkout_fields', WC()->checkout()->get_checkout_fields() );
        $this->assertArrayNotHasKey( 'billing_company', $fields['billing'] );
        $this->assertTrue( $fields['billing']['billing_phone']['required'] );
        $this->assertArrayNotHasKey( 'order_comments', $fields['order'] );

        $locale = apply_filters( 'woocommerce_get_country_locale', [ 'DE' => [ 'address_1' => [], 'address_2' => [], 'postcode' => [] ] ] );
        $this->assertTrue( $locale['DE']['address_1']['required'] );
        $this->assertFalse( $locale['DE']['address_2']['required'] );
        $this->assertTrue( $locale['DE']['postcode']['required'] );
        $billing = apply_filters( 'woocommerce_billing_fields', [ 'billing_address_1' => [], 'billing_address_2' => [] ] );
        $this->assertTrue( $billing['billing_address_1']['required'] );
        $this->assertFalse( $billing['billing_address_2']['required'] );
    }

    public function test_address_setting_leaves_shipping_fields_of_classic_forms_untouched(): void {
        add_filter( 'woocommerce_is_account_page', '__return_true' );
        foreach ( [ 'hidden', 'required' ] as $state ) {
            update_option( 'jps_cs_quote_field_address', $state );
            $shipping = ( new \WC_Countries() )->get_address_fields( 'DE', 'shipping_' );
            foreach ( [ 'shipping_address_1', 'shipping_city', 'shipping_postcode' ] as $key ) {
                $this->assertArrayHasKey( $key, $shipping );
                $this->assertTrue( $shipping[ $key ]['required'] );
            }
            $this->assertArrayHasKey( 'shipping_address_2', $shipping );
            $this->assertFalse( $shipping['shipping_address_2']['required'] );
        }
    }

    public function test_hidden_address_is_removed_from_classic_and_locale_fields(): void {
        update_option( 'jps_cs_quote_field_address', 'hidden' );
        $fields = apply_filters( 'woocommerce_checkout_fields', WC()->checkout()->get_checkout_fields() );
        foreach ( [ 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state', 'billing_postcode' ] as $key ) {
            $this->assertArrayNotHasKey( $key, $fields['billing'] );
        }
        $this->assertArrayHasKey( 'billing_country', $fields['billing'] );
        $locale = apply_filters( 'woocommerce_get_country_locale', [ 'DE' => [ 'address_1' => [] ] ] );
        $this->assertTrue( $locale['DE']['address_1']['hidden'] );
        $billing = apply_filters( 'woocommerce_billing_fields', [ 'billing_address_1' => [] ] );
        $this->assertArrayNotHasKey( 'billing_address_1', $billing );
    }

    public function test_company_phone_and_address_support_every_configured_state(): void {
        foreach ( [ 'optional', 'required', 'hidden' ] as $state ) {
            update_option( 'jps_cs_quote_field_company', $state );
            update_option( 'jps_cs_quote_field_phone', $state );
            update_option( 'jps_cs_quote_field_address', $state );
            $fields = apply_filters( 'woocommerce_checkout_fields', WC()->checkout()->get_checkout_fields() );
            foreach ( [ 'company', 'phone' ] as $field ) {
                $key = "billing_{$field}";
                if ( 'hidden' === $state ) {
                    $this->assertArrayNotHasKey( $key, $fields['billing'] );
                } else {
                    $this->assertArrayHasKey( $key, $fields['billing'] );
                    $this->assertSame( 'required' === $state, $fields['billing'][ $key ]['required'] );
                }
            }
            $locale = apply_filters( 'woocommerce_get_country_locale', [ 'DE' => [ 'address_1' => [] ] ] );
            $this->assertSame( 'required' === $state, $locale['DE']['address_1']['required'] );
            $this->assertSame( 'hidden' === $state, $locale['DE']['address_1']['hidden'] );
            $billing = apply_filters( 'woocommerce_billing_fields', [ 'billing_address_1' => [] ] );
            if ( 'hidden' === $state ) {
                $this->assertArrayNotHasKey( 'billing_address_1', $billing );
            } else {
                $this->assertSame( 'required' === $state, $billing['billing_address_1']['required'] );
            }
        }
    }

    public function test_blocks_company_and_phone_follow_saved_preferences(): void {
        update_option( 'jps_cs_quote_field_company', 'required' );
        update_option( 'jps_cs_quote_field_phone', 'hidden' );
        $this->assertSame( 'required', get_option( 'woocommerce_checkout_company_field' ) );
        $this->assertSame( 'hidden', get_option( 'woocommerce_checkout_phone_field' ) );
    }

    public function test_checkout_field_preferences_do_not_apply_when_quote_mode_is_inactive(): void {
        update_option( 'jps_cs_quote_field_company', 'hidden' );
        update_option( 'jps_cs_quote_field_address', 'hidden' );
        $preserve_pre = static fn ( $pre ) => false === $pre ? 'baseline-company' : $pre;
        add_filter( 'pre_option_woocommerce_checkout_company_field', $preserve_pre, 1 );
        add_filter( 'jps_cs_quote_mode_active', '__return_false' );

        $original = WC()->checkout()->get_checkout_fields();
        $fields   = apply_filters( 'woocommerce_checkout_fields', $original );
        $this->assertSame( $original, $fields );
        $this->assertSame( 'baseline-company', get_option( 'woocommerce_checkout_company_field' ) );
        $this->assertSame( [ 'DE' => [ 'address_1' => [] ] ], apply_filters( 'woocommerce_get_country_locale', [ 'DE' => [ 'address_1' => [] ] ] ) );
        $this->assertSame( [ 'address_1' => [] ], apply_filters( 'woocommerce_default_address_fields', [ 'address_1' => [] ] ) );

        remove_filter( 'jps_cs_quote_mode_active', '__return_false' );
        remove_filter( 'pre_option_woocommerce_checkout_company_field', $preserve_pre, 1 );
    }
    public function test_admin_ajax_keeps_tax_calculation_enabled(): void {
        $previous_get = $_GET;
        unset( $_GET['wc-ajax'] );
        set_current_screen( 'dashboard' );
        add_filter( 'wp_doing_ajax', '__return_true' );
        $this->assertTrue( apply_filters( 'wc_tax_enabled', true ) );
        $_GET['wc-ajax'] = 'checkout';
        $this->assertFalse( apply_filters( 'wc_tax_enabled', true ) );
        $_GET = $previous_get;
        remove_filter( 'wp_doing_ajax', '__return_true' );
        set_current_screen( 'front' );
    }
}
