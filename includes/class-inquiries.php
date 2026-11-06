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
        add_filter( 'woocommerce_analytics_settings_default_excluded_order_statuses', [ self::class, 'excluded_statuses' ] );
        add_filter( 'option_woocommerce_excluded_report_order_statuses', [ self::class, 'saved_excluded_statuses' ] );
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

    public static function excluded_statuses( array $statuses ): array {
        if ( ! in_array( 'jps-inquiry', $statuses, true ) ) {
            $statuses[] = 'jps-inquiry';
        }
        return $statuses;
    }

    public static function saved_excluded_statuses( $statuses ) {
        if ( is_array( $statuses ) && ! in_array( 'jps-inquiry', $statuses, true ) ) {
            $statuses[] = 'jps-inquiry';
        }
        return $statuses;
    }

    public static function is_inquiry( \WC_Order $order ): bool {
        return 'yes' === $order->get_meta( '_jps_cs_inquiry' ) || $order->has_status( 'jps-inquiry' );
    }
}
