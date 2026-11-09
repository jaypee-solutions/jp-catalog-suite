<?php
/**
 * Uninstall handler.
 *
 * @package JayPee\CatalogSuite
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$purge = 'yes' === get_option( 'jps_cs_uninstall_delete_data' );

global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Read option names matching the required uninstall prefix.
$option_names = $wpdb->get_col(
    $wpdb->prepare(
        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
        $wpdb->esc_like( 'jps_cs_' ) . '%'
    )
);
foreach ( $option_names as $option_name ) {
    delete_option( $option_name );
}
delete_option( 'jp_catalog_suite_github_token' );
delete_option( 'woocommerce_jps_cs_new_inquiry_settings' );
delete_option( 'woocommerce_jps_cs_inquiry_confirmation_settings' );
delete_option( 'woocommerce_jps_cs_quote_settings' );
delete_option( 'woocommerce_jps_cs_quote_accepted_settings' );
wp_clear_scheduled_hook( 'jps_cs_purge_download_log' );

if ( ! $purge ) {
    return;
}

$tab_ids = get_posts(
    [
        'post_type'        => 'jps_cs_tab',
        'post_status'      => array_keys( get_post_stati() ),
        'numberposts'    => -1,
        'fields'         => 'ids',
        'suppress_filters' => true,
    ]
);
foreach ( $tab_ids as $tab_id ) {
    wp_delete_post( $tab_id, true );
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Remove the plugin-owned log table before deleting files.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}jps_cs_download_log" );
$attachment_ids = get_posts(
    [
        'post_type'        => 'attachment',
        'post_status'      => array_keys( get_post_stati() ),
        'numberposts' => -1,
        'fields'      => 'ids',
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Select only protected attachments for opt-in removal.
        'meta_key'    => '_jps_cs_protected',
        'meta_value'  => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Select only protected attachments for opt-in removal.
    ]
);
foreach ( $attachment_ids as $attachment_id ) {
    wp_delete_attachment( $attachment_id, true );
}

foreach ( [ '_jps_cs_hidden_tabs', '_jps_cs_length_enabled', '_jps_cs_length_unit', '_jps_cs_length_min', '_jps_cs_length_max', '_jps_cs_length_step', '_jps_cs_length_default', '_jps_cs_length_price_per_unit' ] as $meta_key ) {
    delete_post_meta_by_key( $meta_key );
}


$protected_dir = wp_upload_dir()['basedir'] . '/jps-cs-protected';
if ( is_dir( $protected_dir ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
    $filesystem = new WP_Filesystem_Direct( false );
    $filesystem->rmdir( $protected_dir, true );
}
