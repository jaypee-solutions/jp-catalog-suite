<?php
/**
 * Marks and completes customer Inquiries.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class Inquiry_Intake {

    public static function init(): void {
        add_action( 'woocommerce_checkout_create_order', [ self::class, 'mark_order' ], 10, 2 );
        add_action( 'woocommerce_store_api_checkout_update_order_meta', [ self::class, 'mark_order' ], 10, 1 );
        add_action( 'woocommerce_pre_payment_complete', [ self::class, 'complete_inquiry' ] );
        add_filter( 'woocommerce_cart_needs_payment', [ self::class, 'cart_needs_payment' ], 10, 2 );
        add_filter( 'woocommerce_order_needs_payment', [ self::class, 'order_needs_payment' ], 10, 3 );
        add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', [ self::class, 'valid_payment_statuses' ], 10, 2 );
    }

    public static function mark_order( \WC_Order $order, array $data = [] ): void {
        unset( $data );
        if ( Quote_Mode::is_active() ) {
            $order->update_meta_data( '_jps_cs_inquiry', 'yes' );
        }
    }

    public static function valid_payment_statuses( array $statuses, \WC_Order $order ): array {
        return Inquiries::is_inquiry( $order ) ? [] : $statuses;
    }

    public static function complete_inquiry( int $order_id ): void {
        $order = wc_get_order( $order_id );
        if ( ! $order || ! Inquiries::is_inquiry( $order ) ) {
            return;
        }
        $order->update_status( 'jps-inquiry', __( 'Inquiry received.', 'jp-catalog-suite' ) );
        do_action( 'jps_cs_inquiry_created', $order );
    }

    public static function cart_needs_payment( bool $needs_payment, $cart ): bool {
        unset( $cart );
        return Quote_Mode::is_active() ? false : $needs_payment;
    }

    public static function order_needs_payment( bool $needs_payment, \WC_Order $order, array $valid_statuses ): bool {
        unset( $valid_statuses );
        return Inquiries::is_inquiry( $order ) ? false : $needs_payment;
    }
}
