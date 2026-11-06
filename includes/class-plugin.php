<?php
/**
 * Plugin lifecycle and module registry.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class Plugin {

    public const MODULES = [
        'quote' => Quote_Mode::class,
        'tabs' => Product_Tabs::class,
        'length' => Variable_Length::class,
    ];

    public static function init(): void {
        require_once PLUGIN_DIR . 'includes/class-variable-length.php';
        Inquiries::init();
        add_filter(
            'woocommerce_get_settings_pages',
            static function ( array $pages ): array {
                require_once PLUGIN_DIR . 'includes/class-settings-page.php';
                $pages[] = new Settings_Page();
                return $pages;
            }
        );

        foreach ( self::MODULES as $id => $class ) {
            if ( self::is_module_active( $id ) ) {
                $class::init();
            }
        }
    }


    public static function is_module_active( string $id ): bool {
        return 'yes' === get_option( "jps_cs_module_{$id}", 'no' );
    }

    public static function canonical_id( int $id, string $type ): int {
        return (int) apply_filters(
            'wpml_object_id',
            $id,
            $type,
            true,
            apply_filters( 'wpml_default_language', null )
        );
    }

    public static function activate(): void {}

    public static function deactivate(): void {
        wp_clear_scheduled_hook( 'jps_cs_purge_download_log' );
    }
}
