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
        add_filter( 'woocommerce_cart_needs_shipping', [ self::class, 'needs_shipping' ] );
        add_filter( 'wc_tax_enabled', [ self::class, 'taxes_enabled' ] );
        add_filter( 'woocommerce_coupons_enabled', [ self::class, 'coupons_enabled' ] );
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

    public static function settings(): array {
        return [
            [ 'title' => __( 'Quote mode', 'jp-catalog-suite' ), 'type' => 'title', 'id' => 'jps_cs_quote' ],
            [ 'type' => 'sectionend', 'id' => 'jps_cs_quote' ],
        ];
    }
}
