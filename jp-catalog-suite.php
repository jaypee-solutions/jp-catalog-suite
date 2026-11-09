<?php
/**
 * Plugin Name:       JayPee Solutions Catalog Suite for WooCommerce
 * Plugin URI:        https://github.com/jaypee-solutions/jp-catalog-suite
 * Description:       Quote mode, extended product tabs with access levels and protected files, and customer-entered lengths for WooCommerce.
 * Version:           0.2.0
 * Author:            JayPee Solutions
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       jp-catalog-suite
 * Domain Path:       /languages
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * WC requires at least: 8.0
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

defined( 'ABSPATH' ) || exit;

const VERSION     = '0.2.0';
const PLUGIN_DIR  = __DIR__ . '/';
const PLUGIN_FILE = __FILE__;
const GITHUB_REPO = 'jaypee-solutions/jp-catalog-suite';
const UPDATE_URL  = 'https://github.com/jaypee-solutions/jp-catalog-suite/';

// Bundled by scripts/build-release.sh; present in dev after `composer install`.
if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

require_once __DIR__ . '/includes/class-updates.php';

require_once __DIR__ . '/includes/class-inquiries.php';
require_once __DIR__ . '/includes/class-inquiry-intake.php';
require_once __DIR__ . '/includes/class-quote-mode.php';
require_once __DIR__ . '/includes/class-product-tabs.php';
require_once __DIR__ . '/includes/class-inquiry-emails.php';
require_once __DIR__ . '/includes/class-access-block.php';
require_once __DIR__ . '/includes/class-download-log.php';
require_once __DIR__ . '/includes/class-file-list-block.php';
require_once __DIR__ . '/includes/class-plugin.php';

register_activation_hook( PLUGIN_FILE, [ Plugin::class, 'activate' ] );
register_deactivation_hook( PLUGIN_FILE, [ Plugin::class, 'deactivate' ] );

Updates::init();

/**
 * Bootstrap.
 */
add_action(
    'plugins_loaded',
    static function (): void {
        load_plugin_textdomain(
            'jp-catalog-suite',
            false,
            dirname( plugin_basename( PLUGIN_FILE ) ) . '/languages'
        );

        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action(
                'admin_notices',
                static function (): void {
                    printf(
                        '<div class="notice notice-error"><p>%s</p></div>',
                        esc_html__( 'JayPee Solutions Catalog Suite for WooCommerce requires WooCommerce.', 'jp-catalog-suite' )
                    );
                }
            );
            return;
        }

        Plugin::init();
    }
);

/**
 * Declare HPOS (custom order tables) compatibility.
 */
add_action(
    'before_woocommerce_init',
    static function (): void {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                PLUGIN_FILE,
                true
            );
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'cart_checkout_blocks',
                PLUGIN_FILE,
                true
            );
        }
    }
);
