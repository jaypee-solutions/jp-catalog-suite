<?php
/**
 * Inquiry order behavior independent of quote mode.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class Inquiries {

    public static function init(): void {
        add_filter( 'woocommerce_register_shop_order_post_statuses', [ self::class, 'register_status' ] );
        add_filter( 'wc_order_statuses', [ self::class, 'order_statuses' ] );
        add_filter( 'default_option_woocommerce_excluded_report_order_statuses', [ self::class, 'excluded_statuses' ] );
        add_filter( 'option_woocommerce_excluded_report_order_statuses', [ self::class, 'excluded_statuses' ] );
        add_filter( 'woocommerce_get_order_item_totals', [ self::class, 'order_item_totals' ], 10, 3 );
        add_filter( 'woocommerce_order_formatted_line_subtotal', [ self::class, 'line_subtotal' ], 10, 3 );
        add_filter( 'woocommerce_get_formatted_order_total', [ self::class, 'formatted_order_total' ], 10, 2 );
        // Account labels belong to Quote mode and are registered by Inquiry_Intake.
    }

    public static function register_status( array $statuses ): array {
        $statuses['wc-jps-inquiry'] = [
            'label'                     => _x( 'Inquiry', 'Order status', 'jp-catalog-suite' ),
            'public'                    => false,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            // translators: %s is the number of inquiries in this order-status count.
            'label_count'               => _n_noop( 'Inquiry <span class="count">(%s)</span>', 'Inquiries <span class="count">(%s)</span>', 'jp-catalog-suite' ),
        ];
        return $statuses;
    }

    public static function order_statuses( array $statuses ): array {
        $label = _x( 'Inquiry', 'Order status', 'jp-catalog-suite' );
        $result = [];
        foreach ( $statuses as $key => $value ) {
            $result[ $key ] = $value;
            if ( 'wc-pending' === $key ) {
                $result['wc-jps-inquiry'] = $label;
            }
        }
        if ( ! isset( $result['wc-jps-inquiry'] ) ) {
            $result['wc-jps-inquiry'] = $label;
        }
        return $result;
    }

    public static function excluded_statuses( $statuses ) {
        if ( is_array( $statuses ) && ! in_array( 'jps-inquiry', $statuses, true ) ) {
            $statuses[] = 'jps-inquiry';
        }
        return $statuses;
    }

    public static function order_item_totals( array $totals, \WC_Order $order, string $tax_display ): array {
        unset( $tax_display );
        return self::is_inquiry( $order ) && ( ! is_admin() || doing_action( 'woocommerce_email_order_details' ) ) ? [] : $totals;
    }

    public static function line_subtotal( string $subtotal, \WC_Order_Item_Product $item, \WC_Order $order ): string {
        unset( $item );
        return self::is_inquiry( $order ) && ( ! is_admin() || doing_action( 'woocommerce_email_order_details' ) ) ? '' : $subtotal;
    }

    public static function formatted_order_total( string $total, \WC_Order $order ): string {
        return self::is_inquiry( $order ) && ( ! is_admin() || doing_action( 'woocommerce_email_order_details' ) ) ? '' : $total;
    }

    public static function account_menu_items( array $items ): array {
        if ( Quote_Mode::is_active() && isset( $items['orders'] ) ) {
            $items['orders'] = __( 'Inquiries', 'jp-catalog-suite' );
        }
        return $items;
    }

    public static function account_order_columns( array $columns ): array {
        if ( Quote_Mode::is_active() ) {
            unset( $columns['order-total'] );
        }
        return $columns;
    }

    public static function is_inquiry( \WC_Order $order ): bool {
        return 'yes' === $order->get_meta( '_jps_cs_inquiry' ) || $order->has_status( 'jps-inquiry' );
    }
}
