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

    private function variable_product(): array {
        $product = new WC_Product_Variable();
        $product->set_name( 'Length Rail' );
        $product->set_regular_price( '5' );
        $attribute = new WC_Product_Attribute();
        $attribute->set_name( 'Colour' );
        $attribute->set_options( [ 'Blue', 'Red' ] );
        $attribute->set_position( 0 );
        $attribute->set_visible( true );
        $attribute->set_variation( true );
        $product->set_attributes( [ $attribute ] );
        $product->save();
        update_post_meta( $product->get_id(), '_jps_cs_length_enabled', 'yes' );
        update_post_meta( $product->get_id(), '_jps_cs_length_unit', 'cm' );
        update_post_meta( $product->get_id(), '_jps_cs_length_min', '10' );
        update_post_meta( $product->get_id(), '_jps_cs_length_max', '300' );
        update_post_meta( $product->get_id(), '_jps_cs_length_step', '1' );
        $variations = [];
        foreach ( [ 'Blue', 'Red' ] as $colour ) {
            $variation = new WC_Product_Variation();
            $variation->set_status( 'publish' );
            $variation->set_parent_id( $product->get_id() );
            $variation->set_attributes( [ 'colour' => $colour ] );
            $variation->set_regular_price( '5' );
            $variation->save();
            if ( 'Blue' === $colour ) {
                $_POST['_jps_cs_length_min'] = [ 0 => '9.5' ];
                $_POST['_jps_cs_length_max'] = [ 0 => '200' ];
                $_POST['_jps_cs_length_step'] = [ 0 => '0.5' ];
                $_POST['_jps_cs_length_default'] = [ 0 => '120' ];
                do_action( 'woocommerce_save_product_variation', $variation->get_id(), 0 );
                unset( $_POST['_jps_cs_length_min'], $_POST['_jps_cs_length_max'], $_POST['_jps_cs_length_step'], $_POST['_jps_cs_length_default'] );
            }
            $variations[ $colour ] = $variation->get_id();
        }
        WC_Product_Variable::sync( $product->get_id() );
        return [ wc_get_product( $product->get_id() ), $variations ];
    }

    public function test_variation_length_overrides_are_sent_to_browser_and_enforced_for_cart_additions(): void {
        [ $product, $variations ] = $this->variable_product();
        $blue = wc_get_product( $variations['Blue'] );
        $red = wc_get_product( $variations['Red'] );
        $blue_data = apply_filters( 'woocommerce_available_variation', [], $product, $blue );
        $this->assertSame( 9.5, $blue_data['jps_cs_length']['min'] );
        $this->assertSame( 200.0, $blue_data['jps_cs_length']['max'] );
        $this->assertSame( 0.5, $blue_data['jps_cs_length']['step'] );
        $this->assertSame( '120', $blue_data['jps_cs_length']['default'] );
        $red_data = apply_filters( 'woocommerce_available_variation', [], $product, $red );
        $this->assertSame( 10.0, $red_data['jps_cs_length']['min'] );
        $this->assertSame( 300.0, $red_data['jps_cs_length']['max'] );
        $this->assertSame( 1.0, $red_data['jps_cs_length']['step'] );
        WC()->cart->empty_cart();
        $_REQUEST['jps_cs_length'] = '250';
        $_POST['jps_cs_length'] = '250';
        $_REQUEST['variation_id'] = (string) $variations['Blue'];
        $_POST['variation_id'] = (string) $variations['Blue'];
        $this->assertFalse( WC()->cart->add_to_cart( $product->get_id(), 1, $variations['Blue'], [ 'attribute_colour' => 'Blue' ] ) );
        wc_clear_notices();
        $_REQUEST['variation_id'] = (string) $variations['Red'];
        $_POST['variation_id'] = (string) $variations['Red'];
        $this->assertNotFalse( WC()->cart->add_to_cart( $product->get_id(), 1, $variations['Red'], [ 'attribute_colour' => 'Red' ] ) );
        $item = reset( WC()->cart->get_cart() );
        $this->assertSame( 250.0, $item['jps_cs_length'] );
        $this->assertSame( 'cm', $item['jps_cs_length_unit'] );
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
        $this->assertSame( [ 120.0, 150.0 ], array_column( array_values( WC()->cart->get_cart() ), 'jps_cs_length' ) );
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

    public function test_order_item_meta_formats_length_with_unit_without_exposing_raw_key(): void {
        $product = $this->product();
        $order = wc_create_order();
        $item = new WC_Order_Item_Product();
        $item->set_product( $product );
        do_action(
            'woocommerce_checkout_create_order_line_item',
            $item,
            'length-line',
            [ 'jps_cs_length' => '120', 'jps_cs_length_unit' => 'cm' ],
            $order
        );
        $order->add_item( $item );
        $order->save();

        $items = $order->get_items();
        $saved_item = reset( $items );
        $formatted_meta = array_values( $saved_item->get_formatted_meta_data() );
        $this->assertCount( 1, $formatted_meta );
        $this->assertSame( 'Length', $formatted_meta[0]->display_key );
        $this->assertSame( '120 cm', wp_strip_all_tags( $formatted_meta[0]->display_value ) );
        $this->assertNotSame( 'jps_cs_length', $formatted_meta[0]->display_key );
    }

    public function test_store_api_add_item_preserves_valid_length_and_rejects_invalid_length(): void {
        $product = $this->product( [ 'max' => 300, 'step' => 1 ] );
        WC()->cart->empty_cart();

        $request = new WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
        $request->add_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
        $request->set_body_params( [ 'id' => $product->get_id(), 'quantity' => 1, 'extensions' => [ 'jp-catalog-suite' => [ 'jps_cs_length' => '120' ] ] ] );
        $request->set_param( 'extensions', [ 'jp-catalog-suite' => [ 'jps_cs_length' => '120' ] ] );
        $response = rest_get_server()->dispatch( $request );
        $this->assertSame( 201, $response->get_status(), wp_json_encode( $response->get_data() ) );
        $this->assertCount( 1, WC()->cart->get_cart() );
        $item = reset( WC()->cart->get_cart() );
        $display_data = apply_filters( 'woocommerce_get_item_data', [], $item );
        $this->assertSame( '120 cm', $display_data[0]['display'] );
        $this->assertSame( 120.0, $item['jps_cs_length'] );
        $this->assertSame( 'cm', $item['jps_cs_length_unit'] );

        $request = new WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
        $request->add_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
        $request->set_body_params( [ 'id' => $product->get_id(), 'quantity' => 1, 'extensions' => [ 'jp-catalog-suite' => [ 'jps_cs_length' => '5' ] ] ] );
        $request->set_param( 'extensions', [ 'jp-catalog-suite' => [ 'jps_cs_length' => '5' ] ] );
        $response = rest_get_server()->dispatch( $request );
        $this->assertGreaterThanOrEqual( 400, $response->get_status() );
        $this->assertCount( 1, WC()->cart->get_cart() );
    }

    public function test_store_api_uses_variable_product_variation_length_limits(): void {
        [ $product, $variations ] = $this->variable_product();
        WC()->cart->empty_cart();
        $request = new WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
        $request->add_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
        $request->set_body_params( [ 'id' => $product->get_id(), 'quantity' => 1, 'variation' => [ [ 'attribute' => 'attribute_colour', 'value' => 'Blue' ] ] ] );
        $request->set_param( 'variation', [ [ 'attribute' => 'attribute_colour', 'value' => 'Blue' ] ] );
        $request->set_param( 'extensions', [ 'jp-catalog-suite' => [ 'jps_cs_length' => '9.5' ] ] );
        $response = rest_get_server()->dispatch( $request );
        $this->assertSame( 201, $response->get_status(), wp_json_encode( $response->get_data() ) );
        $item = reset( WC()->cart->get_cart() );
        $this->assertSame( 9.5, $item['jps_cs_length'] );
        $this->assertSame( 'cm', $item['jps_cs_length_unit'] );

        $request = new WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
        $request->add_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
        $request->set_body_params( [ 'id' => $product->get_id(), 'quantity' => 1, 'variation' => [ [ 'attribute' => 'attribute_colour', 'value' => 'Blue' ] ] ] );
        $request->set_param( 'variation', [ [ 'attribute' => 'attribute_colour', 'value' => 'Blue' ] ] );
        $request->set_param( 'extensions', [ 'jp-catalog-suite' => [ 'jps_cs_length' => '5' ] ] );
        $response = rest_get_server()->dispatch( $request );
        $this->assertGreaterThanOrEqual( 400, $response->get_status() );
        $this->assertCount( 1, WC()->cart->get_cart() );
    }

    public function test_store_api_distinct_lengths_create_distinct_cart_lines(): void {
        $product = $this->product( [ 'max' => 300, 'step' => 1 ] );
        WC()->cart->empty_cart();
        foreach ( [ '120', '150' ] as $length ) {
            $request = new WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
            $request->add_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
            $request->set_body_params( [ 'id' => $product->get_id(), 'quantity' => 1, 'jps_cs_length' => $length ] );
            $request->set_param( 'jps_cs_length', $length );
            $response = rest_get_server()->dispatch( $request );
            $this->assertSame( 201, $response->get_status() );
        }
        $this->assertCount( 2, WC()->cart->get_cart() );
    }
}
