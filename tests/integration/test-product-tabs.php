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
        $_POST = [ 'jps_cs_tab_nonce' => wp_create_nonce( 'jps_cs_tab_meta' ), 'jps_cs_scope' => 'categories', 'jps_cs_target' => (string) $parent_id, 'jps_cs_priority' => '10' ];
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
        $_POST = [ 'jps_cs_tab_nonce' => wp_create_nonce( 'jps_cs_tab_meta' ), 'jps_cs_scope' => 'products', 'jps_cs_target' => (string) $product_id, 'jps_cs_replaces' => (string) $all_tab, 'jps_cs_priority' => '' ];
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
        $this->assertSame( $all_tab, (int) get_post_meta( $product_tab, '_jps_cs_replaces', true ) );
        $GLOBALS['post'] = get_post( $product_id );
        ob_start();
        do_action( 'woocommerce_product_data_panels' );
        $panel = ob_get_clean();
        $this->assertMatchesRegularExpression( '/name="_jps_cs_hidden_tabs\[\]" value="' . $hidden_tab . '"\s+checked/', $panel );
        $this->assertStringContainsString( 'name="_jps_cs_hidden_tabs[]" value="' . $category_tab . '"', $panel );
        $this->assertStringContainsString( 'jps_cs_scope=products&#038;jps_cs_target=' . $product_id, $panel );
        $this->assertStringContainsString( 'Replaced by Replacing product tab', $panel );
        wp_set_current_user( 0 );
        ob_start();
        do_action( 'woocommerce_product_data_panels' );
        $guest_panel = ob_get_clean();
        $this->assertStringNotContainsString( 'jps_cs_target=' . $product_id, $guest_panel );
    }
    public function test_replacement_inherits_global_priority_even_when_global_is_hidden_first(): void {
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        $product_id = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        $replacement_id = self::factory()->post->create( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'draft', 'post_title' => 'Priority replacement', 'post_content' => 'Replacement content.' ] );
        $global_id = $this->create_tab( 'Priority source', 'Global content.', 'all', [], 13 );
        $_POST = [ 'jps_cs_tab_nonce' => wp_create_nonce( 'jps_cs_tab_meta' ), 'jps_cs_scope' => 'products', 'jps_cs_target' => (string) $product_id, 'jps_cs_replaces' => (string) $global_id, 'jps_cs_priority' => '' ];
        wp_update_post( [ 'ID' => $replacement_id, 'post_status' => 'publish', 'post_date' => '2000-01-01 00:00:00' ] );
        $_POST = [ '_jps_cs_hidden_tabs' => [ (string) $global_id ] ];
        $product = wc_get_product( $product_id );
        do_action( 'woocommerce_admin_process_product_object', $product );
        $product->save();
        $_POST = [];
        $tabs = $this->tabs_for_product( $product_id );
        $this->assertSame( 13, $tabs[ 'jps_cs_tab_' . $replacement_id ]['priority'] );
    }


    public function test_new_tab_editor_preselects_owner_and_prefills_the_replaced_tab(): void {
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        $product_id = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        $global_id = $this->create_tab( 'Global to replace', '<p>Global body.</p>', 'all', [], 20 );
        $_GET = [ 'jps_cs_scope' => 'products', 'jps_cs_target' => (string) $product_id, 'jps_cs_replaces' => (string) $global_id ];
        $tab = get_post( self::factory()->post->create( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'auto-draft' ] ) );
        $GLOBALS['post'] = $tab;
        set_current_screen( 'post' );
        do_action( 'add_meta_boxes_jps_cs_tab', $tab );
        ob_start();
        do_meta_boxes( 'jps_cs_tab', 'side', $tab );
        $markup = ob_get_clean();
        $title = apply_filters( 'default_title', '', $tab );
        $content = apply_filters( 'default_content', '', $tab );
        $_GET = [];
        $this->assertStringContainsString( 'name="jps_cs_scope" value="products"', $markup );
        $this->assertStringContainsString( 'name="jps_cs_target" value="' . $product_id . '"', $markup );
        $this->assertMatchesRegularExpression( '/<option value="' . $global_id . '"\s+selected/', $markup );
        $this->assertSame( 'Global to replace', $title );
        $this->assertSame( '<p>Global body.</p>', $content );
    }

    public function test_saving_ignores_a_missing_owner(): void {
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        $category_id = self::factory()->term->create( [ 'taxonomy' => 'product_cat', 'name' => 'Owner kept' ] );
        $tab_id = $this->create_tab( 'Owned tab', 'Owned content.', 'categories', [ $category_id ], 10 );
        $_POST = [ 'jps_cs_tab_nonce' => wp_create_nonce( 'jps_cs_tab_meta' ), 'jps_cs_scope' => 'products', 'jps_cs_target' => '999999', 'jps_cs_priority' => '10' ];
        wp_update_post( [ 'ID' => $tab_id, 'post_title' => 'Owned tab renamed' ] );
        $_POST = [];
        $this->assertSame( 'categories', get_post_meta( $tab_id, '_jps_cs_scope', true ) );
        $this->assertSame( [ $category_id ], get_post_meta( $tab_id, '_jps_cs_categories', true ) );
    }

    public function test_category_screen_lists_own_and_inherited_tabs(): void {
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        $parent_id = self::factory()->term->create( [ 'taxonomy' => 'product_cat', 'name' => 'Parent' ] );
        $child_id = self::factory()->term->create( [ 'taxonomy' => 'product_cat', 'name' => 'Child', 'parent' => $parent_id ] );
        $this->create_tab( 'Child own tab', 'Child content.', 'categories', [ $child_id ], 10 );
        $this->create_tab( 'Parent inherited tab', 'Parent content.', 'categories', [ $parent_id ], 20 );
        ob_start();
        do_action( 'product_cat_edit_form_fields', get_term( $child_id, 'product_cat' ), 'product_cat' );
        $markup = ob_get_clean();
        $this->assertStringContainsString( 'Child own tab', $markup );
        $this->assertStringContainsString( 'Parent category: Parent', $markup );
        $this->assertStringContainsString( 'Parent inherited tab', $markup );
        $this->assertStringContainsString( 'jps_cs_scope=categories&#038;jps_cs_target=' . $child_id, $markup );
    }

    public function test_trash_endpoint_requires_delete_capability(): void {
        add_filter( 'wp_doing_ajax', '__return_true' );
        add_filter(
            'wp_die_ajax_handler',
            static fn (): callable => static function ( $message = '' ): void {
                throw new WPDieException( is_scalar( $message ) ? (string) $message : '' );
            }
        );
        $tab_id = $this->create_tab( 'Tab to trash', 'Content.', 'all', [], 10 );
        $trash = function () use ( $tab_id ): void {
            $_POST = [ 'nonce' => wp_create_nonce( 'jps_cs_tab_manager' ), 'tab_id' => (string) $tab_id ];
            $_REQUEST = $_POST;
            ob_start();
            try {
                do_action( 'wp_ajax_jps_cs_trash_tab' );
            } catch ( WPDieException $exception ) {
                unset( $exception );
            } finally {
                ob_end_clean();
                $_POST = [];
                $_REQUEST = [];
            }
        };
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'customer' ] ) );
        $trash();
        $this->assertSame( 'publish', get_post_status( $tab_id ) );
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        $trash();
        $this->assertSame( 'trash', get_post_status( $tab_id ) );
    }

    public function test_migration_splits_multi_owner_tabs_without_duplicates(): void {
        delete_option( 'jps_cs_tabs_single_owner_migrated' );
        $category_a = self::factory()->term->create( [ 'taxonomy' => 'product_cat', 'name' => 'Split A' ] );
        $category_b = self::factory()->term->create( [ 'taxonomy' => 'product_cat', 'name' => 'Split B' ] );
        $in_both = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        $in_b_only = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        $in_both_plain = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        wp_set_object_terms( $in_both, [ $category_a, $category_b ], 'product_cat' );
        wp_set_object_terms( $in_both_plain, [ $category_a, $category_b ], 'product_cat' );
        wp_set_object_terms( $in_b_only, [ $category_b ], 'product_cat' );
        $split_id = $this->create_tab( 'Split me', 'Split body.', 'categories', [ $category_a, $category_b ], 10 );
        $replacing_id = $this->create_tab( 'Replacing split', 'Replacement body.', 'products', [], 20 );
        update_post_meta( $replacing_id, '_jps_cs_products', [ $in_both, $in_b_only ] );
        update_post_meta( $replacing_id, '_jps_cs_replaces', $split_id );
        // Firing admin_init outside wp-admin also runs core's privacy-policy registration, which flags that.
        $this->setExpectedIncorrectUsage( 'wp_add_privacy_policy_content' );
        remove_action( 'admin_init', 'wp_admin_headers' );
        do_action( 'admin_init' );
        $copies = get_posts( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'publish', 'title' => 'Split me', 'exclude' => [ $split_id ], 'numberposts' => -1 ] );
        $this->assertCount( 1, $copies );
        $copy_id = $copies[0]->ID;
        $this->assertSame( [ $category_a ], get_post_meta( $split_id, '_jps_cs_categories', true ) );
        $this->assertSame( [ $category_b ], get_post_meta( $copy_id, '_jps_cs_categories', true ) );
        $this->assertSame( 'Split body.', $copies[0]->post_content );
        $replacement_copies = get_posts( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'publish', 'title' => 'Replacing split', 'exclude' => [ $replacing_id ], 'numberposts' => -1 ] );
        $this->assertCount( 1, $replacement_copies );
        $replacement_copy_id = $replacement_copies[0]->ID;
        $this->assertSame( [ $in_both ], get_post_meta( $replacing_id, '_jps_cs_products', true ) );
        $this->assertSame( [ $in_b_only ], get_post_meta( $replacement_copy_id, '_jps_cs_products', true ) );
        $this->assertSame( $split_id, (int) get_post_meta( $replacing_id, '_jps_cs_replaces', true ) );
        $this->assertSame( $copy_id, (int) get_post_meta( $replacement_copy_id, '_jps_cs_replaces', true ) );
        $this->assertSame( [ 'jps_cs_tab_' . $split_id ], $this->tab_keys( $this->tabs_for_product( $in_both_plain ) ) );
        $this->assertSame( [ $copy_id ], get_post_meta( $in_both_plain, '_jps_cs_hidden_tabs', true ) );
        $this->assertSame( [ 'jps_cs_tab_' . $replacing_id ], $this->tab_keys( $this->tabs_for_product( $in_both ) ) );
        $this->assertSame( [ 'jps_cs_tab_' . $replacement_copy_id ], $this->tab_keys( $this->tabs_for_product( $in_b_only ) ) );
        $tab_count = count( get_posts( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ] ) );
        do_action( 'admin_init' );
        $this->assertCount( $tab_count, get_posts( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ] ) );
    }

    public function test_deleting_a_product_or_category_deletes_its_tabs(): void {
        $product_id = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        $category_id = self::factory()->term->create( [ 'taxonomy' => 'product_cat', 'name' => 'Deleted with tabs' ] );
        $product_tab = $this->create_tab( 'Product-owned tab', 'Content.', 'products', [], 10 );
        update_post_meta( $product_tab, '_jps_cs_products', [ $product_id ] );
        $category_tab = $this->create_tab( 'Category-owned tab', 'Content.', 'categories', [ $category_id ], 10 );
        $shop_wide_tab = $this->create_tab( 'Shop-wide tab', 'Content.', 'all', [], 10 );
        wp_delete_post( $product_id, true );
        wp_delete_term( $category_id, 'product_cat' );
        $this->assertNull( get_post( $product_tab ) );
        $this->assertNull( get_post( $category_tab ) );
        $this->assertNotNull( get_post( $shop_wide_tab ) );
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
    public function test_core_tab_settings_are_saved_and_applied_without_replacing_core_tabs(): void {
        $product_id = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        $original = $this->tabs_for_product( $product_id, true );
        $this->assertSame( 'Description', $original['description']['title'] );
        $this->assertSame( 'Additional information', $original['additional_information']['title'] );
        $this->assertStringStartsWith( 'Reviews', $original['reviews']['title'] );
        $this->assertSame( 10, $original['description']['priority'] );
        $this->assertSame( 20, $original['additional_information']['priority'] );
        $this->assertSame( 30, $original['reviews']['priority'] );

        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        global $current_tab, $current_section;
        $current_tab = 'jps_cs';
        $current_section = 'tabs';
        if ( ! class_exists( 'WC_Admin_Settings' ) ) {
            require_once WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';
        }
        $_POST = [
            'jps_cs_tabs_core_description_title' => 'Overview',
            'jps_cs_tabs_core_description_priority' => '',
            'jps_cs_tabs_core_additional_information_title' => '',
            'jps_cs_tabs_core_additional_information_priority' => '',
            'jps_cs_tabs_core_additional_information_hidden' => '1',
            'jps_cs_tabs_core_reviews_title' => '',
            'jps_cs_tabs_core_reviews_priority' => '5',
            '_wpnonce' => wp_create_nonce( 'woocommerce-settings' ),
        ];
        $_REQUEST = $_POST;
        \WC_Admin_Settings::save();

        $custom_tab_id = $this->create_tab( 'Core collision', 'Content', 'all', [], 1 );
        $tabs = $this->tabs_for_product( $product_id, true );
        $this->assertArrayNotHasKey( 'additional_information', $tabs );
        $this->assertSame( 5, $tabs['reviews']['priority'] );
        $ordered_tabs = $tabs;
        uasort( $ordered_tabs, static fn ( array $left, array $right ): int => $left['priority'] <=> $right['priority'] );
        $ordered_keys = array_keys( $ordered_tabs );
        $this->assertLessThan( array_search( 'description', $ordered_keys, true ), array_search( 'reviews', $ordered_keys, true ) );
        $this->assertArrayHasKey( 'jps_cs_tab_' . $custom_tab_id, $tabs );
    }

    public function test_whitespace_only_tab_content_is_not_rendered(): void {
        $product_id = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        $tab_id = $this->create_tab( 'Blank information', '<p>   </p>', 'all', [], 50 );
        $this->assertArrayNotHasKey( 'jps_cs_tab_' . $tab_id, $this->tabs_for_product( $product_id ) );
    }
    public function test_tab_output_preserves_editor_embeds(): void {
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        $product_id = self::factory()->post->create( [ 'post_type' => 'product', 'post_status' => 'publish' ] );
        $tab_id = $this->create_tab( 'Embedded tab', '<iframe src="https://example.com/embed"></iframe>', 'all', [], 50 );
        $GLOBALS['post'] = get_post( $product_id );
        setup_postdata( $GLOBALS['post'] );
        $tabs = apply_filters( 'woocommerce_product_tabs', [], wc_get_product( $product_id ) );
        ob_start();
        $tabs[ 'jps_cs_tab_' . $tab_id ]['callback']();
        $output = ob_get_clean();
        wp_reset_postdata();
        $this->assertStringContainsString( '<iframe src="https://example.com/embed">', $output );
    }

    public function test_product_editor_tabs_filter_adds_the_reachable_panel(): void {
        $tabs = apply_filters( 'woocommerce_product_data_tabs', [] );
        $this->assertSame( 'jps-cs-product-tabs', $tabs['jps_cs_tabs']['target'] );
    }

    public function test_logged_in_customer_cannot_read_tab_or_revisions_through_rest(): void {
        $tab_id = $this->create_tab( 'Private customer REST check', 'Secret customer tab body.', 'all', [], 50 );
        $customer_id = self::factory()->user->create( [ 'role' => 'customer' ] );
        wp_set_current_user( $customer_id );
        foreach ( [ '/wp/v2/jps_cs_tab/' . $tab_id, '/wp/v2/jps_cs_tab/' . $tab_id . '/revisions', '/wp/v2/JPS_CS_TAB/' . $tab_id ] as $route ) {
            $response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', $route ) );
            $data = $response->get_data();
            $this->assertGreaterThanOrEqual( 400, $response->get_status(), $route );
            $this->assertStringNotContainsString( 'Secret customer tab body.', wp_json_encode( $data ) );
        }
    }


    public function test_guest_cannot_read_tab_content_through_rest(): void {
        wp_set_current_user( 0 );
        $tab_id = $this->create_tab( 'Private REST check', 'Secret tab body.', 'all', [], 50 );
        $request = new WP_REST_Request( 'GET', '/wp/v2/jps_cs_tab/' . $tab_id );
        $this->assertFalse( is_user_logged_in() );
        $this->assertFalse( current_user_can( 'edit_posts' ) );
        $this->assertSame( '/wp/v2/jps_cs_tab/' . $tab_id, $request->get_route() );
        $response = rest_get_server()->dispatch( $request );
        $data = $response->get_data();
        $this->assertTrue( $response->get_status() >= 400 || ! str_contains( $data['content']['rendered'] ?? '', 'Secret tab body.' ) );
    }

    private function tabs_for_product( int $product_id, bool $include_core = false ): array {
        $GLOBALS['product'] = wc_get_product( $product_id );
        $GLOBALS['post'] = get_post( $product_id );
        setup_postdata( $GLOBALS['post'] );
        $tabs = apply_filters(
            'woocommerce_product_tabs',
            $include_core ? [
                'description' => [ 'title' => 'Description', 'priority' => 10 ],
                'additional_information' => [ 'title' => 'Additional information', 'priority' => 20 ],
                'reviews' => [ 'title' => 'Reviews', 'priority' => 30 ],
            ] : [],
            $GLOBALS['product']
        );
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
