<?php
/**
 * Germanized compatibility for Quote mode.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class Germanized_Integration {

    public static function init(): void {
        if ( ! class_exists( 'WooCommerce_Germanized' ) ) {
            return;
        }

        add_action( 'woocommerce_gzd_registered_legal_checkboxes', [ self::class, 'remove_legal_checkboxes' ] );
        add_action( 'wp', [ self::class, 'remove_shopmarks' ], PHP_INT_MAX );
    }

    public static function remove_legal_checkboxes( $manager ): void {
        if (
            ! Quote_Mode::is_active()
            || ! is_object( $manager )
            || ! method_exists( $manager, 'get_checkboxes' )
            || ! method_exists( $manager, 'remove' )
        ) {
            return;
        }
        $kept = (array) apply_filters( 'jps_cs_germanized_kept_checkboxes', [ 'privacy' ] );
        foreach ( array_keys( $manager->get_checkboxes() ) as $id ) {
            if ( ! in_array( $id, $kept, true ) ) {
                $manager->remove( $id );
            }
        }
    }

    public static function remove_shopmarks(): void {
        if ( ! Quote_Mode::is_active() ) {
            return;
        }

        foreach (
            [
                'wc_gzd_get_single_product_shopmarks',
                'wc_gzd_get_single_product_grouped_shopmarks',
                'wc_gzd_get_product_loop_shopmarks',
                'wc_gzd_get_product_block_shopmarks',
                'wc_gzd_get_cart_shopmarks',
                'wc_gzd_get_mini_cart_shopmarks',
                'wc_gzd_get_checkout_shopmarks',
                'wc_gzd_get_order_shopmarks',
            ] as $getter
        ) {
            if ( ! function_exists( $getter ) ) {
                continue;
            }
            foreach ( $getter() as $shopmark ) {
                if ( is_object( $shopmark ) && method_exists( $shopmark, 'remove' ) ) {
                    $shopmark->remove();
                }
            }
        }
    }
}
