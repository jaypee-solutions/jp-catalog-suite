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
        $this->assertSame( [ $result['file'] ], glob( dirname( $result['file'] ) . '/*' ) );
        $this->assertStringContainsString( '/jps-cs-protected/', $result['file'] );
        $protected_dir = wp_upload_dir()['basedir'] . '/jps-cs-protected';
        $this->assertStringContainsString( 'Require all denied', file_get_contents( $protected_dir . '/.htaccess' ) );
        $attachment = wp_insert_attachment( [ 'post_mime_type' => 'application/step', 'post_title' => 'part.stp', 'post_status' => 'inherit' ], $result['file'] );
        $this->assertSame( '1', get_post_meta( $attachment, '_jps_cs_protected', true ) );
        $this->assertStringContainsString( 'jps_cs_file=' . $attachment, wp_get_attachment_url( $attachment ) );
        $this->assertStringContainsString( 'jps_cs_file=' . $attachment, get_the_guid( $attachment ) );
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
        $this->assertTrue( apply_filters( 'pre_handle_404', false, $GLOBALS['wp_query'] ) );
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
    public function test_media_editor_protects_and_unprotects_non_image_attachment(): void {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        $uploads = wp_upload_dir();
        $directory = $uploads['basedir'] . '/2026/11';
        wp_mkdir_p( $directory );
        $source = $directory . '/toggle.pdf';
        file_put_contents( $source, 'PDF fixture' );
        $size = $directory . '/toggle-150x150.pdf';
        file_put_contents( $size, 'Intermediate size' );
        $attachment = wp_insert_attachment(
            [ 'post_mime_type' => 'application/pdf', 'post_title' => 'Toggle PDF', 'post_status' => 'inherit' ],
            $source
        );
        wp_update_attachment_metadata(
            $attachment,
            [ 'file' => '2026/11/toggle.pdf', 'sizes' => [ 'thumbnail' => [ 'file' => 'toggle-150x150.pdf' ] ] ]
        );
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

        $errors = $this->save_media_attachment( $attachment, [ 'jps_cs_protected' => '1' ] );
        $protected = get_attached_file( $attachment );
        $this->assertNull( $errors );
        $this->assertSame( '1', get_post_meta( $attachment, '_jps_cs_protected', true ) );
        $this->assertFileExists( $protected );
        $this->assertFileDoesNotExist( $source );
        $this->assertFileDoesNotExist( $size );
        $this->assertStringContainsString( 'jps_cs_file=' . $attachment, wp_get_attachment_url( $attachment ) );
        $this->assertSame( [], wp_get_attachment_metadata( $attachment )['sizes'] );

        file_put_contents( $source, 'Existing public file' );
        $errors = $this->save_media_attachment( $attachment, [] );
        $this->assertNull( $errors );
        $public = $uploads['basedir'] . '/2026/11/toggle-1.pdf';
        $this->assertNotSame( '1', get_post_meta( $attachment, '_jps_cs_protected', true ) );
        $this->assertFileExists( $public );
        $this->assertSame( 'Existing public file', file_get_contents( $source ) );
        $this->assertSame( 'PDF fixture', file_get_contents( $public ) );
        $this->assertFileDoesNotExist( $protected );
        $this->assertSame( $uploads['baseurl'] . '/2026/11/toggle-1.pdf', wp_get_attachment_url( $attachment ) );
        set_query_var( 'jps_cs_file', $attachment );
        $redirect = static function ( $location, $status ) {
            throw new RuntimeException( $location . '|' . $status );
        };
        add_filter( 'wp_redirect', $redirect, 10, 2 );
        try {
            do_action( 'template_redirect' );
            $this->fail( 'A stored endpoint link must redirect after unprotection.' );
        } catch ( RuntimeException $error ) {
            $this->assertSame( $uploads['baseurl'] . '/2026/11/toggle-1.pdf|302', $error->getMessage() );
        } finally {
            remove_filter( 'wp_redirect', $redirect, 10 );
            set_query_var( 'jps_cs_file', 0 );
        }
        $_POST = [];
        wp_set_current_user( 0 );
    }
    public function test_failed_protection_move_preserves_file_sizes_and_state(): void {
        $attachment = self::factory()->post->create( [ 'post_type' => 'attachment', 'post_mime_type' => 'application/pdf', 'post_title' => 'Cannot move PDF', 'post_status' => 'inherit' ] );
        $source = wp_tempnam( 'cannot-move.pdf' );
        file_put_contents( $source, 'PDF fixture' );
        $size = dirname( $source ) . '/cannot-move-150x150.pdf';
        file_put_contents( $size, 'Intermediate size' );
        update_attached_file( $attachment, $source );
        wp_update_attachment_metadata(
            $attachment,
            [ 'file' => '2026/11/cannot-move.pdf', 'sizes' => [ 'thumbnail' => [ 'file' => basename( $size ) ] ] ]
        );
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        $errors = $this->save_media_attachment( $attachment, [ 'jps_cs_protected' => '1' ] );
        $this->assertFileExists( $source );
        $this->assertFileExists( $size );
        $this->assertSame( $source, get_attached_file( $attachment ) );
        $this->assertNotSame( '1', get_post_meta( $attachment, '_jps_cs_protected', true ) );
        $this->assertSame( [ 'thumbnail' => [ 'file' => basename( $size ) ] ], wp_get_attachment_metadata( $attachment )['sizes'] );
        $this->assertContains( 'The file could not be moved.', $errors[ $attachment ]['jps_cs_protected']['errors'] );
        unlink( $source );
        unlink( $size );
        $_POST = [];
        wp_set_current_user( 0 );
    }

    public function test_attachment_protection_control_is_non_image_and_edit_permission_only(): void {
        $pdf = wp_insert_attachment(
            [ 'post_mime_type' => 'application/pdf', 'post_title' => 'Editable PDF', 'post_status' => 'inherit' ],
            __FILE__
        );
        $image = wp_insert_attachment(
            [ 'post_mime_type' => 'image/jpeg', 'post_title' => 'Image', 'post_status' => 'inherit' ],
            __FILE__
        );
        $editor = self::factory()->user->create( [ 'role' => 'editor' ] );
        wp_set_current_user( $editor );
        $fields = apply_filters( 'attachment_fields_to_edit', [], get_post( $pdf ) );
        $this->assertArrayHasKey( 'jps_cs_protected', $fields );
        $this->assertSame( 'Protected', $fields['jps_cs_protected']['label'] );
        $this->assertStringContainsString( 'Served only to authorized users', $fields['jps_cs_protected']['html'] );
        $this->assertArrayNotHasKey( 'jps_cs_protected', apply_filters( 'attachment_fields_to_edit', [], get_post( $image ) ) );
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'customer' ] ) );
        $this->assertArrayNotHasKey( 'jps_cs_protected', apply_filters( 'attachment_fields_to_edit', [], get_post( $pdf ) ) );
        wp_set_current_user( 0 );
    }


    public function test_product_context_file_request_preempts_wordpress_404(): void {
        $query = new WP_Query();
        $query->set( 'jps_cs_file', 123 );
        $query->set( 'product', '999' );
        $this->assertTrue( apply_filters( 'pre_handle_404', false, $query ) );
    }
    public function test_endpoint_redirects_unprotected_attachment_and_404s_missing_id(): void {
        $attachment = wp_insert_attachment( [ 'post_mime_type' => 'text/plain', 'post_title' => 'public file', 'post_status' => 'inherit' ], __FILE__ );
        update_post_meta( $attachment, '_jps_cs_was_protected', '1' );
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
        delete_post_meta( $attachment, '_jps_cs_was_protected' );
        set_query_var( 'jps_cs_file', $attachment );
        try {
            do_action( 'template_redirect' );
            $this->fail( 'An unrelated attachment must not disclose its URL through the endpoint.' );
        } catch ( WPDieException $error ) {
            $this->assertStringContainsString( 'File not found.', $error->getMessage() );
        }
        set_query_var( 'jps_cs_file', PHP_INT_MAX );
        $this->expectException( WPDieException::class );
        do_action( 'template_redirect' );
    }
    public function test_protected_download_returns_forbidden_when_access_is_denied(): void {
        $attachment = wp_insert_attachment( [ 'post_mime_type' => 'application/step', 'post_title' => 'private file', 'post_status' => 'inherit' ], __FILE__ );
        update_post_meta( $attachment, '_jps_cs_protected', '1' );
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'customer' ] ) );
        set_query_var( 'jps_cs_file', $attachment );
        $deny = static fn (): bool => false;
        add_filter( 'jps_cs_can_access_protected', $deny );
        try {
            do_action( 'template_redirect' );
            $this->fail( 'An unauthorized download must return 403.' );
        } catch ( WPDieException $error ) {
            $this->assertStringContainsString( 'You do not have permission to download this file.', $error->getMessage() );
        } finally {
            remove_filter( 'jps_cs_can_access_protected', $deny );
            wp_set_current_user( 0 );
        }
    }
    public function test_guest_endpoint_redirects_to_login_with_a_subdirectory_safe_endpoint(): void {
        $attachment = wp_insert_attachment( [ 'post_mime_type' => 'application/step', 'post_title' => 'login file', 'post_status' => 'inherit' ], __FILE__ );
        update_post_meta( $attachment, '_jps_cs_protected', '1' );
        $request_uri = $_SERVER['REQUEST_URI'] ?? null;
        $_SERVER['REQUEST_URI'] = '/blog/?jps_cs_file=' . $attachment;
        set_query_var( 'jps_cs_file', $attachment );
        $redirect = static function ( $location, $status ) {
            throw new RuntimeException( $location . '|' . $status );
        };
        add_filter( 'wp_redirect', $redirect, 10, 2 );
        try {
            do_action( 'template_redirect' );
            $this->fail( 'A guest endpoint request must redirect to login.' );
        } catch ( RuntimeException $error ) {
            $this->assertStringContainsString( 'wp-login.php', $error->getMessage() );
            $this->assertStringContainsString( rawurlencode( add_query_arg( 'jps_cs_file', $attachment, home_url( '/' ) ) ), $error->getMessage() );
        } finally {
            remove_filter( 'wp_redirect', $redirect, 10 );
            if ( null === $request_uri ) {
                unset( $_SERVER['REQUEST_URI'] );
            } else {
                $_SERVER['REQUEST_URI'] = $request_uri;
            }
            set_query_var( 'jps_cs_file', 0 );
        }
    }

    public function test_mime_settings_reject_code_extensions_and_restrict_editing(): void {
        $previous = get_option( 'jps_cs_files_mimes', null );
        update_option( 'jps_cs_files_mimes', "php=text/plain\nstep=application/step" );
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        $sanitized = apply_filters( 'woocommerce_admin_settings_sanitize_option_jps_cs_files_mimes', "php=text/plain\nstep=application/step", 'jps_cs_files_mimes', [] );
        $this->assertSame( 'step=application/step', $sanitized );
        $this->assertArrayNotHasKey( 'php', apply_filters( 'upload_mimes', [] ) );
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'shop_manager' ] ) );
        $this->assertSame( "php=text/plain\nstep=application/step", apply_filters( 'woocommerce_admin_settings_sanitize_option_jps_cs_files_mimes', "php=text/plain", 'jps_cs_files_mimes', [] ) );
        if ( null === $previous ) {
            delete_option( 'jps_cs_files_mimes' );
        } else {
            update_option( 'jps_cs_files_mimes', $previous );
        }
        wp_set_current_user( 0 );
    }

    public function test_guest_rest_media_response_hides_protected_attachments(): void {
        $attachment = wp_insert_attachment( [ 'post_mime_type' => 'application/step', 'post_title' => 'REST secret', 'post_status' => 'inherit' ], __FILE__ );
        update_post_meta( $attachment, '_jps_cs_protected', '1' );
        wp_set_current_user( 0 );
        $request = new WP_REST_Request( 'GET', '/wp/v2/media/' . $attachment );
        $response = rest_get_server()->dispatch( $request );
        $this->assertGreaterThanOrEqual( 400, $response->get_status() );
    }
    private function save_media_attachment( int $id, array $fields ): ?array {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        $_POST = [
            '_wpnonce' => wp_create_nonce( 'media-form' ),
            'attachments' => [ $id => $fields ],
        ];
        $_REQUEST = $_POST;
        return media_upload_form_handler();
    }
}
