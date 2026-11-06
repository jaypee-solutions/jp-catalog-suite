<?php
/**
 * WordPress integration-test bootstrap.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
    define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__, 2 ) . '/vendor/yoast/phpunit-polyfills' );
}

require dirname( __DIR__, 2 ) . '/vendor/autoload.php';
require_once getenv( 'WP_TESTS_DIR' ) . '/includes/functions.php';

tests_add_filter(
    'pre_option_jps_cs_module_quote',
    static function (): string {
        return 'yes';
    }
);

tests_add_filter(
    'muplugins_loaded',
    static function (): void {
        $woocommerce_file = WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
        if ( ! is_readable( $woocommerce_file ) ) {
            $woocommerce_file = WP_PLUGIN_DIR . '/woocommerce.latest-stable/woocommerce.php';
        }
        require_once $woocommerce_file;
        require_once dirname( __DIR__, 2 ) . '/jp-catalog-suite.php';
    }
);

tests_add_filter(
    'pre_option_jps_cs_module_tabs',
    static fn (): string => 'yes'
);

tests_add_filter(
    'setup_theme',
    static function (): void {
        \WC_Install::install();
        $GLOBALS['wp_roles'] = null;
        wp_roles();
    }
);

require getenv( 'WP_TESTS_DIR' ) . '/includes/bootstrap.php';

/**
 * Detect handlers that terminate PHP before PHPUnit can report a result.
 */
final class Integration_Run_Completion implements \PHPUnit\Runner\AfterLastTestHook {

    public static bool $completed = false;

    public function executeAfterLastTest(): void {
        self::$completed = true;
    }
}

register_shutdown_function(
    static function (): void {
        if ( ! Integration_Run_Completion::$completed ) {
            fwrite( STDERR, PHP_EOL . 'Integration run aborted before PHPUnit finished: a test or handler reached exit()/die().' . PHP_EOL );
            exit( 1 );
        }
    }
);
