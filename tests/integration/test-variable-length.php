<?php
/** Variable length integration tests. */

declare( strict_types = 1 );

final class Test_Variable_Length extends WP_UnitTestCase {
    private function product( array $meta = [] ): WC_Product_Simple {
        $product = new WC_Product_Simple();
        $product->set_name( 'Length test' );
        $product->set_regular_price( '5' );
        $product->save();
        update_post_meta( $product->get_id(), '_jps_cs_length_enabled', 'yes' );
        update_post_meta( $product->get_id(), '_jps_cs_length_unit', 'cm' );
        update_post_meta( $product->get_id(), '_jps_cs_length_min', '10' );
        update_post_meta( $product->get_id(), '_jps_cs_length_max', '100' );
        update_post_meta( $product->get_id(), '_jps_cs_length_step', '0.5' );
        foreach ( $meta as $key => $value ) {
            update_post_meta( $product->get_id(), '_jps_cs_length_' . $key, (string) $value ); }
        return wc_get_product( $product->get_id() );
    }

    public function test_customer_can_add_distinct_valid_lengths_and_invalid_values_are_rejected(): void {
        $product = $this->product( [ 'max' => 300, 'step' => 1 ] );
        WC()->cart->empty_cart();
        $_REQUEST['jps_cs_length'] = '120';
        $_POST['jps_cs_length'] = '120';
        $this->assertNotFalse( WC()->cart->add_to_cart( $product->get_id() ) );
        $_REQUEST['jps_cs_length'] = '150';
        $_POST['jps_cs_length'] = '150';
        $this->assertNotFalse( WC()->cart->add_to_cart( $product->get_id() ) );
        $this->assertCount( 2, WC()->cart->get_cart() );
        $item_data = apply_filters( 'woocommerce_get_item_data', [], reset( WC()->cart->get_cart() ) );
        $this->assertSame( '120 cm', $item_data[0]['display'] );
        $_REQUEST['jps_cs_length'] = '5';
        $_POST['jps_cs_length'] = '5';
        $this->assertFalse( WC()->cart->add_to_cart( $product->get_id() ) );
        $this->assertNotEmpty( wc_get_notices( 'error' ) );
    }

    public function test_minimum_maximum_and_step_boundaries_are_validated_through_cart(): void {
        $product = $this->product();
        WC()->cart->empty_cart();
        foreach ( [ '10', '100', '10.5' ] as $valid ) {
            $_REQUEST['jps_cs_length'] = $valid;
            $_POST['jps_cs_length'] = $valid;
            $this->assertNotFalse( WC()->cart->add_to_cart( $product->get_id() ) );
            WC()->cart->empty_cart();
        }
        foreach ( [ '9.99', '100.01', '10.3' ] as $invalid ) {
            $_REQUEST['jps_cs_length'] = $invalid;
            $_POST['jps_cs_length'] = $invalid;
            $this->assertFalse( WC()->cart->add_to_cart( $product->get_id() ) );
            wc_clear_notices();
        }
        update_post_meta( $product->get_id(), '_jps_cs_length_step', '0.2' );
        $_REQUEST['jps_cs_length'] = '10.6';
        $_POST['jps_cs_length'] = '10.6';
        $this->assertNotFalse( WC()->cart->add_to_cart( $product->get_id() ) );
    }
}
