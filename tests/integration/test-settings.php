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
        $settings = apply_filters( 'woocommerce_get_settings_pages', [] );
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        $this->assertNotEmpty( array_filter( $settings, static fn ( $candidate ): bool => 'jps_cs' === $candidate->get_id() ) );
        global $current_tab, $current_section;
        $current_tab     = 'jps_cs';
        $current_section = '';
        if ( ! class_exists( 'WC_Admin_Settings' ) ) {
            require_once WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';
        }
        $_POST = [
            'jps_cs_module_quote'         => '1',
            'jps_cs_module_tabs'          => '1',
            'jps_cs_module_length'        => '1',
            'jps_cs_uninstall_delete_data' => '1',
            '_wpnonce'                    => wp_create_nonce( 'woocommerce-settings' ),
        ];
        $_REQUEST = $_POST;
        \WC_Admin_Settings::save();
        $this->assertSame( 'yes', get_option( 'jps_cs_module_quote' ) );
        $this->assertSame( 'yes', get_option( 'jps_cs_module_tabs' ) );
        $this->assertSame( 'yes', get_option( 'jps_cs_module_length' ) );
        $this->assertSame( 'yes', get_option( 'jps_cs_uninstall_delete_data' ) );
    }
}
