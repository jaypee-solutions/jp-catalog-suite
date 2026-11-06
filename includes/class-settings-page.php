<?php
/**
 * WooCommerce settings page.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class Settings_Page extends \WC_Settings_Page {

    public function __construct() {
        $this->id    = 'jps_cs';
        $this->label = __( 'Catalog Suite', 'jp-catalog-suite' );
        parent::__construct();
    }

    public function get_own_sections(): array {
        $sections = [ '' => __( 'General', 'jp-catalog-suite' ) ];
        $labels   = [
            'quote'  => __( 'Quote mode', 'jp-catalog-suite' ),
            'tabs'   => __( 'Product tabs', 'jp-catalog-suite' ),
            'length' => __( 'Variable length', 'jp-catalog-suite' ),
        ];
        foreach ( Plugin::MODULES as $id => $class ) {
            $sections[ $id ] = $labels[ $id ];
        }
        return $sections;
    }

    public function get_settings_for_default_section(): array {
        $settings = [
            [
                'title' => __( 'General', 'jp-catalog-suite' ),
                'type'  => 'title',
                'id'    => 'jps_cs_general',
            ],
        ];
        $labels = [ 'quote' => __( 'Quote mode', 'jp-catalog-suite' ), 'tabs' => __( 'Product tabs', 'jp-catalog-suite' ), 'length' => __( 'Variable length', 'jp-catalog-suite' ) ];
        foreach ( Plugin::MODULES as $id => $class ) {
            $settings[] = [
                'title'   => $labels[ $id ],
                'id'      => "jps_cs_module_{$id}",
                'type'    => 'checkbox',
                'default' => 'no',
            ];
        }
        $settings[] = [
            'title'    => __( 'Delete tabs, protected files and the download log when the plugin is deleted. Orders are never deleted.', 'jp-catalog-suite' ),
            'id'       => 'jps_cs_uninstall_delete_data',
            'type'     => 'checkbox',
            'default'  => 'no',
            'desc'     => __( 'Delete tabs, protected files and the download log when the plugin is deleted. Orders are never deleted.', 'jp-catalog-suite' ),
        ];
        $settings[] = [ 'type' => 'sectionend', 'id' => 'jps_cs_general' ];
        return $settings;
    }

    public function get_settings_for_quote_section(): array {
        return Plugin::MODULES['quote']::settings();
    }

    public function get_settings_for_tabs_section(): array {
        return Plugin::MODULES['tabs']::settings();
    }

    public function get_settings_for_length_section(): array {
        return Plugin::MODULES['length']::settings();
    }
}
