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
        add_action( 'woocommerce_product_options_tabs', [ self::class, 'product_data_tab' ] );
        add_action( 'woocommerce_product_data_panels', [ self::class, 'product_data_panel' ] );
        add_action( 'woocommerce_admin_process_product_object', [ self::class, 'save_product_data' ] );
        add_filter( 'woocommerce_product_tabs', [ self::class, 'add_product_tabs' ], 98, 2 );
        add_filter( 'woocommerce_product_tabs', [ self::class, 'configure_core_tabs' ], 99 );
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
        register_post_meta( 'jps_cs_tab', '_jps_cs_products', [ 'type' => 'array', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => static fn ( $value ): array => array_values( array_unique( array_filter( array_map( 'absint', (array) $value ) ) ) ) ] );
        register_post_meta( 'jps_cs_tab', '_jps_cs_priority', [ 'type' => 'string', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => 'sanitize_text_field' ] );
        register_post_meta( 'jps_cs_tab', '_jps_cs_replace_tab', [ 'type' => 'integer', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => 'absint' ] );
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
        $product_ids = array_map( 'absint', (array) get_post_meta( $post->ID, '_jps_cs_products', true ) );
        $priority   = get_post_meta( $post->ID, '_jps_cs_priority', true );
        $replace = absint( get_post_meta( $post->ID, '_jps_cs_replace_tab', true ) );
        // This query parameter only preselects fields; saving remains protected by the tab nonce.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This query parameter only preselects fields; saving remains protected by the tab nonce.
        if ( isset( $_GET['jps_cs_product'] ) ) {
            $requested_product = absint( wp_unslash( $_GET['jps_cs_product'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The link is capability-checked and only preselects editor fields.
            if ( current_user_can( 'edit_post', $requested_product ) && 'product' === get_post_type( $requested_product ) ) {
                $scope = 'products';
                $product_ids = [ $requested_product ];
            }
        }
        ?>
        <p><label for="jps-cs-scope"><?php esc_html_e( 'Scope', 'jp-catalog-suite' ); ?></label>
        <select id="jps-cs-scope" name="jps_cs_scope">
            <option value="all" <?php selected( $scope, 'all' ); ?>><?php esc_html_e( 'All products', 'jp-catalog-suite' ); ?></option>
            <option value="categories" <?php selected( $scope, 'categories' ); ?>><?php esc_html_e( 'Categories', 'jp-catalog-suite' ); ?></option>
            <option value="products" <?php selected( $scope, 'products' ); ?>><?php esc_html_e( 'Products', 'jp-catalog-suite' ); ?></option>
        </select></p>
        <p><label for="jps-cs-categories"><?php esc_html_e( 'Categories', 'jp-catalog-suite' ); ?></label>
        <select id="jps-cs-categories" name="jps_cs_categories[]" multiple class="wc-enhanced-select" style="width:100%">
            <?php foreach ( get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false ] ) as $term ) : ?>
                <option value="<?php echo esc_attr( (string) $term->term_id ); ?>" <?php selected( in_array( (int) $term->term_id, $categories, true ) ); ?>><?php echo esc_html( $term->name ); ?></option>
            <?php endforeach; ?>
        </select></p>
        <p><label for="jps-cs-products"><?php esc_html_e( 'Products', 'jp-catalog-suite' ); ?></label>
        <select id="jps-cs-products" name="jps_cs_products[]" multiple class="wc-product-search" data-action="woocommerce_json_search_products" style="width:100%">
            <?php foreach ( $product_ids as $product_id ) : ?>
                <?php $product = wc_get_product( $product_id ); ?>
                <?php if ( $product ) : ?>
                    <option value="<?php echo esc_attr( (string) $product_id ); ?>" selected><?php echo esc_html( $product->get_name() ); ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select></p>
        <p><label for="jps-cs-replace-tab"><?php esc_html_e( 'Replace global tab', 'jp-catalog-suite' ); ?></label>
        <select id="jps-cs-replace-tab" name="jps_cs_replace_tab">
            <option value="0"><?php esc_html_e( 'None', 'jp-catalog-suite' ); ?></option>
            <?php foreach ( get_posts( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'publish', 'numberposts' => -1, 'exclude' => [ $post->ID ], 'suppress_filters' => false ] ) as $global_tab ) : ?>
                <?php $global_scope = get_post_meta( $global_tab->ID, '_jps_cs_scope', true ); ?>
                <?php if ( '' === $global_scope || in_array( $global_scope, [ 'all', 'categories' ], true ) ) : ?>
                    <option value="<?php echo esc_attr( (string) $global_tab->ID ); ?>" <?php selected( $replace, (int) $global_tab->ID ); ?>><?php echo esc_html( get_the_title( $global_tab ) ); ?></option>
                <?php endif; ?>
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
        if ( ! in_array( $scope, [ 'all', 'categories', 'products' ], true ) ) {
            $scope = 'all';
        }
        update_post_meta( $post_id, '_jps_cs_scope', $scope );
        $category_ids = isset( $_POST['jps_cs_categories'] ) ? array_values( array_unique( array_filter( array_map( 'absint', (array) wp_unslash( $_POST['jps_cs_categories'] ) ) ) ) ) : [];
        update_post_meta( $post_id, '_jps_cs_categories', $category_ids );
        $product_ids = isset( $_POST['jps_cs_products'] ) ? array_values( array_unique( array_filter( array_map( 'absint', (array) wp_unslash( $_POST['jps_cs_products'] ) ) ) ) ) : [];
        update_post_meta( $post_id, '_jps_cs_products', $product_ids );
        update_post_meta( $post_id, '_jps_cs_priority', isset( $_POST['jps_cs_priority'] ) ? sanitize_text_field( wp_unslash( $_POST['jps_cs_priority'] ) ) : '' );
        $replace = isset( $_POST['jps_cs_replace_tab'] ) ? absint( wp_unslash( $_POST['jps_cs_replace_tab'] ) ) : 0;
        $replace_post = $replace ? get_post( $replace ) : null;
        $replace_scope = $replace ? get_post_meta( $replace, '_jps_cs_scope', true ) : '';
        if ( '' === $replace_scope ) {
            $replace_scope = 'all';
        }
        if ( ! $replace_post || 'jps_cs_tab' !== $replace_post->post_type || ! in_array( $replace_scope, [ 'all', 'categories' ], true ) ) {
            $replace = 0;
        }
        update_post_meta( $post_id, '_jps_cs_replace_tab', $replace );
    }

    public static function settings(): array {
        $settings = [
            [ 'title' => __( 'Product tabs', 'jp-catalog-suite' ), 'type' => 'title', 'id' => 'jps_cs_tabs_settings' ],
        ];
        foreach ( self::core_tabs() as $key => $tab ) {
            $settings[] = [
                'title' => $tab['label'],
                'type'  => 'title',
                'id'    => 'jps_cs_core_tab_' . $key,
            ];
            $settings[] = [
                'title'       => __( 'Title', 'jp-catalog-suite' ),
                'id'          => 'jps_cs_core_tab_' . $key . '_title',
                'type'        => 'text',
                'placeholder' => $tab['label'],
                'default'     => '',
            ];
            $settings[] = [
                'title'   => __( 'Priority', 'jp-catalog-suite' ),
                'id'      => 'jps_cs_core_tab_' . $key . '_priority',
                'type'    => 'number',
                'default' => '',
            ];
            $settings[] = [
                'title'   => __( 'Hide', 'jp-catalog-suite' ),
                'id'      => 'jps_cs_core_tab_' . $key . '_hidden',
                'type'    => 'checkbox',
                'default' => 'no',
            ];
            $settings[] = [ 'type' => 'sectionend', 'id' => 'jps_cs_core_tab_' . $key ];
        }
        $settings[] = [ 'type' => 'sectionend', 'id' => 'jps_cs_tabs_settings' ];
        $settings[] = [ 'title' => __( 'Protected files', 'jp-catalog-suite' ), 'type' => 'title', 'id' => 'jps_cs_files_settings' ];
        $settings[] = [ 'title' => __( 'Allowed file MIME types', 'jp-catalog-suite' ), 'id' => 'jps_cs_files_mimes', 'type' => 'textarea', 'default' => "stp|step=application/step\ndxf=application/dxf" ];
        $settings[] = [ 'title' => __( 'Automatically protect extensions', 'jp-catalog-suite' ), 'id' => 'jps_cs_files_protected_exts', 'type' => 'text', 'default' => 'stp, step, dxf' ];
        $settings[] = [ 'title' => __( 'File delivery method', 'jp-catalog-suite' ), 'id' => 'jps_cs_files_delivery', 'type' => 'select', 'default' => 'auto', 'options' => [ 'auto' => __( 'Automatic', 'jp-catalog-suite' ), 'xsendfile' => 'X-Sendfile', 'php' => 'PHP' ] ];
        $settings[] = [ 'type' => 'sectionend', 'id' => 'jps_cs_files_settings' ];
        $settings = array_merge( $settings, Access_Block::settings() );
        return $settings;
    }

    private static function core_tabs(): array {
        return [
            'description' => [ 'label' => __( 'Description', 'jp-catalog-suite' ), 'key' => 'description' ],
            'additional_information' => [ 'label' => __( 'Additional information', 'jp-catalog-suite' ), 'key' => 'additional_information' ],
            'reviews' => [ 'label' => __( 'Reviews', 'jp-catalog-suite' ), 'key' => 'reviews' ],
        ];
    }

    public static function configure_core_tabs( array $tabs ): array {
        foreach ( self::core_tabs() as $key => $tab ) {
            if ( 'yes' === get_option( 'jps_cs_core_tab_' . $key . '_hidden', 'no' ) ) {
                unset( $tabs[ $tab['key'] ] );
                continue;
            }
            if ( ! isset( $tabs[ $tab['key'] ] ) ) {
                continue;
            }
            $title = (string) get_option( 'jps_cs_core_tab_' . $key . '_title', '' );
            $priority = get_option( 'jps_cs_core_tab_' . $key . '_priority', '' );
            if ( '' !== $title ) {
                $tabs[ $tab['key'] ]['title'] = $title;
            }
            if ( '' !== (string) $priority && is_numeric( $priority ) ) {
                $tabs[ $tab['key'] ]['priority'] = (int) $priority;
            }
        }
        uasort( $tabs, static fn ( array $left, array $right ): int => ( $left['priority'] ?? 0 ) <=> ( $right['priority'] ?? 0 ) );
        return $tabs;
    }

    public static function product_data_tab(): void {
        echo '<li class="product_tabs"><a href="#jps-cs-product-tabs"><span>' . esc_html__( 'Tabs', 'jp-catalog-suite' ) . '</span></a></li>';
    }

    public static function product_data_panel(): void {
        global $post;
        if ( ! $post instanceof \WP_Post ) {
            return;
        }
        $hidden = array_map( static fn ( int $id ): int => Plugin::canonical_id( $id, 'jps_cs_tab' ), array_map( 'absint', (array) get_post_meta( $post->ID, '_jps_cs_hidden_tabs', true ) ) );
        $product = wc_get_product( $post->ID );
        $applicable_tabs = $product instanceof \WC_Product ? self::tabs_for( $product, false ) : [];
        $applicable_ids = array_map( static fn ( int $id ): int => Plugin::canonical_id( $id, 'jps_cs_tab' ), array_map( 'absint', array_keys( $applicable_tabs ) ) );
        echo '<div id="jps-cs-product-tabs" class="panel woocommerce_options_panel">';
        foreach ( get_posts( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'publish', 'numberposts' => -1, 'suppress_filters' => false ] ) as $tab ) {
            $scope = get_post_meta( $tab->ID, '_jps_cs_scope', true );
            $scope = '' !== $scope ? $scope : 'all';
            if ( ! in_array( $scope, [ 'all', 'categories' ], true ) || ! in_array( Plugin::canonical_id( (int) $tab->ID, 'jps_cs_tab' ), $applicable_ids, true ) ) {
                continue;
            }
            $id = Plugin::canonical_id( (int) $tab->ID, 'jps_cs_tab' );
            printf( '<p><label><input type="checkbox" name="_jps_cs_hidden_tabs[]" value="%1$s" %2$s /> %3$s</label></p>', esc_attr( (string) $tab->ID ), checked( in_array( $id, $hidden, true ), true, false ), esc_html( get_the_title( $tab ) . ' — ' . __( 'Hide on this product', 'jp-catalog-suite' ) ) );
        }
        foreach ( get_posts( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'publish', 'numberposts' => -1, 'suppress_filters' => false ] ) as $tab ) {
            $ids = array_map( static fn ( int $id ): int => Plugin::canonical_id( $id, 'product' ), array_map( 'absint', (array) get_post_meta( $tab->ID, '_jps_cs_products', true ) ) );
            if ( in_array( Plugin::canonical_id( $post->ID, 'product' ), $ids, true ) ) {
                printf( '<p><a href="%1$s">%2$s</a></p>', esc_url( get_edit_post_link( $tab->ID ) ), esc_html( get_the_title( $tab ) ) );
            }
        }
        if ( current_user_can( 'edit_post', $post->ID ) ) {
            printf( '<p><a href="%1$s">%2$s</a></p>', esc_url( admin_url( 'post-new.php?post_type=jps_cs_tab&jps_cs_product=' . $post->ID ) ), esc_html__( 'Add product-specific tab', 'jp-catalog-suite' ) );
        }
        echo '</div>';
    }

    public static function save_product_data( \WC_Product $product ): void {
        // WooCommerce verifies the product editor nonce before this callback runs.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $hidden = isset( $_POST['_jps_cs_hidden_tabs'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['_jps_cs_hidden_tabs'] ) ) : [];
        $product->update_meta_data( '_jps_cs_hidden_tabs', array_values( array_unique( array_filter( $hidden ) ) ) );
    }

    public static function tabs_for( \WC_Product $product, bool $exclude_hidden = true ): array {
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
            if ( 'products' === $scope ) {
                $stored = array_map( static fn ( int $id ): int => Plugin::canonical_id( $id, 'product' ), array_map( 'absint', (array) get_post_meta( $post->ID, '_jps_cs_products', true ) ) );
                $applies = in_array( Plugin::canonical_id( $product->get_id(), 'product' ), $stored, true );
            } elseif ( 'categories' === $scope ) {
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
            $matched[ $post->ID ] = [ 'post' => $post, 'content' => $content, 'priority' => '' === $priority ? 50 : (int) $priority, 'scope' => $scope ];
        }
        $hidden_ids = array_map( static fn ( int $id ): int => Plugin::canonical_id( $id, 'jps_cs_tab' ), array_map( 'absint', (array) get_post_meta( Plugin::canonical_id( $product->get_id(), 'product' ), '_jps_cs_hidden_tabs', true ) ) );
        foreach ( $matched as $id => $tab ) {
            if ( 'products' === $tab['scope'] ) {
                $replace_id = absint( get_post_meta( $id, '_jps_cs_replace_tab', true ) );
                if ( $replace_id ) {
                    $canonical_replace = Plugin::canonical_id( $replace_id, 'jps_cs_tab' );
                    $replacement_priority = null;
                    foreach ( $matched as $global_id => $global_tab ) {
                        if ( 'products' !== $global_tab['scope'] && Plugin::canonical_id( (int) $global_id, 'jps_cs_tab' ) === $canonical_replace ) {
                            $replacement_priority = $global_tab['priority'];
                            unset( $matched[ $global_id ] );
                        }
                    }
                    if ( '' === get_post_meta( $id, '_jps_cs_priority', true ) ) {
                        $matched[ $id ]['priority'] = $replacement_priority ?? 50;
                    }
                }
            } elseif ( $exclude_hidden && in_array( Plugin::canonical_id( (int) $id, 'jps_cs_tab' ), $hidden_ids, true ) ) {
                unset( $matched[ $id ] );
            }
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
        uasort( $tabs, static fn ( array $left, array $right ): int => ( $left['priority'] ?? 0 ) <=> ( $right['priority'] ?? 0 ) );
        return apply_filters( 'jps_cs_product_tabs', $tabs, $product );
    }
}
