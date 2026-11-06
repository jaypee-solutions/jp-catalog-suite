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
// Keep Germanized active for checkout behavior without its first-run admin redirect.
delete_transient( '_wc_gzd_setup_wizard_redirect' );

// Ticket #9: Global tabs tracer fixtures.
$tab_query = new WP_Query( [ 'post_type' => 'jps_cs_tab', 'title' => 'Downloads', 'post_status' => 'any', 'posts_per_page' => 1 ] );
$tab = $tab_query->posts[0] ?? null;
if ( ! $tab ) {
    $tab_id = wp_insert_post( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'publish', 'post_title' => 'Downloads', 'post_content' => '<p>Public info</p>' ] );
    update_post_meta( $tab_id, '_jps_cs_scope', 'all' );
    update_post_meta( $tab_id, '_jps_cs_priority', '50' );
}
$product_query = new WP_Query( [ 'post_type' => 'product', 'title' => 'Tab Demo', 'post_status' => 'any', 'posts_per_page' => 1 ] );
$product = isset( $product_query->posts[0] ) ? wc_get_product( $product_query->posts[0]->ID ) : false;
if ( ! $product ) {
    $product = new WC_Product_Simple();
    $product->set_name( 'Tab Demo' );
    $product->set_regular_price( '1' );
    $product->set_status( 'publish' );
    $product->save();
}
update_option( 'woocommerce_coming_soon', 'no' );
update_option( 'jps_cs_module_tabs', 'yes' );

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

// Ticket #3: Inquiry tracer bullet: price-less checkout becomes an Inquiry fixtures.
$quote_profile_id = wc_get_product_id_by_sku( 'QP-1' );
if ( ! $quote_profile_id ) {
    $product = new WC_Product_Simple();
    $product->set_name( 'Quote Profile' );
    $product->set_sku( 'QP-1' );
    $product->set_status( 'publish' );
    $quote_profile_id = $product->save();
}
$priced_bolt_id = wc_get_product_id_by_sku( 'QP-2' );
if ( ! $priced_bolt_id ) {
    $product = new WC_Product_Simple();
    $product->set_name( 'Priced Bolt' );
    $product->set_sku( 'QP-2' );
    $product->set_regular_price( '9.99' );
    $product->set_status( 'publish' );
    $priced_bolt_id = $product->save();
}
foreach ( [ 'Classic Cart' => '[woocommerce_cart]', 'Classic Checkout' => '[woocommerce_checkout]' ] as $title => $content ) {
    $existing = get_posts( [ 'post_type' => 'page', 'title' => $title, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ] );
    if ( ! $existing ) {
        wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_content' => $content ] );
    }
}


// Ticket #12: Access block and protected notice fixtures.
$access_tab_id = $tab->ID ?? ( $tab_id ?? 0 );
if ( $access_tab_id ) {
    wp_update_post(
        [
            'ID' => $access_tab_id,
            'post_content' => '<p>Public info</p><!-- wp:jp-catalog-suite/access {"mode":"only-with-access"} --><p>Protected Downloads content.</p><!-- /wp:jp-catalog-suite/access -->',
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
