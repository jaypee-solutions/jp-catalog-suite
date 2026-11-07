<?php
/**
 * Integration tests for the File list block.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

final class Test_File_List_Block extends WP_UnitTestCase {

    public function test_protected_file_links_require_access_and_include_product_context(): void {
        $product_id = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        $attachment_id = self::factory()->post->create( [ 'post_type' => 'attachment', 'post_title' => 'part.stp', 'post_status' => 'inherit' ] );
        $path = tempnam( sys_get_temp_dir(), 'jps-cs-file-' );
        file_put_contents( $path, 'part data' );
        update_attached_file( $attachment_id, $path );
        update_post_meta( $attachment_id, '_wp_attached_file', $path );
        update_post_meta( $attachment_id, '_jps_cs_protected', '1' );
        $block = '<!-- wp:jp-catalog-suite/file-list {"ids":[' . $attachment_id . ']} /-->';
        $GLOBALS['post'] = get_post( $product_id );
        setup_postdata( $GLOBALS['post'] );

        wp_set_current_user( 0 );
        $guest = do_blocks( $block );
        $this->assertStringContainsString( '(login required)', $guest );
        $this->assertStringNotContainsString( '<a ', $guest );

        $customer_id = self::factory()->user->create( [ 'role' => 'customer' ] );
        wp_set_current_user( $customer_id );
        $customer = do_blocks( $block );
        $this->assertStringContainsString( 'jps_cs_file=' . $attachment_id, $customer );
        $this->assertStringContainsString( 'product=' . $product_id, $customer );

        wp_set_current_user( 0 );
        wp_reset_postdata();
        unlink( $path );
    }
}
