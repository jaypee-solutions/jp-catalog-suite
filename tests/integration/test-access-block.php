<?php
/**
 * Integration tests for the Access block.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

final class Test_Access_Block extends WP_UnitTestCase {

    public function test_protected_content_and_notice_follow_authorization(): void {
        $previous_registration = get_option( 'woocommerce_enable_myaccount_registration', null );
        $previous_wp_registration = get_option( 'users_can_register', null );
        update_option( 'woocommerce_enable_myaccount_registration', 'no' );
        update_option( 'users_can_register', 0 );
        $product_id = $this->create_product();
        $GLOBALS['post'] = get_post( $product_id );
        setup_postdata( $GLOBALS['post'] );
        $block = '<!-- wp:jp-catalog-suite/access {"audience":"authorized"} --><p>Protected text</p><!-- /wp:jp-catalog-suite/access -->';
        wp_set_current_user( 0 );
        $guest = do_blocks( $block );
        $this->assertStringNotContainsString( 'Protected text', $guest );
        $this->assertStringContainsString( 'redirect_to=', $guest );
        $this->assertSame( 1, substr_count( $guest, '<a href=' ) );
        $this->assertStringNotContainsString( 'or  to', $guest );

        $customer_id = self::factory()->user->create( [ 'role' => 'customer' ] );
        wp_set_current_user( $customer_id );
        $customer = do_blocks( $block );
        $this->assertStringContainsString( 'Protected text', $customer );
        $this->assertArrayHasKey( 'Cache-Control', wp_get_nocache_headers() );

        $filter_args = [];
        $deny = static function ( $allowed, $user_id, $context ) use ( &$filter_args ): bool {
            $filter_args = [ $user_id, $context ];
            return false;
        };
        add_filter( 'jps_cs_can_access_protected', $deny, 10, 3 );
        try {
            $denied = do_blocks( $block );
            $this->assertStringNotContainsString( 'Protected text', $denied );
            $this->assertStringContainsString( 'You do not have permission to view this content.', $denied );
            $this->assertSame( [ $customer_id, [ 'product_id' => $product_id ] ], $filter_args );
        } finally {
            remove_filter( 'jps_cs_can_access_protected', $deny, 10 );
        }
        if ( null === $previous_registration ) {
            delete_option( 'woocommerce_enable_myaccount_registration' );
        } else {
            update_option( 'woocommerce_enable_myaccount_registration', $previous_registration );
        }
        if ( null === $previous_wp_registration ) {
            delete_option( 'users_can_register' );
        } else {
            update_option( 'users_can_register', $previous_wp_registration );
        }
        wp_set_current_user( 0 );
        wp_reset_postdata();
    }

    public function test_fallback_content_is_only_rendered_for_guest(): void {
        $block = '<!-- wp:jp-catalog-suite/access {"audience":"unauthorized","showNotice":false} --><p>Fallback text</p><!-- /wp:jp-catalog-suite/access -->';
        wp_set_current_user( 0 );
        $this->assertStringContainsString( 'Fallback text', do_blocks( $block ) );
        $customer_id = self::factory()->user->create( [ 'role' => 'customer' ] );
        wp_set_current_user( $customer_id );
        $this->assertStringNotContainsString( 'Fallback text', do_blocks( $block ) );
        wp_set_current_user( 0 );
    }

    public function test_notice_registration_link_returns_to_product(): void {
        $previous_registration = get_option( 'woocommerce_enable_myaccount_registration', null );
        $previous_account = get_option( 'woocommerce_myaccount_page_id', null );
        $previous_notice = get_option( 'jps_cs_protected_notice', null );
        update_option( 'jps_cs_protected_notice', '' );
        update_option( 'woocommerce_enable_myaccount_registration', 'yes' );
        $account_id = self::factory()->post->create( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'My account' ] );
        update_option( 'woocommerce_myaccount_page_id', $account_id );
        $product_id = $this->create_product();
        $GLOBALS['post'] = get_post( $product_id );
        setup_postdata( $GLOBALS['post'] );
        $block = '<!-- wp:jp-catalog-suite/access {"audience":"authorized"} --><p>Protected text</p><!-- /wp:jp-catalog-suite/access -->';
        wp_set_current_user( 0 );
        $html = do_blocks( $block );
        $this->assertStringContainsString( 'redirect_to=', $html );
        $this->assertStringContainsString( 'page_id=', $html );
        $this->assertStringContainsString( rawurlencode( get_permalink( $product_id ) ), $html );
        preg_match_all( '/href="([^"]+)"/', $html, $matches );
        $register_url = html_entity_decode( $matches[1][1] ?? '', ENT_QUOTES );
        parse_str( (string) wp_parse_url( $register_url, PHP_URL_QUERY ), $register_query );
        $_REQUEST = $register_query;
        $this->assertSame( get_permalink( $product_id ), apply_filters( 'woocommerce_registration_redirect', home_url( '/my-account/' ) ) );
        $this->assertSame( get_permalink( $product_id ), apply_filters( 'registration_redirect', home_url( '/my-account/' ) ) );
        $_REQUEST['redirect_to'] = 'https://attacker.example/';
        $this->assertSame( home_url( '/my-account/' ), apply_filters( 'registration_redirect', home_url( '/my-account/' ) ) );
        $_REQUEST = [];
        wp_reset_postdata();
        if ( null === $previous_registration ) {
            delete_option( 'woocommerce_enable_myaccount_registration' );
        } else {
            update_option( 'woocommerce_enable_myaccount_registration', $previous_registration );
        }
        if ( null === $previous_account ) {
            delete_option( 'woocommerce_myaccount_page_id' );
        } else {
            update_option( 'woocommerce_myaccount_page_id', $previous_account );
        }
        if ( null === $previous_notice ) {
            delete_option( 'jps_cs_protected_notice' );
        } else {
            update_option( 'jps_cs_protected_notice', $previous_notice );
        }
    }

    public function test_protected_notice_filter_receives_rendered_html(): void {
        $product_id = $this->create_product();
        $GLOBALS['post'] = get_post( $product_id );
        setup_postdata( $GLOBALS['post'] );
        $seen_html = '';
        $seen_context = [];
        $filter = static function ( $html, $context ) use ( &$seen_html, &$seen_context ): string {
            $seen_html = $html;
            $seen_context = $context;
            return '<strong>Custom protected notice</strong>';
        };
        add_filter( 'jps_cs_protected_notice_html', $filter, 10, 2 );
        try {
            wp_set_current_user( 0 );
            $block = '<!-- wp:jp-catalog-suite/access {"audience":"authorized"} --><p>Protected text</p><!-- /wp:jp-catalog-suite/access -->';
            $html = do_blocks( $block );
            $this->assertStringContainsString( '<a href=', $seen_html );
            $this->assertStringContainsString( 'Custom protected notice', $html );
            $this->assertSame( [ 'product_id' => $product_id ], $seen_context );
        } finally {
            remove_filter( 'jps_cs_protected_notice_html', $filter, 10 );
            wp_reset_postdata();
        }
    }

    public function test_protected_notice_setting_is_saved_by_woocommerce(): void {
        $pages = apply_filters( 'woocommerce_get_settings_pages', [] );
        $settings_page = array_values( array_filter( $pages, static fn ( $page ): bool => 'jps_cs' === $page->get_id() ) )[0];
        $setting_ids = array_column( $settings_page->get_settings( 'tabs' ), 'id' );
        $this->assertContains( 'jps_cs_protected_notice', $setting_ids );

        $previous = get_option( 'jps_cs_protected_notice', null );
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        global $current_tab, $current_section;
        $current_tab = 'jps_cs';
        $current_section = 'tabs';
        if ( ! class_exists( 'WC_Admin_Settings' ) ) {
            require_once WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';
        }
        $_POST = [
            'jps_cs_protected_notice' => 'Please {login} to continue.',
            '_wpnonce' => wp_create_nonce( 'woocommerce-settings' ),
        ];
        $_REQUEST = $_POST;
        \WC_Admin_Settings::save();
        $this->assertSame( 'Please {login} to continue.', get_option( 'jps_cs_protected_notice' ) );
        if ( null === $previous ) {
            delete_option( 'jps_cs_protected_notice' );
        } else {
            update_option( 'jps_cs_protected_notice', $previous );
        }
        wp_set_current_user( 0 );
    }

    private function create_product(): int {
        $product = new WC_Product_Simple();
        $product->set_name( 'Access product' );
        $product->set_status( 'publish' );
        return $product->save();
    }
}
