<?php
/**
 * Integration tests for product tabs.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

final class Test_Product_Tabs extends WP_UnitTestCase {

    public function test_global_and_parent_category_tabs_are_ordered_for_child_category_product(): void {
        $parent_id = self::factory()->term->create( [ 'taxonomy' => 'product_cat', 'name' => 'Parent category' ] );
        $child_id = self::factory()->term->create( [ 'taxonomy' => 'product_cat', 'name' => 'Child category', 'parent' => $parent_id ] );
        $product_id = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        wp_set_object_terms( $product_id, [ $child_id ], 'product_cat' );
        $global_id = $this->create_tab( 'Global information', 'Public global text.', 'all', [], 20 );
        $category_id = $this->create_tab( 'Category information', 'Public category text.', 'categories', [ $parent_id ], 10 );
        $tabs = $this->tabs_for_product( $product_id );
        $this->assertSame( [ 'jps_cs_tab_' . $category_id, 'jps_cs_tab_' . $global_id ], $this->tab_keys( $tabs ) );
    }

    public function test_tab_settings_saved_by_core_control_category_scope_and_priority(): void {
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        $parent_id = self::factory()->term->create( [ 'taxonomy' => 'product_cat', 'name' => 'Saved scope parent' ] );
        $child_id = self::factory()->term->create( [ 'taxonomy' => 'product_cat', 'name' => 'Saved scope child', 'parent' => $parent_id ] );
        $product_id = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        wp_set_object_terms( $product_id, [ $child_id ], 'product_cat' );
        $global_id = $this->create_tab( 'Global saved test', 'Global public content.', 'all', [], 20 );
        $tab_id = self::factory()->post->create( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'draft', 'post_title' => 'Saved category tab', 'post_content' => 'Category public content.' ] );
        $_POST = [ 'jps_cs_tab_nonce' => wp_create_nonce( 'jps_cs_tab_meta' ), 'jps_cs_scope' => 'categories', 'jps_cs_categories' => [ (string) $parent_id ], 'jps_cs_priority' => '10', 'jps_cs_products' => [] ];
        wp_update_post( [ 'ID' => $tab_id, 'post_status' => 'publish' ] );
        $_POST = [];
        $this->assertSame( [ 'jps_cs_tab_' . $tab_id, 'jps_cs_tab_' . $global_id ], $this->tab_keys( $this->tabs_for_product( $product_id ) ) );
        $this->assertSame( 'categories', get_post_meta( $tab_id, '_jps_cs_scope', true ) );
        $this->assertSame( [ $parent_id ], get_post_meta( $tab_id, '_jps_cs_categories', true ) );
        $this->assertSame( '10', get_post_meta( $tab_id, '_jps_cs_priority', true ) );
    }

    public function test_product_scoped_replacement_and_hidden_tabs_are_saved_and_applied(): void {
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        $product_id = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        $other_product_id = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        $all_tab = $this->create_tab( 'All products tab for override', 'All content.', 'all', [], 10 );
        $category_id = self::factory()->term->create( [ 'taxonomy' => 'product_cat', 'name' => 'Override category' ] );
        wp_set_object_terms( $product_id, [ $category_id ], 'product_cat' );
        wp_set_object_terms( $other_product_id, [ $category_id ], 'product_cat' );
        $category_tab = $this->create_tab( 'Category tab for override', 'Category content.', 'categories', [ $category_id ], 20 );
        $hidden_tab = $this->create_tab( 'Hidden global tab', 'Hidden content.', 'all', [], 30 );
        $product_tab = self::factory()->post->create( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'draft', 'post_title' => 'Replacing product tab', 'post_content' => 'Replacement content.' ] );
        $_POST = [ 'jps_cs_tab_nonce' => wp_create_nonce( 'jps_cs_tab_meta' ), 'jps_cs_scope' => 'products', 'jps_cs_products' => [ (string) $product_id ], 'jps_cs_replace_tab' => (string) $all_tab, 'jps_cs_priority' => '' ];
        wp_update_post( [ 'ID' => $product_tab, 'post_status' => 'publish' ] );
        $_POST = [];
        $product = wc_get_product( $product_id );
        $_POST = [ '_jps_cs_hidden_tabs' => [ (string) $hidden_tab ] ];
        do_action( 'woocommerce_admin_process_product_object', $product );
        $product->save();
        $_POST = [];
        $this->assertSame( [ 'jps_cs_tab_' . $product_tab, 'jps_cs_tab_' . $category_tab ], $this->tab_keys( $this->tabs_for_product( $product_id ) ) );
        $this->assertSame( [ 'jps_cs_tab_' . $all_tab, 'jps_cs_tab_' . $category_tab, 'jps_cs_tab_' . $hidden_tab ], $this->tab_keys( $this->tabs_for_product( $other_product_id ) ) );
        $this->assertSame( [ $hidden_tab ], get_post_meta( $product_id, '_jps_cs_hidden_tabs', true ) );
        $this->assertSame( 'products', get_post_meta( $product_tab, '_jps_cs_scope', true ) );
        $this->assertSame( [ $product_id ], get_post_meta( $product_tab, '_jps_cs_products', true ) );
        $this->assertSame( $all_tab, (int) get_post_meta( $product_tab, '_jps_cs_replace_tab', true ) );
        $GLOBALS['post'] = get_post( $product_id );
        ob_start();
        do_action( 'woocommerce_product_data_panels' );
        $panel = ob_get_clean();
        $this->assertMatchesRegularExpression( '/name="_jps_cs_hidden_tabs\[\]" value="' . $hidden_tab . '"\s+checked/', $panel );
    }

    public function test_product_tab_creation_link_and_editor_preselect_product_scope(): void {
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        $product_id = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        $GLOBALS['post'] = get_post( $product_id );
        ob_start();
        do_action( 'woocommerce_product_data_panels' );
        $panel = ob_get_clean();
        $this->assertStringContainsString( 'post-new.php?post_type=jps_cs_tab&#038;jps_cs_product=' . $product_id, $panel );
        $_GET['jps_cs_product'] = (string) $product_id;
        $tab = get_post( self::factory()->post->create( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'draft' ] ) );
        $GLOBALS['post'] = $tab;
        set_current_screen( 'post' );
        do_action( 'add_meta_boxes_jps_cs_tab', $tab );
        ob_start();
        do_meta_boxes( 'jps_cs_tab', 'side', $tab );
        $markup = ob_get_clean();
        unset( $_GET['jps_cs_product'] );
        $this->assertMatchesRegularExpression( '/<option value="products"\s+selected(?:="selected")?/', $markup );
        $this->assertMatchesRegularExpression( '/<option value="' . $product_id . '"\s+selected/', $markup );
    }

    public function test_tab_settings_meta_box_is_registered(): void {
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        $tab = get_post( self::factory()->post->create( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'draft' ] ) );
        set_current_screen( 'post' );
        do_action( 'add_meta_boxes_jps_cs_tab', $tab );
        $this->assertArrayHasKey( 'jps_cs_tab_settings', $GLOBALS['wp_meta_boxes']['jps_cs_tab']['side']['default'] ?? [] );
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
        $this->assertArrayNotHasKey( 'jps_cs_tab_' . $tab_id, $this->tabs_for_product( $product_id ) );
    }

    public function test_guest_cannot_read_tab_content_through_rest(): void {
        wp_set_current_user( 0 );
        $tab_id = $this->create_tab( 'Private REST check', 'Secret tab body.', 'all', [], 50 );
        $response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/wp/v2/jps_cs_tab/' . $tab_id ) );
        $data = $response->get_data();
        $this->assertTrue( $response->get_status() >= 400 || ! str_contains( $data['content']['rendered'] ?? '', 'Secret tab body.' ) );
    }

    private function tabs_for_product( int $product_id ): array {
        $GLOBALS['product'] = wc_get_product( $product_id );
        $GLOBALS['post'] = get_post( $product_id );
        setup_postdata( $GLOBALS['post'] );
        $tabs = apply_filters( 'woocommerce_product_tabs', [], $GLOBALS['product'] );
        wp_reset_postdata();
        return $tabs;
    }

    private function tab_keys( array $tabs ): array {
        return array_values( array_filter( array_keys( $tabs ), static fn ( string $key ): bool => str_starts_with( $key, 'jps_cs_tab_' ) ) );
    }

    private function create_tab( string $title, string $content, string $scope, array $categories, int $priority ): int {
        $id = self::factory()->post->create( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'publish', 'post_title' => $title, 'post_content' => $content ] );
        update_post_meta( $id, '_jps_cs_scope', $scope );
        update_post_meta( $id, '_jps_cs_categories', $categories );
        update_post_meta( $id, '_jps_cs_priority', (string) $priority );
        return $id;
    }
}
