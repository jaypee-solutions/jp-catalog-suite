<?php
/**
 * Test seed: creates the fixtures the E2E suite expects.
 *
 * Call: npm run env:seed  (wp eval-file tests/seed.php)
 *
 * No declare(strict_types=1): wp eval-file does not evaluate the contents as the
 * first statement of a script, which would be a fatal error.
 *
 * @package JayPee\CatalogSuite
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    fwrite( STDERR, "Only callable via WP-CLI.\n" );
    exit( 1 );
}

global $wp_rewrite;
update_option( 'permalink_structure', '/%postname%/' );
$wp_rewrite->init();
$wp_rewrite->flush_rules();
require_once ABSPATH . 'wp-admin/includes/misc.php';
save_mod_rewrite_rules();

require_once ABSPATH . 'wp-admin/includes/plugin.php';
WP_CLI::runcommand( 'i18n make-mo ' . escapeshellarg( dirname( __DIR__ ) . '/languages' ) );
\WC_Install::create_pages();
if ( ! username_exists( 'customer1' ) ) {
    wp_create_user( 'customer1', 'password', 'customer1@example.com' );
    $customer = get_user_by( 'login', 'customer1' );
    $customer->set_role( 'customer' );
}
if ( is_plugin_active( 'woocommerce-germanized/woocommerce-germanized.php' ) ) {
    deactivate_plugins( 'woocommerce-germanized/woocommerce-germanized.php' );
}

global $wpdb;
foreach ( get_users( [ 'fields' => 'ID' ] ) as $user_id ) {
    update_user_meta(
        $user_id,
        $wpdb->get_blog_prefix() . 'persisted_preferences',
        [
            'core/edit-post' => [ 'welcomeGuide' => false ],
            'core'           => [ 'enableChoosePatternModal' => false ],
            '_modified'      => gmdate( 'c' ),
        ]
    );
}

WP_CLI::success( 'Seed complete.' );
// Ticket #17: Variable length tracer bullet on simple products fixtures.
$length_product = wc_get_product_id_by_sku( 'LB-1' );
if ( ! $length_product ) {
    $length_product = wp_insert_post( [ 'post_type' => 'product', 'post_status' => 'publish', 'post_title' => 'Length Bar' ] );
    update_post_meta( $length_product, '_sku', 'LB-1' );
    update_post_meta( $length_product, '_regular_price', '5' );
    update_post_meta( $length_product, '_price', '5' );
}
foreach ( [ 'enabled' => 'yes', 'unit' => 'cm', 'min' => '10', 'max' => '300', 'step' => '1' ] as $key => $value ) {
    update_post_meta( $length_product, '_jps_cs_length_' . $key, $value );
}
update_option( 'jps_cs_module_length', 'yes' );
update_option( 'jps_cs_module_quote', 'yes' );
