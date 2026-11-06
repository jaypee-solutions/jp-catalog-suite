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

WP_CLI::success( 'Seed complete.' );
