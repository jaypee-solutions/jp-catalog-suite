<?php
/**
 * Integration tests for the Catalog Suite settings page.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

final class Test_Settings extends WP_UnitTestCase {

    public function test_catalog_suite_settings_page_is_registered(): void {
        $pages = apply_filters( 'woocommerce_get_settings_pages', [] );
        $ids   = array_map(
            static fn ( $page ): string => $page->get_id(),
            $pages
        );
        $this->assertContains( 'jps_cs', $ids );
    }

    public function test_general_settings_are_saved_by_woocommerce(): void {
        $pages = apply_filters( 'woocommerce_get_settings_pages', [] );
        $catalog_pages = array_values( array_filter( $pages, static fn ( $candidate ): bool => 'jps_cs' === $candidate->get_id() ) );
        $settings_page = $catalog_pages[0] ?? null;
        $this->assertNotNull( $settings_page );
        $this->assertNotFalse( $settings_page );
        $module_ids = array_values(
            array_filter(
                array_map( static fn ( array $setting ): string => $setting['id'] ?? '', $settings_page->get_settings( '' ) ),
                static fn ( string $id ): bool => str_starts_with( $id, 'jps_cs_module_' )
            )
        );
        $this->assertNotEmpty( $module_ids, 'At least one module toggle must be registered.' );
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        global $current_tab, $current_section;
        $current_tab     = 'jps_cs';
        $current_section = '';
        if ( ! class_exists( 'WC_Admin_Settings' ) ) {
            require_once WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';
        }
        $_POST    = array_fill_keys( $module_ids, '1' ) + [
            'jps_cs_uninstall_delete_data' => '1',
            '_wpnonce'                     => wp_create_nonce( 'woocommerce-settings' ),
        ];
        $_REQUEST = $_POST;
        \WC_Admin_Settings::save();
        foreach ( $module_ids as $module_id ) {
            $this->assertSame( 'yes', get_option( $module_id ) );
        }
        $this->assertSame( 'yes', get_option( 'jps_cs_uninstall_delete_data' ) );
    }

    public function test_protected_file_settings_are_saved_by_woocommerce(): void {
        $pages = apply_filters( 'woocommerce_get_settings_pages', [] );
        $catalog_pages = array_values( array_filter( $pages, static fn ( $candidate ): bool => 'jps_cs' === $candidate->get_id() ) );
        $settings_page = $catalog_pages[0] ?? null;
        $settings = $settings_page->get_settings( 'tabs' );
        $ids = [ 'jps_cs_files_mimes', 'jps_cs_files_protected_exts', 'jps_cs_files_delivery' ];
        $this->assertSame( $ids, array_values( array_intersect( $ids, array_column( $settings, 'id' ) ) ) );
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        global $current_tab, $current_section;
        $current_tab = 'jps_cs';
        $current_section = 'tabs';
        if ( ! class_exists( 'WC_Admin_Settings' ) ) {
            require_once WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';
        }
        $_POST = [
            'jps_cs_files_mimes' => "stp=application/step\ndxf=application/dxf",
            'jps_cs_files_protected_exts' => 'step,dxf',
            'jps_cs_files_delivery' => 'php',
            '_wpnonce' => wp_create_nonce( 'woocommerce-settings' ),
        ];
        $_REQUEST = $_POST;
        \WC_Admin_Settings::save();
        $this->assertSame( "stp=application/step\ndxf=application/dxf", get_option( 'jps_cs_files_mimes' ) );
        $this->assertSame( 'step,dxf', get_option( 'jps_cs_files_protected_exts' ) );
        $this->assertSame( 'php', get_option( 'jps_cs_files_delivery' ) );
    }
}
