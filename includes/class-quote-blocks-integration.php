<?php
/**
 * WooCommerce Blocks label integration for Quote mode.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class Quote_Blocks_Integration implements \Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface {

    public function get_name(): string {
        return 'jp-catalog-suite';
    }

    public function initialize(): void {
        $this->register_script();
    }

    public function get_script_handles(): array {
        return [ 'jps-cs-quote-blocks' ];
    }

    public function get_editor_script_handles(): array {
        return [];
    }

    public function get_script_data(): array {
        return [ 'labels' => Quote_Mode::labels() ];
    }

    public function register_script(): void {
        $asset = require PLUGIN_DIR . 'assets/js/quote-blocks.asset.php';
        wp_register_script(
            'jps-cs-quote-blocks',
            plugins_url( 'assets/js/quote-blocks.js', PLUGIN_FILE ),
            $asset['dependencies'],
            VERSION,
            true
        );
        wp_set_script_translations( 'jps-cs-quote-blocks', 'jp-catalog-suite', PLUGIN_DIR . 'languages' );
    }
}
