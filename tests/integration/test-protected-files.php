<?php
/** Protected file integration tests. @package JayPee\CatalogSuite */

declare( strict_types = 1 );

final class Test_Protected_Files extends WP_UnitTestCase {
    public function test_protected_upload_uses_denied_directory_and_endpoint(): void {
        $tmp = wp_tempnam( 'part.stp' );
        file_put_contents( $tmp, 'STEP fixture' );
        $upload = [ 'name' => 'part.stp', 'type' => 'application/step', 'tmp_name' => $tmp, 'error' => 0, 'size' => filesize( $tmp ) ];
        $result = wp_handle_sideload( $upload, [ 'test_form' => false, 'test_type' => true ] );
        $this->assertArrayNotHasKey( 'error', $result, isset( $result['error'] ) ? $result['error'] : '' );
        $this->assertStringContainsString( '/jps-cs-protected/', $result['file'] );
        $protected_dir = wp_upload_dir()['basedir'] . '/jps-cs-protected';
        $this->assertStringContainsString( 'Require all denied', file_get_contents( $protected_dir . '/.htaccess' ) );
        $attachment = wp_insert_attachment( [ 'post_mime_type' => 'application/step', 'post_title' => 'part.stp', 'post_status' => 'inherit' ], $result['file'] );
        $this->assertSame( '1', get_post_meta( $attachment, '_jps_cs_protected', true ) );
        $this->assertStringContainsString( 'jps_cs_file=' . $attachment, wp_get_attachment_url( $attachment ) );
        $this->assertSame( [], apply_filters( 'intermediate_image_sizes_advanced', [ 'thumbnail' => [] ], [], $attachment ) );
        $this->assertSame( [], apply_filters( 'fallback_intermediate_image_sizes', [ 'thumbnail' ], [ 'file' => 'jps-cs-protected/part.stp' ] ) );
        $customer = get_user_by( 'login', 'customer1' );
        $customer_id = $customer instanceof WP_User ? $customer->ID : self::factory()->user->create( [ 'user_login' => 'customer1', 'role' => 'customer' ] );
        wp_set_current_user( $customer_id );
        $_GET['product'] = '999';
        $context = [];
        $access = static function ( $allowed, $user_id, $received ) use ( &$context ): bool {
            $context = $received;
            return true;
        };
        add_filter( 'jps_cs_can_access_protected', $access, 10, 3 );
        $delivery = static function (): void {
            throw new RuntimeException( 'delivered' );
        };
        add_action( 'jps_cs_before_file_delivery', $delivery );
        set_query_var( 'jps_cs_file', $attachment );
        try {
            do_action( 'template_redirect' );
            $this->fail( 'Delivery action must interrupt streaming in this test.' );
        } catch ( RuntimeException $error ) {
            $this->assertSame( 'delivered', $error->getMessage() );
        } finally {
            remove_filter( 'jps_cs_can_access_protected', $access, 10 );
            remove_action( 'jps_cs_before_file_delivery', $delivery );
            unset( $_GET['product'] );
            wp_set_current_user( 0 );
        }
        $this->assertSame( [ 'attachment_id' => $attachment ], $context );
    }

    public function test_endpoint_redirects_unprotected_attachment_and_404s_missing_id(): void {
        $attachment = wp_insert_attachment( [ 'post_mime_type' => 'text/plain', 'post_title' => 'public file', 'post_status' => 'inherit' ], __FILE__ );
        set_query_var( 'jps_cs_file', $attachment );
        $redirect = static function ( $location, $status ) {
            throw new RuntimeException( $location . '|' . $status );
        };
        add_filter( 'wp_redirect', $redirect, 10, 2 );
        try {
            do_action( 'template_redirect' );
            $this->fail( 'Unprotected endpoint must redirect.' );
        } catch ( RuntimeException $error ) {
            $this->assertStringContainsString( wp_get_attachment_url( $attachment ) . '|302', $error->getMessage() );
        } finally {
            remove_filter( 'wp_redirect', $redirect, 10 );
        }
        set_query_var( 'jps_cs_file', PHP_INT_MAX );
        $this->expectException( WPDieException::class );
        do_action( 'template_redirect' );
    }
}
