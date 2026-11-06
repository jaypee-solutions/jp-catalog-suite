<?php
/**
 * Quote mode behavior for catalog products and checkout.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class Quote_Mode {

    public static function init(): void {
        Inquiry_Intake::init();
        add_filter( 'woocommerce_is_purchasable', [ self::class, 'is_purchasable' ], 10, 2 );
        add_filter( 'woocommerce_variation_is_purchasable', [ self::class, 'is_variation_purchasable' ], 10, 2 );
        add_filter( 'woocommerce_variation_is_visible', [ self::class, 'is_variation_visible' ], 10, 4 );
        add_action( 'woocommerce_before_calculate_totals', [ self::class, 'set_missing_prices_to_zero' ] );
        add_filter( 'woocommerce_get_price_html', [ self::class, 'price_html' ], 100, 2 );
        add_filter( 'woocommerce_show_variation_price', [ self::class, 'show_variation_price' ], 100, 3 );
        add_filter( 'woocommerce_available_variation', [ self::class, 'available_variation' ], 100, 3 );
        add_filter( 'woocommerce_sale_flash', [ self::class, 'sale_flash' ], 100, 3 );
        foreach ( [ 'woocommerce_cart_item_price', 'woocommerce_cart_item_subtotal', 'woocommerce_cart_subtotal', 'woocommerce_cart_total', 'woocommerce_cart_totals_order_total_html' ] as $hook ) {
            add_filter( $hook, [ self::class, 'cart_price' ], 100 );
        }
        add_filter( 'woocommerce_structured_data_product', [ self::class, 'structured_data' ], 100, 2 );
        add_filter( 'woocommerce_catalog_orderby', [ self::class, 'catalog_orderby' ], 100 );
        add_filter( 'body_class', [ self::class, 'body_class' ] );
        add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_styles' ] );
        add_filter( 'woocommerce_cart_needs_shipping', [ self::class, 'needs_shipping' ] );
        add_filter( 'wc_tax_enabled', [ self::class, 'taxes_enabled' ] );
        add_filter( 'woocommerce_coupons_enabled', [ self::class, 'coupons_enabled' ] );
        add_filter( 'woocommerce_order_button_text', [ self::class, 'order_button_text' ], PHP_INT_MAX );
        add_filter( 'woocommerce_product_single_add_to_cart_text', [ self::class, 'add_to_cart_text' ], 100, 2 );
        add_filter( 'woocommerce_product_add_to_cart_text', [ self::class, 'add_to_cart_text' ], 100, 2 );
        add_filter( 'woocommerce_thankyou_order_received_text', [ self::class, 'thankyou_text' ], 10, 2 );
        add_filter( 'gettext_woocommerce', [ self::class, 'gettext' ], 10, 3 );
        add_filter( 'ngettext_woocommerce', [ self::class, 'ngettext' ], 10, 5 );
    }

    public static function is_active(): bool {
        return (bool) apply_filters( 'jps_cs_quote_mode_active', true );
    }

    public static function applies_to( \WC_Product $product ): bool {
        return (bool) apply_filters( 'jps_cs_quote_mode_applies_to_product', self::is_active(), $product );
    }

    public static function is_purchasable( bool $purchasable, \WC_Product $product ): bool {
        if ( ! self::applies_to( $product ) || 'publish' !== $product->get_status() || in_array( $product->get_type(), [ 'external', 'grouped' ], true ) ) {
            return $purchasable;
        }
        return '' !== $product->get_price( 'edit' ) ? $purchasable : true;
    }

    public static function is_variation_purchasable( bool $purchasable, \WC_Product_Variation $variation ): bool {
        $parent = wc_get_product( $variation->get_parent_id() );
        if ( ! $parent || 'publish' !== $parent->get_status() || ! self::applies_to( $variation ) ) {
            return $purchasable;
        }
        return '' !== $variation->get_price( 'edit' ) ? $purchasable : true;
    }

    public static function is_variation_visible( bool $visible, int $variation_id, int $parent_id, \WC_Product_Variation $variation ): bool {
        unset( $variation_id );
        $parent = wc_get_product( $parent_id );
        return $parent && 'publish' === $parent->get_status() && self::applies_to( $variation ) ? true : $visible;
    }

    public static function set_missing_prices_to_zero( $cart ): void {
        foreach ( $cart->get_cart() as $item ) {
            if ( '' === $item['data']->get_price( 'edit' ) && self::applies_to( $item['data'] ) ) {
                $item['data']->set_price( 0 );
            }
        }
    }

    public static function needs_shipping( bool $needs_shipping ): bool {
        return self::is_active() ? false : $needs_shipping;
    }

    public static function taxes_enabled( bool $enabled ): bool {
        $store_api = function_exists( 'WC' ) && WC() && method_exists( WC(), 'is_store_api_request' ) && WC()->is_store_api_request();
        $store_api = $store_api || ( defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_SERVER['REQUEST_URI'] ) && 0 === strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '/wp-json/wc/store/' ) );
        $frontend_request = ! is_admin() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST );
        if ( self::is_active() && ( $frontend_request || wp_doing_ajax() || $store_api ) ) {
            return false;
        }
        return $enabled;
    }

    public static function coupons_enabled( bool $enabled ): bool {
        return self::is_active() ? false : $enabled;
    }

    public static function price_html( string $html, \WC_Product $product ): string {
        return self::applies_to( $product ) ? '' : $html;
    }

    public static function show_variation_price( bool $show, $product = null, $variation = null ): bool {
        $product = $variation instanceof \WC_Product ? $variation : $product;
        return $product instanceof \WC_Product && self::applies_to( $product ) ? false : $show;
    }

    public static function available_variation( array $data, $product = null, $variation = null ): array {
        $product = $variation instanceof \WC_Product ? $variation : $product;
        if ( $product instanceof \WC_Product && self::applies_to( $product ) ) {
            $data['price_html'] = '';
        }
        return $data;
    }

    public static function sale_flash( string $html, $post = null, $product = null ): string {
        unset( $post );
        return $product instanceof \WC_Product && self::applies_to( $product ) ? '' : $html;
    }

    public static function cart_price( string $html ): string {
        return self::is_active() ? '' : $html;
    }

    public static function structured_data( array $markup, \WC_Product $product ): array {
        if ( self::applies_to( $product ) ) {
            unset( $markup['offers'] );
        }
        return $markup;
    }

    public static function catalog_orderby( array $orderby ): array {
        if ( self::is_active() ) {
            unset( $orderby['price'], $orderby['price-desc'] );
        }
        return $orderby;
    }

    public static function body_class( array $classes ): array {
        if ( self::is_active() ) {
            $classes[] = 'jps-cs-quote-mode';
        }
        return $classes;
    }

    public static function enqueue_styles(): void {
        if ( ! self::is_active() ) {
            return;
        }
        wp_enqueue_style( 'jps-cs-quote-mode', plugins_url( 'assets/css/quote-mode.css', PLUGIN_FILE ), [], VERSION );
        if ( wp_script_is( 'wc-blocks-checkout', 'registered' ) ) {
            wp_enqueue_script( 'jps-cs-quote-blocks', plugins_url( 'assets/js/quote-blocks.js', PLUGIN_FILE ), [ 'wc-blocks-checkout' ], VERSION, true );
        }
    }

    public static function add_to_cart_text( string $text, \WC_Product $product ): string {
        return self::applies_to( $product ) && in_array( $product->get_type(), [ 'simple', 'variation' ], true ) ? __( 'Add to inquiry', 'jp-catalog-suite' ) : $text;
    }

    public static function order_button_text( string $text ): string {
        return self::is_active() ? __( 'Send inquiry', 'jp-catalog-suite' ) : $text;
    }

    public static function thankyou_text( string $text, \WC_Order $order ): string {
        return Inquiries::is_inquiry( $order ) ? __( 'Thank you. Your inquiry has been received.', 'jp-catalog-suite' ) : $text;
    }

    public static function gettext( string $translation, string $text, string $domain ): string {
        unset( $domain );
        if ( ! self::is_active() ) {
            return $translation;
        }
        $labels = [
            'Cart' => __( 'Inquiry basket', 'jp-catalog-suite' ),
            'View cart' => __( 'Inquiry basket', 'jp-catalog-suite' ),
            'Your cart' => __( 'Inquiry basket', 'jp-catalog-suite' ),
            'Proceed to checkout' => __( 'Continue to inquiry', 'jp-catalog-suite' ),
            'Place order' => __( 'Send inquiry', 'jp-catalog-suite' ),
        ];
        return $labels[ $text ] ?? $translation;
    }

    public static function ngettext( string $translation, string $single, string $plural, int $number, string $domain ): string {
        return self::gettext( $translation, 1 === $number ? $single : $plural, $domain );
    }

    public static function settings(): array {
        return [
            [ 'title' => __( 'Quote mode', 'jp-catalog-suite' ), 'type' => 'title', 'id' => 'jps_cs_quote' ],
            [ 'type' => 'sectionend', 'id' => 'jps_cs_quote' ],
        ];
    }
}
