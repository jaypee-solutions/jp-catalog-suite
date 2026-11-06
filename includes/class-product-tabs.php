<?php
/**
 * Global product tabs.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class Product_Tabs {

    public static function init(): void {
        add_action( 'init', [ self::class, 'register_post_type' ] );
        add_action( 'init', [ self::class, 'register_meta' ] );
        add_action( 'add_meta_boxes_jps_cs_tab', [ self::class, 'add_meta_box' ] );
        add_action( 'save_post_jps_cs_tab', [ self::class, 'save_meta' ] );
        add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_admin_assets' ] );
        add_action( 'rest_api_init', [ self::class, 'restrict_guest_rest_access' ] );
        add_filter( 'woocommerce_product_tabs', [ self::class, 'add_product_tabs' ], 98, 2 );
    }

    public static function restrict_guest_rest_access(): void {
        add_filter(
            'rest_pre_dispatch',
            static function ( $result, \WP_REST_Server $rest_server, \WP_REST_Request $request ) {
                if ( ! is_user_logged_in() && preg_match( '#^/wp/v2/jps_cs_tab(?:/\d+)?$#', $request->get_route() ) ) {
                    return new \WP_Error( 'rest_forbidden', __( 'Sorry, you are not allowed to read product tabs.', 'jp-catalog-suite' ), [ 'status' => 401 ] );
                }
                return $result;
            },
            10,
            3
        );
    }

    public static function register_post_type(): void {
        register_post_type(
            'jps_cs_tab',
            [
                'labels'             => [
                    'name'          => __( 'Product tabs', 'jp-catalog-suite' ),
                    'singular_name' => __( 'Product tab', 'jp-catalog-suite' ),
                    'add_new_item'  => __( 'Add product tab', 'jp-catalog-suite' ),
                    'edit_item'     => __( 'Edit product tab', 'jp-catalog-suite' ),
                ],
                'public'             => false,
                'show_ui'            => true,
                'show_in_menu'       => 'edit.php?post_type=product',
                'show_in_rest'       => true,
                'supports'           => [ 'title', 'editor', 'revisions' ],
                'capability_type'    => 'product',
                'map_meta_cap'       => true,
                'publicly_queryable' => false,
            ]
        );
    }

    public static function register_meta(): void {
        register_post_meta( 'jps_cs_tab', '_jps_cs_scope', [ 'type' => 'string', 'single' => true, 'show_in_rest' => false, 'default' => 'all', 'sanitize_callback' => 'sanitize_key' ] );
        register_post_meta( 'jps_cs_tab', '_jps_cs_categories', [ 'type' => 'array', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => static fn ( $value ): array => array_values( array_unique( array_filter( array_map( 'absint', (array) $value ) ) ) ) ] );
        register_post_meta( 'jps_cs_tab', '_jps_cs_priority', [ 'type' => 'string', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => 'sanitize_text_field' ] );
    }

    public static function enqueue_admin_assets(): void {
        $screen = get_current_screen();
        if ( ! $screen || 'jps_cs_tab' !== $screen->post_type ) {
            return;
        }
        wp_enqueue_script( 'wc-enhanced-select' );
        wp_enqueue_style( 'woocommerce_admin_styles' );
    }

    public static function add_meta_box(): void {
        add_meta_box( 'jps_cs_tab_settings', __( 'Tab settings', 'jp-catalog-suite' ), [ self::class, 'render_meta_box' ], 'jps_cs_tab', 'side' );
    }

    public static function render_meta_box( \WP_Post $post ): void {
        wp_nonce_field( 'jps_cs_tab_meta', 'jps_cs_tab_nonce' );
        $scope = get_post_meta( $post->ID, '_jps_cs_scope', true );
        if ( '' === $scope ) {
            $scope = 'all';
        }
        $categories = array_map( 'absint', (array) get_post_meta( $post->ID, '_jps_cs_categories', true ) );
        $priority   = get_post_meta( $post->ID, '_jps_cs_priority', true );
        ?>
        <p><label for="jps-cs-scope"><?php esc_html_e( 'Scope', 'jp-catalog-suite' ); ?></label>
        <select id="jps-cs-scope" name="jps_cs_scope">
            <option value="all" <?php selected( $scope, 'all' ); ?>><?php esc_html_e( 'All products', 'jp-catalog-suite' ); ?></option>
            <option value="categories" <?php selected( $scope, 'categories' ); ?>><?php esc_html_e( 'Categories', 'jp-catalog-suite' ); ?></option>
        </select></p>
        <p><label for="jps-cs-categories"><?php esc_html_e( 'Categories', 'jp-catalog-suite' ); ?></label>
        <select id="jps-cs-categories" name="jps_cs_categories[]" multiple class="wc-enhanced-select" style="width:100%">
            <?php foreach ( get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false ] ) as $term ) : ?>
                <option value="<?php echo esc_attr( (string) $term->term_id ); ?>" <?php selected( in_array( (int) $term->term_id, $categories, true ) ); ?>><?php echo esc_html( $term->name ); ?></option>
            <?php endforeach; ?>
        </select></p>
        <p><label for="jps-cs-priority"><?php esc_html_e( 'Priority', 'jp-catalog-suite' ); ?></label>
        <input id="jps-cs-priority" name="jps_cs_priority" type="number" value="<?php echo esc_attr( (string) $priority ); ?>" /></p>
        <?php
    }

    public static function save_meta( int $post_id ): void {
        if ( ! isset( $_POST['jps_cs_tab_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jps_cs_tab_nonce'] ) ), 'jps_cs_tab_meta' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        $scope = isset( $_POST['jps_cs_scope'] ) ? sanitize_key( wp_unslash( $_POST['jps_cs_scope'] ) ) : 'all';
        update_post_meta( $post_id, '_jps_cs_scope', in_array( $scope, [ 'all', 'categories' ], true ) ? $scope : 'all' );
        $category_ids = isset( $_POST['jps_cs_categories'] ) ? array_values( array_unique( array_filter( array_map( 'absint', (array) wp_unslash( $_POST['jps_cs_categories'] ) ) ) ) ) : [];
        update_post_meta( $post_id, '_jps_cs_categories', $category_ids );
        update_post_meta( $post_id, '_jps_cs_priority', isset( $_POST['jps_cs_priority'] ) ? sanitize_text_field( wp_unslash( $_POST['jps_cs_priority'] ) ) : '' );
    }

    public static function settings(): array {
        return [
            [ 'title' => __( 'Product tabs', 'jp-catalog-suite' ), 'type' => 'title', 'id' => 'jps_cs_tabs_settings' ],
            [ 'type' => 'sectionend', 'id' => 'jps_cs_tabs_settings' ],
        ];
    }

    public static function tabs_for( \WC_Product $product ): array {
        $posts = get_posts( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'publish', 'numberposts' => -1, 'suppress_filters' => false ] );
        $categories = wp_get_object_terms( $product->get_id(), 'product_cat', [ 'fields' => 'ids' ] );
        if ( is_wp_error( $categories ) ) {
            $categories = [];
        }
        $expanded = [];
        foreach ( $categories as $category_id ) {
            $term_ids = array_merge( [ (int) $category_id ], get_ancestors( (int) $category_id, 'product_cat', 'taxonomy' ) );
            foreach ( $term_ids as $term_id ) {
                $expanded[] = Plugin::canonical_id( (int) $term_id, 'product_cat' );
            }
        }
        $expanded = array_unique( $expanded );
        $matched = [];
        foreach ( $posts as $post ) {
            $scope = get_post_meta( $post->ID, '_jps_cs_scope', true );
            if ( '' === $scope ) {
                $scope = 'all';
            }
            $applies = 'all' === $scope;
            if ( 'categories' === $scope ) {
                $stored = array_map( static fn ( int $term_id ): int => Plugin::canonical_id( $term_id, 'product_cat' ), array_map( 'absint', (array) get_post_meta( $post->ID, '_jps_cs_categories', true ) ) );
                $applies = (bool) array_intersect( $stored, $expanded );
            }
            if ( ! $applies ) {
                continue;
            }
            $content = apply_filters( 'jps_cs_tab_content', wp_filter_content_tags( do_shortcode( do_blocks( $post->post_content ) ) ), $post, $product );
            $visible = preg_replace( '/<(img|video|iframe|embed|object|svg)\b/i', 'x<$1', $content );
            if ( '' === trim( wp_strip_all_tags( (string) $visible ) ) ) {
                continue;
            }
            $priority = get_post_meta( $post->ID, '_jps_cs_priority', true );
            $matched[ $post->ID ] = [ 'post' => $post, 'content' => $content, 'priority' => '' === $priority ? 50 : (int) $priority ];
        }
        uasort( $matched, static fn ( array $left, array $right ): int => $left['priority'] <=> $right['priority'] );
        return $matched;
    }

    public static function add_product_tabs( array $tabs, ?\WC_Product $product = null ): array {
        if ( null === $product ) {
            $product = $GLOBALS['product'] ?? null;
        }
        if ( ! $product instanceof \WC_Product ) {
            $product = wc_get_product( get_the_ID() );
        }
        if ( ! $product instanceof \WC_Product ) {
            return $tabs;
        }
        foreach ( self::tabs_for( $product ) as $id => $tab ) {
            $post = $tab['post'];
            $content = $tab['content'];
            $tabs[ 'jps_cs_tab_' . Plugin::canonical_id( (int) $id, 'jps_cs_tab' ) ] = [
                'title'    => get_the_title( $post ),
                'priority' => $tab['priority'],
                'callback' => static function () use ( $content ): void {
                    echo wp_kses_post( $content );
                },
            ];
        }
        return apply_filters( 'jps_cs_product_tabs', $tabs, $product );
    }
}
