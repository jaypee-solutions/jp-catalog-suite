<?php
/**
 * Customer-entered length for simple products.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class Variable_Length {

    private const META = [ 'enabled' => '_jps_cs_length_enabled', 'unit' => '_jps_cs_length_unit', 'min' => '_jps_cs_length_min', 'max' => '_jps_cs_length_max', 'step' => '_jps_cs_length_step', 'default' => '_jps_cs_length_default' ];

    public static function init(): void {
        add_action( 'woocommerce_product_options_general_product_data', [ self::class, 'product_fields' ] );
        add_action( 'woocommerce_admin_process_product_object', [ self::class, 'save_product_fields' ] );
        add_action( 'woocommerce_variation_options_pricing', [ self::class, 'variation_fields' ], 10, 3 );
        add_action( 'woocommerce_save_product_variation', [ self::class, 'save_variation_fields' ], 10, 2 );
        add_filter( 'woocommerce_available_variation', [ self::class, 'available_variation' ], 10, 3 );
        add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_scripts' ] );
        add_action( 'woocommerce_before_add_to_cart_button', [ self::class, 'render_input' ] );
        add_filter( 'woocommerce_add_to_cart_validation', [ self::class, 'validate' ], 10, 3 );
        add_filter( 'woocommerce_add_cart_item_data', [ self::class, 'cart_item_data' ], 10, 3 );
        add_filter( 'woocommerce_get_item_data', [ self::class, 'display_item_data' ], 10, 2 );
        add_action( 'woocommerce_checkout_create_order_line_item', [ self::class, 'order_item_meta' ], 10, 4 );
        add_filter( 'woocommerce_loop_add_to_cart_link', [ self::class, 'loop_button' ], 10, 3 );
        add_filter( 'woocommerce_product_supports', [ self::class, 'disable_ajax_add' ], 10, 3 );
        add_filter( 'woocommerce_order_item_display_meta_key', [ self::class, 'order_meta_key' ], 10, 2 );
        add_filter( 'woocommerce_order_item_display_meta_value', [ self::class, 'order_meta_value' ], 10, 3 );
    }

    public static function settings(): array {
        return [
            [ 'title' => __( 'Variable length defaults', 'jp-catalog-suite' ), 'type' => 'title', 'id' => 'jps_cs_length' ],
            [ 'title' => __( 'Unit', 'jp-catalog-suite' ), 'id' => 'jps_cs_length_unit', 'type' => 'select', 'default' => 'cm', 'options' => [ 'mm' => 'mm', 'cm' => 'cm', 'm' => 'm' ] ],
            [ 'title' => __( 'Minimum length', 'jp-catalog-suite' ), 'id' => 'jps_cs_length_min', 'type' => 'number', 'default' => 1, 'custom_attributes' => [ 'min' => 0, 'step' => 'any' ] ],
            [ 'title' => __( 'Maximum length', 'jp-catalog-suite' ), 'id' => 'jps_cs_length_max', 'type' => 'number', 'default' => 1000, 'custom_attributes' => [ 'min' => 0, 'step' => 'any' ] ],
            [ 'title' => __( 'Length step', 'jp-catalog-suite' ), 'id' => 'jps_cs_length_step', 'type' => 'number', 'default' => 1, 'custom_attributes' => [ 'min' => 0, 'step' => 'any' ] ],
            [ 'type' => 'sectionend', 'id' => 'jps_cs_length' ],
        ];
    }

    public static function config( \WC_Product $product ): array {
        $id = Plugin::canonical_id( $product->get_id(), $product->get_type() );
        $stored_unit = get_post_meta( $id, self::META['unit'], true );
        $unit = '' !== $stored_unit ? $stored_unit : get_option( 'jps_cs_length_unit', 'cm' );
        $config = [
            'enabled' => 'yes' === get_post_meta( $id, self::META['enabled'], true ),
            'unit' => in_array( $unit, [ 'mm', 'cm', 'm' ], true ) ? $unit : 'cm',
            'min' => (float) ( '' !== get_post_meta( $id, self::META['min'], true ) ? get_post_meta( $id, self::META['min'], true ) : get_option( 'jps_cs_length_min', 1 ) ),
            'max' => (float) ( '' !== get_post_meta( $id, self::META['max'], true ) ? get_post_meta( $id, self::META['max'], true ) : get_option( 'jps_cs_length_max', 1000 ) ),
            'step' => (float) ( '' !== get_post_meta( $id, self::META['step'], true ) ? get_post_meta( $id, self::META['step'], true ) : get_option( 'jps_cs_length_step', 1 ) ),
            'default' => get_post_meta( $id, self::META['default'], true ),
        ];
        return apply_filters( 'jps_cs_length_config', $config, $product );
    }

    private static function variation_config( \WC_Product $parent_product, \WC_Product_Variation $variation ): array {
        $config = self::config( $parent_product );
        foreach ( [ 'min', 'max', 'step', 'default' ] as $key ) {
            $override = get_post_meta( $variation->get_id(), self::META[ $key ], true );
            if ( '' !== $override ) {
                $config[ $key ] = 'default' === $key ? $override : (float) $override;
            }
        }
        return $config;
    }

    public static function available_variation( array $data, \WC_Product $parent_product, \WC_Product_Variation $variation ): array {
        $config = self::variation_config( $parent_product, $variation );
        $data['jps_cs_length'] = [ 'enabled' => $config['enabled'], 'min' => $config['min'], 'max' => $config['max'], 'step' => $config['step'], 'default' => $config['default'] ];
        return $data;
    }

    public static function enqueue_scripts(): void {
        if ( ! is_product() ) {
            return;
        }
        $product = wc_get_product( get_queried_object_id() );
        if ( $product instanceof \WC_Product_Variable && self::config( $product )['enabled'] ) {
            wp_enqueue_script( 'jps-cs-variable-length', plugins_url( '../assets/js/variable-length.js', __FILE__ ), [ 'jquery', 'wc-add-to-cart-variation' ], '0.1.0', true );
        }
    }

    public static function variation_fields( int $loop, array $variation_data, \WP_Post $variation ): void {
        foreach ( [ 'min' => __( 'Minimum length override', 'jp-catalog-suite' ), 'max' => __( 'Maximum length override', 'jp-catalog-suite' ), 'step' => __( 'Length step override', 'jp-catalog-suite' ), 'default' => __( 'Default length override', 'jp-catalog-suite' ) ] as $key => $label ) {
            woocommerce_wp_text_input( [ 'id' => self::META[ $key ] . '[' . $loop . ']', 'name' => self::META[ $key ] . '[' . $loop . ']', 'value' => get_post_meta( $variation->ID, self::META[ $key ], true ), 'label' => $label, 'type' => 'number', 'wrapper_class' => 'form-row form-row-full', 'custom_attributes' => [ 'min' => 0, 'step' => 'any' ] ] );
        }
    }

    public static function save_variation_fields( int $variation_id, int $loop ): void {
        foreach ( [ 'min', 'max', 'step', 'default' ] as $key ) {
            $meta = self::META[ $key ];
            // WooCommerce verifies the variation editor nonce before this callback runs.
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $value = isset( $_POST[ $meta ][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST[ $meta ][ $loop ] ) ) : '';
            update_post_meta( $variation_id, $meta, $value );
        }
    }

    public static function product_fields(): void {
        global $product_object;
        if ( ! $product_object instanceof \WC_Product_Simple && ! $product_object instanceof \WC_Product_Variable ) {
            return;
        }
        woocommerce_wp_checkbox( [ 'id' => self::META['enabled'], 'label' => __( 'Variable length', 'jp-catalog-suite' ) ] );
        woocommerce_wp_select( [ 'id' => self::META['unit'], 'label' => __( 'Length unit', 'jp-catalog-suite' ), 'options' => [ '' => __( 'Use store default', 'jp-catalog-suite' ), 'mm' => 'mm', 'cm' => 'cm', 'm' => 'm' ] ] );
        foreach ( [ 'min' => __( 'Minimum length', 'jp-catalog-suite' ), 'max' => __( 'Maximum length', 'jp-catalog-suite' ), 'step' => __( 'Length step', 'jp-catalog-suite' ), 'default' => __( 'Default length', 'jp-catalog-suite' ) ] as $key => $label ) {
            woocommerce_wp_text_input( [ 'id' => self::META[ $key ], 'label' => $label, 'type' => 'number', 'custom_attributes' => [ 'min' => 0, 'step' => 'any' ] ] );
        }
    }

    public static function save_product_fields( \WC_Product $product ): void {
        if ( ! $product instanceof \WC_Product_Simple && ! $product instanceof \WC_Product_Variable ) {
            return;
        }
        foreach ( self::META as $key => $meta ) {
            // WooCommerce verifies the product editor nonce before this callback runs.
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $value = isset( $_POST[ $meta ] ) ? sanitize_text_field( wp_unslash( $_POST[ $meta ] ) ) : '';
            $product->update_meta_data( $meta, $value );
        }
    }

    public static function render_input(): void {
        global $product;
        if ( ! $product instanceof \WC_Product_Simple && ! $product instanceof \WC_Product_Variable ) {
            return;
        }
        $config = self::config( $product );
        if ( ! $config['enabled'] ) {
            return;
        }
        // translators: %s is the product's length unit (mm, cm, or m).
        printf( '<p class="jps-cs-length"><label for="jps_cs_length">%1$s</label><input id="jps_cs_length" name="jps_cs_length" type="number" required min="%2$s" max="%3$s" step="%4$s" value="%5$s" /></p>', esc_html( sprintf( __( 'Length (%s)', 'jp-catalog-suite' ), $config['unit'] ) ), esc_attr( (string) $config['min'] ), esc_attr( (string) $config['max'] ), esc_attr( (string) $config['step'] ), esc_attr( (string) $config['default'] ) );
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WooCommerce supplies quantity in its validation contract.
    public static function validate( bool $passed, int $product_id, int $quantity ): bool {
        $product = wc_get_product( $product_id );
        if ( ! $passed || ( ! $product instanceof \WC_Product_Simple && ! $product instanceof \WC_Product_Variable ) ) {
            return $passed;
        }
        $config = self::config( $product );
        if ( ! $config['enabled'] ) {
            return $passed;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WooCommerce validates the variation request.
        $variation_id = isset( $_REQUEST['variation_id'] ) ? absint( $_REQUEST['variation_id'] ) : 0;
        if ( $product instanceof \WC_Product_Variable && $variation_id ) {
            $variation = wc_get_product( $variation_id );
            if ( $variation instanceof \WC_Product_Variation && $variation->get_parent_id() === $product_id ) {
                $config = self::variation_config( $product, $variation );
            }
        }
        $value = self::request_length();
        $message = self::validation_message( $config, $value );
        if ( null !== $message ) {
            wc_add_notice( $message, 'error' );
            return false;
        }
        return true;
    }

    private static function request_length(): string {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce validates product add-to-cart requests.
        $value = isset( $_POST['jps_cs_length'] ) ? sanitize_text_field( wp_unslash( $_POST['jps_cs_length'] ) ) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Product add-to-cart data is validated below.
        if ( '' === $value && isset( $_REQUEST['jps_cs_length'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Product add-to-cart data is validated below.
            $value = sanitize_text_field( wp_unslash( $_REQUEST['jps_cs_length'] ) );
        }
        return is_scalar( $value ) ? wc_format_decimal( $value ) : '';
    }

    private static function validation_message( array $config, string $value ): ?string {
        $length = is_numeric( $value ) ? (float) $value : NAN;
        if ( ! is_finite( $length ) || $length < $config['min'] || $length > $config['max'] ) {
            // translators: %1$s and %2$s are the inclusive length bounds; %3$s is the unit.
            return sprintf( __( 'Please enter a length between %1$s and %2$s %3$s.', 'jp-catalog-suite' ), $config['min'], $config['max'], $config['unit'] );
        }
        $step = max( 0.0000001, $config['step'] );
        if ( abs( ( ( $length - $config['min'] ) / $step ) - round( ( $length - $config['min'] ) / $step ) ) > 0.00001 ) {
            // translators: %1$s is the required step size; %2$s is the unit.
            return sprintf( __( 'The length must be a multiple of %1$s %2$s.', 'jp-catalog-suite' ), $config['step'], $config['unit'] );
        }
        return null;
    }

    public static function cart_item_data( array $data, int $product_id, int $variation_id ): array {
        $product = wc_get_product( $product_id );
        if ( ! $product instanceof \WC_Product_Simple && ! $product instanceof \WC_Product_Variable ) {
            return $data;
        }
        $config = self::config( $product );
        if ( ! $config['enabled'] ) {
            return $data;
        }
        if ( $product instanceof \WC_Product_Variable && $variation_id ) {
            $variation = wc_get_product( $variation_id );
            if ( $variation instanceof \WC_Product_Variation && $variation->get_parent_id() === $product_id ) {
                $config = self::variation_config( $product, $variation );
            }
        }
        $value = self::request_length();
        $message = self::validation_message( $config, $value );
        if ( null !== $message ) {
            throw new \Exception( esc_html( $message ) );
        }
        $data['jps_cs_length'] = (float) $value;
        $data['jps_cs_length_unit'] = $config['unit'];
        return $data;
    }

    public static function display_item_data( array $data, array $cart_item ): array {
        if ( isset( $cart_item['jps_cs_length'], $cart_item['jps_cs_length_unit'] ) ) {
            $formatted_length = wc_format_localized_decimal( $cart_item['jps_cs_length'] );
            $data[] = [
                'key'     => __( 'Length', 'jp-catalog-suite' ),
                'value'   => $formatted_length . ' ' . $cart_item['jps_cs_length_unit'],
                'display' => $formatted_length . ' ' . $cart_item['jps_cs_length_unit'],
            ];
        }
        return $data;
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WooCommerce supplies order in its checkout hook contract.
    public static function order_item_meta( \WC_Order_Item_Product $item, string $cart_item_key, array $values, \WC_Order $order ): void {
        if ( isset( $values['jps_cs_length'], $values['jps_cs_length_unit'] ) ) {
            $item->add_meta_data( 'jps_cs_length', wc_format_decimal( $values['jps_cs_length'] ), true );
            $item->add_meta_data( '_jps_cs_length_unit', $values['jps_cs_length_unit'], true );
        }
    }
    public static function order_meta_key( string $display_key, \WC_Meta_Data $meta ): string {
        return 'jps_cs_length' === $meta->key ? __( 'Length', 'jp-catalog-suite' ) : $display_key;
    }

    public static function order_meta_value( string $display_value, \WC_Meta_Data $meta, \WC_Order_Item $item ): string {
        if ( 'jps_cs_length' !== $meta->key ) {
            return $display_value;
        }
        return wc_format_localized_decimal( $display_value ) . ' ' . $item->get_meta( '_jps_cs_length_unit', true );
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WooCommerce supplies loop arguments in its filter contract.
    public static function loop_button( string $html, \WC_Product $product, array $args ): string {
        if ( $product instanceof \WC_Product_Simple && self::config( $product )['enabled'] ) {
            return sprintf( '<a href="%s" class="button">%s</a>', esc_url( $product->get_permalink() ), esc_html__( 'Choose length', 'jp-catalog-suite' ) ); }
        return $html;
    }

    public static function disable_ajax_add( bool $supports, string $feature, \WC_Product $product ): bool {
        return 'ajax_add_to_cart' === $feature && $product instanceof \WC_Product_Simple && self::config( $product )['enabled'] ? false : $supports;
    }
}
