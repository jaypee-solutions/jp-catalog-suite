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
        update_option( 'jps_cs_checkout_company', 'hidden' );
        update_option( 'jps_cs_checkout_phone', 'required' );
        update_option( 'jps_cs_checkout_address', 'required' );
        update_option( 'jps_cs_checkout_message', 'no' );

        $fields = apply_filters( 'woocommerce_checkout_fields', WC()->checkout()->get_checkout_fields() );
        $this->assertArrayNotHasKey( 'billing_company', $fields['billing'] );
        $this->assertTrue( $fields['billing']['billing_phone']['required'] );
        $this->assertArrayNotHasKey( 'order_comments', $fields['order'] );

        $locale = apply_filters( 'woocommerce_get_country_locale', [ 'DE' => [ 'address_1' => [], 'postcode' => [] ] ] );
        $this->assertTrue( $locale['DE']['address_1']['required'] );
        $this->assertTrue( $locale['DE']['postcode']['required'] );
        $defaults = apply_filters( 'woocommerce_default_address_fields', [ 'address_1' => [] ] );
        $this->assertTrue( $defaults['address_1']['required'] );
    }

    public function test_hidden_address_is_removed_from_classic_and_locale_fields(): void {
        update_option( 'jps_cs_checkout_address', 'hidden' );
        $fields = apply_filters( 'woocommerce_checkout_fields', WC()->checkout()->get_checkout_fields() );
        foreach ( [ 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state', 'billing_postcode', 'billing_country' ] as $key ) {
            $this->assertArrayNotHasKey( $key, $fields['billing'] );
        }
        $locale = apply_filters( 'woocommerce_get_country_locale', [ 'DE' => [ 'address_1' => [] ] ] );
        $this->assertTrue( $locale['DE']['address_1']['hidden'] );
    }

    public function test_company_phone_and_address_support_every_configured_state(): void {
        foreach ( [ 'optional', 'required', 'hidden' ] as $state ) {
            update_option( 'jps_cs_checkout_company', $state );
            update_option( 'jps_cs_checkout_phone', $state );
            update_option( 'jps_cs_checkout_address', $state );
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
        }
    }

    public function test_blocks_company_and_phone_follow_saved_preferences(): void {
        update_option( 'jps_cs_checkout_company', 'required' );
        update_option( 'jps_cs_checkout_phone', 'hidden' );
        $this->assertSame( 'required', apply_filters( 'pre_option_woocommerce_checkout_company_field', false ) );
        $this->assertSame( 'hidden', apply_filters( 'pre_option_woocommerce_checkout_phone_field', false ) );
    }
}
