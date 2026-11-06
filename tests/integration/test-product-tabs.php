<?php
/**
 * Integration tests for global product tabs.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

final class Test_Product_Tabs extends WP_UnitTestCase {

    public function test_global_and_parent_category_tabs_are_ordered_for_child_category_product(): void {
        $parent_id = self::factory()->term->create( [ 'taxonomy' => 'product_cat', 'name' => 'Parent category' ] );
        $child_id  = self::factory()->term->create( [ 'taxonomy' => 'product_cat', 'name' => 'Child category', 'parent' => $parent_id ] );
        $product_id = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        wp_set_object_terms( $product_id, [ $child_id ], 'product_cat' );
        $global_id = $this->create_tab( 'Global information', 'Public global text.', 'all', [], 20 );
        $category_id = $this->create_tab( 'Category information', 'Public category text.', 'categories', [ $parent_id ], 10 );
        $product_post = get_post( $product_id );
        $GLOBALS['product'] = wc_get_product( $product_id );
        $GLOBALS['post'] = $product_post;
        setup_postdata( $product_post );
        $tabs = apply_filters( 'woocommerce_product_tabs', [], $GLOBALS['product'] );
        wp_reset_postdata();

        $tab_keys = array_values( array_filter( array_keys( $tabs ), static fn ( string $key ): bool => str_starts_with( $key, 'jps_cs_tab_' ) ) );
        $this->assertSame( [ 'jps_cs_tab_' . $category_id, 'jps_cs_tab_' . $global_id ], $tab_keys );
    }

    public function test_tab_settings_saved_by_core_control_category_scope_and_priority(): void {
        $administrator_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
        wp_set_current_user( $administrator_id );
        $parent_id = self::factory()->term->create( [ 'taxonomy' => 'product_cat', 'name' => 'Saved scope parent' ] );
        $child_id = self::factory()->term->create( [ 'taxonomy' => 'product_cat', 'name' => 'Saved scope child', 'parent' => $parent_id ] );
        $product_id = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        wp_set_object_terms( $product_id, [ $child_id ], 'product_cat' );
        $global_id = $this->create_tab( 'Global saved test', 'Global public content.', 'all', [], 20 );
        $tab_id = self::factory()->post->create( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'draft', 'post_title' => 'Saved category tab', 'post_content' => 'Category public content.' ] );
        $_POST = [
            'jps_cs_tab_nonce' => wp_create_nonce( 'jps_cs_tab_meta' ),
            'jps_cs_scope' => 'categories',
            'jps_cs_categories' => [ (string) $parent_id ],
            'jps_cs_priority' => '10',
        ];
        wp_update_post( [ 'ID' => $tab_id, 'post_status' => 'publish' ] );
        $_POST = [];

        $product_post = get_post( $product_id );
        $GLOBALS['product'] = wc_get_product( $product_id );
        $GLOBALS['post'] = $product_post;
        setup_postdata( $product_post );
        $tabs = apply_filters( 'woocommerce_product_tabs', [], $GLOBALS['product'] );
        wp_reset_postdata();
        $tab_keys = array_values( array_filter( array_keys( $tabs ), static fn ( string $key ): bool => str_starts_with( $key, 'jps_cs_tab_' ) ) );

        $this->assertSame( [ 'jps_cs_tab_' . $tab_id, 'jps_cs_tab_' . $global_id ], $tab_keys );
        $this->assertSame( 'categories', get_post_meta( $tab_id, '_jps_cs_scope', true ) );
        $this->assertSame( [ $parent_id ], get_post_meta( $tab_id, '_jps_cs_categories', true ) );
        $this->assertSame( '10', get_post_meta( $tab_id, '_jps_cs_priority', true ) );
    }

    public function test_product_tabs_settings_section_is_reachable(): void {
        $pages = apply_filters( 'woocommerce_get_settings_pages', [] );
        $page = current( array_filter( $pages, static fn ( $candidate ): bool => 'jps_cs' === $candidate->get_id() ) );

        $this->assertNotFalse( $page );
        $this->assertNotEmpty( $page->get_settings_for_section( 'tabs' ) );
    }

    public function test_whitespace_only_tab_content_is_not_rendered(): void {
        $product_id = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        $tab_id = $this->create_tab( 'Blank information', '<p>   </p>', 'all', [], 50 );
        $product_post = get_post( $product_id );
        $GLOBALS['product'] = wc_get_product( $product_id );
        $GLOBALS['post'] = $product_post;
        setup_postdata( $product_post );
        $tabs = apply_filters( 'woocommerce_product_tabs', [], $GLOBALS['product'] );
        wp_reset_postdata();
        $this->assertArrayNotHasKey( 'jps_cs_tab_' . $tab_id, $tabs );
    }

    public function test_guest_cannot_read_tab_content_through_rest(): void {
        $tab_id = $this->create_tab( 'Private REST check', 'Secret tab body.', 'all', [], 50 );
        wp_set_current_user( 0 );
        $response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/wp/v2/jps_cs_tab/' . $tab_id ) );
        $data = $response->get_data();

        $this->assertTrue( $response->get_status() >= 400 || ! str_contains( $data['content']['rendered'] ?? '', 'Secret tab body.' ) );
    }

    private function create_tab( string $title, string $content, string $scope, array $categories, int $priority ): int {
        $id = self::factory()->post->create( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'publish', 'post_title' => $title, 'post_content' => $content ] );
        update_post_meta( $id, '_jps_cs_scope', $scope );
        update_post_meta( $id, '_jps_cs_categories', $categories );
        update_post_meta( $id, '_jps_cs_priority', (string) $priority );
        return $id;
    }
}
