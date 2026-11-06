<?php
/** Download log integration tests. @package JayPee\CatalogSuite */

declare( strict_types = 1 );

final class Test_Download_Log extends WP_UnitTestCase {
    private function rows( int $user_id ): int {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'jps_cs_download_log WHERE user_id = %d', $user_id ) );
    }

    public function test_delivery_is_logged_only_when_enabled_and_privacy_tools_work(): void {
        $user = self::factory()->user->create( [ 'role' => 'customer' ] );
        $product = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        $file = self::factory()->post->create( [ 'post_type' => 'attachment', 'post_title' => 'Logged drawing', 'post_status' => 'inherit' ] );
        $path = wp_upload_dir()['basedir'] . '/jps-cs-protected/test-log-' . $file . '.step';
        wp_mkdir_p( dirname( $path ) );
        file_put_contents( $path, 'drawing' );
        update_attached_file( $file, $path );
        update_post_meta( $file, '_jps_cs_protected', '1' );
        wp_set_current_user( $user );
        set_query_var( 'jps_cs_file', $file );
        $_GET['product'] = (string) $product;
        $stop_delivery = static function (): string {
            throw new RuntimeException( 'stream' );
        };
        add_filter( 'jps_cs_file_delivery_method', $stop_delivery );
        try {
            update_option( 'jps_cs_files_log_enabled', 'no' );
            try {
                do_action( 'template_redirect' );
                $this->fail( 'Delivery should reach its configured method.' );
            } catch ( RuntimeException $error ) {
                $this->assertSame( 'stream', $error->getMessage() );
            }
            $this->assertSame( 0, $this->rows( $user ) );
            update_option( 'jps_cs_files_log_enabled', 'yes' );
            try {
                do_action( 'template_redirect' );
                $this->fail( 'Delivery should reach its configured method.' );
            } catch ( RuntimeException $error ) {
                $this->assertSame( 'stream', $error->getMessage() );
            }
            $this->assertSame( 1, $this->rows( $user ) );
        } finally {
            remove_filter( 'jps_cs_file_delivery_method', $stop_delivery );
            unset( $_GET['product'] );
            set_query_var( 'jps_cs_file', 0 );
            wp_set_current_user( 0 );
            wp_delete_file( $path );
        }
        $exporters = apply_filters( 'wp_privacy_personal_data_exporters', [] );
        $export = $exporters['jp-catalog-suite-download-log']['callback'];
        $result = call_user_func( $export, get_userdata( $user )->user_email, 1 );
        $this->assertCount( 1, $result['data'] );
        $erasers = apply_filters( 'wp_privacy_personal_data_erasers', [] );
        $erase = $erasers['jp-catalog-suite-download-log']['callback'];
        call_user_func( $erase, get_userdata( $user )->user_email, 1 );
        $this->assertSame( 0, $this->rows( $user ) );
        do_action( 'delete_user', $user );
        $this->assertSame( 0, $this->rows( $user ) );
    }

    public function test_purge_obeys_retention_and_zero_keeps_rows(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'jps_cs_download_log';
        $user = self::factory()->user->create();
        $wpdb->insert( $table, [ 'user_id' => $user, 'attachment_id' => 1, 'product_id' => 0, 'downloaded_at' => gmdate( 'Y-m-d H:i:s', time() - 3 * DAY_IN_SECONDS ) ] );
        $wpdb->insert( $table, [ 'user_id' => $user, 'attachment_id' => 1, 'product_id' => 0, 'downloaded_at' => current_time( 'mysql', true ) ] );
        update_option( 'jps_cs_files_log_retention_days', 1 );
        do_action( 'jps_cs_purge_download_log' );
        $this->assertSame( 1, $this->rows( $user ) );
        update_option( 'jps_cs_files_log_retention_days', 0 );
        do_action( 'jps_cs_purge_download_log' );
        $this->assertSame( 1, $this->rows( $user ) );
    }

    public function test_download_log_page_requires_shop_manager_capability(): void {
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        global $menu, $submenu;
        $menu = [];
        $submenu = [];
        do_action( 'admin_menu' );
        $entry = array_values( array_filter( $submenu['woocommerce'] ?? [], static fn ( array $item ): bool => 'jps-cs-download-log' === $item[2] ) );
        $this->assertCount( 1, $entry );
        $this->assertSame( 'manage_woocommerce', $entry[0][1] );
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'customer' ] ) );
        $this->assertFalse( current_user_can( $entry[0][1] ) );
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'shop_manager' ] ) );
        $this->assertTrue( current_user_can( 'manage_woocommerce' ) );
    }
}
