<?php
/**
 * PHPUnit bootstrap.
 *
 * Loads only the plugin classes needed for isolated unit tests — no wp-env /
 * WordPress test suite required.
 *
 * Call: vendor/bin/phpunit
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

require dirname( __DIR__ ) . '/vendor/autoload.php';

// The plugin classes check defined( 'ABSPATH' ) || exit.
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

require __DIR__ . '/stubs.php';
require dirname( __DIR__ ) . '/includes/class-updates.php';
