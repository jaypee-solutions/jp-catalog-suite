<?php
/** Protected file download records, retention and privacy tools. @package JayPee\CatalogSuite */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class Download_Log {
    private static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'jps_cs_download_log';
    }

    public static function init(): void {
        global $wpdb;
        if ( '1' !== get_option( 'jps_cs_db_version' ) ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Check for a missing table only before initial installation.
            $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( self::table() ) ) );
            if ( self::table() !== $table_exists ) {
                self::install_table();
            } else {
                update_option( 'jps_cs_db_version', '1' );
            }
        }
        add_action( 'jps_cs_before_file_delivery', [ self::class, 'record' ], 10, 3 );
        add_action( 'admin_menu', [ self::class, 'admin_menu' ] );
        add_action( 'delete_user', [ self::class, 'delete_user' ] );
        add_filter( 'wp_privacy_personal_data_exporters', [ self::class, 'exporter' ] );
        add_filter( 'wp_privacy_personal_data_erasers', [ self::class, 'eraser' ] );
        add_action( 'admin_init', [ self::class, 'privacy_policy' ] );
        add_action( 'jps_cs_purge_download_log', [ self::class, 'purge' ] );
        if ( ! wp_next_scheduled( 'jps_cs_purge_download_log' ) ) {
            wp_schedule_event( time(), 'daily', 'jps_cs_purge_download_log' );
        }
    }

    public static function install_table(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        dbDelta( 'CREATE TABLE ' . self::table() . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            attachment_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            downloaded_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY downloaded_at (downloaded_at)
        ) $charset;" );
        update_option( 'jps_cs_db_version', '1' );
    }

    public static function record( int $attachment_id, int $user_id, int $product_id ): void {
        if ( 'yes' !== get_option( 'jps_cs_files_log_enabled', 'no' ) ) {
            return;
        }
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Insert one immutable log record.
        $wpdb->insert(
            self::table(),
            [
                'user_id'       => $user_id,
                'attachment_id' => $attachment_id,
                'product_id'    => $product_id,
                'downloaded_at' => current_time( 'mysql', true ),
            ],
            [ '%d', '%d', '%d', '%s' ]
        );
    }

    public static function purge(): void {
        $days = absint( get_option( 'jps_cs_files_log_retention_days', 365 ) );
        if ( ! $days ) {
            return;
        }
        global $wpdb;
        $cutoff = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS * $days );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Table name is derived from the trusted database prefix.
        $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . ' WHERE downloaded_at < %s', $cutoff ) );
    }

    public static function admin_menu(): void {
        // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this capability.
        add_submenu_page( 'woocommerce', __( 'Download log', 'jp-catalog-suite' ), __( 'Download log', 'jp-catalog-suite' ), 'manage_woocommerce', 'jps-cs-download-log', [ self::class, 'render' ] );
    }

    public static function render(): void {
        // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this capability.
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to view this page.', 'jp-catalog-suite' ) );
        }
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Table name is derived from the trusted database prefix.
        $rows = $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY id DESC LIMIT 200' );
        echo '<div class="wrap"><h1>' . esc_html__( 'Download log', 'jp-catalog-suite' ) . '</h1><table class="widefat"><thead><tr>';
        foreach ( [ __( 'User', 'jp-catalog-suite' ), __( 'File', 'jp-catalog-suite' ), __( 'Product', 'jp-catalog-suite' ), __( 'Date', 'jp-catalog-suite' ) ] as $heading ) {
            echo '<th>' . esc_html( $heading ) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ( $rows as $row ) {
            $user = get_userdata( (int) $row->user_id );
            echo '<tr><td>' . esc_html( $user ? $user->display_name . ' (' . $user->user_email . ')' : '' ) . '</td><td>' . esc_html( get_the_title( (int) $row->attachment_id ) ) . '</td><td>' . esc_html( $row->product_id ? get_the_title( (int) $row->product_id ) : '' ) . '</td><td>' . esc_html( get_date_from_gmt( $row->downloaded_at, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public static function delete_user( int $user_id ): void {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Delete only the specified user's records.
        $wpdb->delete( self::table(), [ 'user_id' => $user_id ], [ '%d' ] );
    }

    public static function exporter( array $exporters ): array {
        $exporters['jp-catalog-suite-download-log'] = [ 'exporter_friendly_name' => __( 'Download log', 'jp-catalog-suite' ), 'callback' => [ self::class, 'export_personal_data' ] ];
        return $exporters;
    }

    public static function export_personal_data( string $email, int $page = 1 ): array {
        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            return [ 'data' => [], 'done' => true ];
        }
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Table name is derived from the trusted database prefix.
        $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE user_id = %d ORDER BY id LIMIT %d OFFSET %d', $user->ID, 100, ( $page - 1 ) * 100 ) );
        $data = [];
        foreach ( $rows as $row ) {
            $data[] = [
                'group_id'    => 'download-log',
                'group_label' => __( 'Download log', 'jp-catalog-suite' ),
                'item_id'     => 'download-' . $row->id,
                'data'        => [
                    [ 'name' => __( 'File', 'jp-catalog-suite' ), 'value' => get_the_title( (int) $row->attachment_id ) ],
                    [ 'name' => __( 'Product', 'jp-catalog-suite' ), 'value' => $row->product_id ? get_the_title( (int) $row->product_id ) : '' ],
                    [ 'name' => __( 'Date', 'jp-catalog-suite' ), 'value' => get_date_from_gmt( $row->downloaded_at, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ],
                ],
            ];
        }
        return [ 'data' => $data, 'done' => count( $rows ) < 100 ];
    }

    public static function eraser( array $erasers ): array {
        $erasers['jp-catalog-suite-download-log'] = [ 'eraser_friendly_name' => __( 'Download log', 'jp-catalog-suite' ), 'callback' => [ self::class, 'erase_personal_data' ] ];
        return $erasers;
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WordPress eraser callback signature includes the page number.
    public static function erase_personal_data( string $email, int $page = 1 ): array {
        $user = get_user_by( 'email', $email );
        if ( $user ) {
            self::delete_user( (int) $user->ID );
        }
        return [ 'items_removed' => (bool) $user, 'items_retained' => false, 'messages' => [], 'done' => true ];
    }

    public static function privacy_policy(): void {
        if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
            wp_add_privacy_policy_content( __( 'Catalog Suite Download Log', 'jp-catalog-suite' ), '<p>' . esc_html__( 'When enabled, the Download log records the user, protected file, optional product and download time. Records are retained for the configured number of days; zero keeps them until erased or deleted with the user account.', 'jp-catalog-suite' ) . '</p>' );
        }
    }
}
