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
        Tab_Admin::init();
        self::restrict_non_editor_rest_access();
        add_filter( 'woocommerce_product_tabs', [ self::class, 'add_product_tabs' ], 98, 2 );
        add_filter( 'woocommerce_product_tabs', [ self::class, 'configure_core_tabs' ], 99 );
        add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_variation_tabs_script' ] );
    }

    public static function restrict_non_editor_rest_access(): void {
        add_filter(
            'rest_pre_dispatch',
            static function ( $result, \WP_REST_Server $rest_server, \WP_REST_Request $request ) {
                unset( $rest_server );
                $route = '/' . ltrim( $request->get_route(), '/' );
                if ( preg_match( '#^/wp/v2/jps_cs_tab(?:/|$)#i', $route ) && ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) ) {
                    return new \WP_Error( 'rest_forbidden', __( 'Sorry, you are not allowed to read product tabs.', 'jp-catalog-suite' ), [ 'status' => 403 ] );
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
                'show_in_menu'       => false,
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
        register_post_meta( 'jps_cs_tab', '_jps_cs_variations', [ 'type' => 'array', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => static fn ( $value ): array => array_values( array_unique( array_filter( array_map( 'absint', (array) $value ) ) ) ) ] );
        register_post_meta( 'jps_cs_tab', '_jps_cs_priority', [ 'type' => 'string', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => 'sanitize_text_field' ] );
        register_post_meta( 'jps_cs_tab', '_jps_cs_replaces', [ 'type' => 'integer', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => 'absint' ] );
    }

    public static function settings(): array {
        $settings = [
            [ 'title' => __( 'Product tabs', 'jp-catalog-suite' ), 'type' => 'title', 'id' => 'jps_cs_tabs_settings' ],
            [ 'title' => __( 'Shop-wide tabs', 'jp-catalog-suite' ), 'type' => 'jps_cs_tab_list', 'id' => 'jps_cs_tabs_shop_wide', 'is_option' => false ],
        ];
        foreach ( self::core_tabs() as $key => $tab ) {
            $settings[] = [
                'title' => $tab['label'],
                'type'  => 'title',
                'id'    => 'jps_cs_tabs_core_' . $key,
            ];
            $settings[] = [
                'title'       => __( 'Title', 'jp-catalog-suite' ),
                'id'          => 'jps_cs_tabs_core_' . $key . '_title',
                'type'        => 'text',
                'placeholder' => $tab['label'],
                'default'     => '',
            ];
            $settings[] = [
                'title'   => __( 'Priority', 'jp-catalog-suite' ),
                'id'      => 'jps_cs_tabs_core_' . $key . '_priority',
                'type'    => 'number',
                'default' => '',
            ];
            $settings[] = [
                'title'   => __( 'Hide', 'jp-catalog-suite' ),
                'id'      => 'jps_cs_tabs_core_' . $key . '_hidden',
                'type'    => 'checkbox',
                'default' => 'no',
            ];
            $settings[] = [ 'type' => 'sectionend', 'id' => 'jps_cs_tabs_core_' . $key ];
        }
        $settings[] = [ 'type' => 'sectionend', 'id' => 'jps_cs_tabs_settings' ];
        $settings[] = [ 'title' => __( 'Protected files', 'jp-catalog-suite' ), 'type' => 'title', 'id' => 'jps_cs_files_settings' ];
        $settings[] = [ 'title' => __( 'Allowed file MIME types', 'jp-catalog-suite' ), 'id' => 'jps_cs_files_mimes', 'type' => 'textarea', 'default' => "stp|step=application/step\ndxf=application/dxf" ];
        $settings[] = [ 'title' => __( 'Automatically protect extensions', 'jp-catalog-suite' ), 'id' => 'jps_cs_files_protected_exts', 'type' => 'text', 'default' => 'stp, step, dxf' ];
        $settings[] = [ 'title' => __( 'File delivery method', 'jp-catalog-suite' ), 'id' => 'jps_cs_files_delivery', 'type' => 'select', 'default' => 'auto', 'options' => [ 'auto' => __( 'Automatic', 'jp-catalog-suite' ), 'xsendfile' => 'X-Sendfile', 'php' => 'PHP' ] ];
        $settings[] = [ 'type' => 'sectionend', 'id' => 'jps_cs_files_settings' ];
        $settings[] = [ 'title' => __( 'Download log', 'jp-catalog-suite' ), 'type' => 'title', 'id' => 'jps_cs_download_log_settings' ];
        $settings[] = [ 'title' => __( 'Enable download log', 'jp-catalog-suite' ), 'id' => 'jps_cs_files_log_enabled', 'type' => 'checkbox', 'default' => 'no' ];
        $settings[] = [ 'title' => __( 'Download log retention (days)', 'jp-catalog-suite' ), 'id' => 'jps_cs_files_log_retention_days', 'type' => 'number', 'default' => 365, 'custom_attributes' => [ 'min' => 0, 'step' => 1 ], 'desc' => __( 'Set to 0 to keep entries until erased or the user is deleted.', 'jp-catalog-suite' ) ];
        $settings[] = [ 'type' => 'sectionend', 'id' => 'jps_cs_download_log_settings' ];
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
            if ( 'yes' === get_option( 'jps_cs_tabs_core_' . $key . '_hidden', 'no' ) ) {
                unset( $tabs[ $tab['key'] ] );
                continue;
            }
            if ( ! isset( $tabs[ $tab['key'] ] ) ) {
                continue;
            }
            $title = (string) get_option( 'jps_cs_tabs_core_' . $key . '_title', '' );
            $priority = get_option( 'jps_cs_tabs_core_' . $key . '_priority', '' );
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

    private static function published_tabs(): array {
        return get_posts( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'publish', 'numberposts' => -1, 'suppress_filters' => false ] );
    }

    public static function scope( \WP_Post $tab ): string {
        $scope = get_post_meta( $tab->ID, '_jps_cs_scope', true );
        return '' === $scope ? 'all' : (string) $scope;
    }

    public static function expanded_category_ids( int $product_id ): array {
        $categories = wp_get_object_terms( $product_id, 'product_cat', [ 'fields' => 'ids' ] );
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
        return array_unique( $expanded );
    }

    /**
     * @param array<int, int> $variations Canonical variation ID => variation ID in the current language, for the product's variations.
     */
    private static function applies( \WP_Post $tab, \WC_Product $product, array $expanded, array $variations ): bool {
        $scope = self::scope( $tab );
        if ( 'products' === $scope ) {
            $stored = array_map( static fn ( int $id ): int => Plugin::canonical_id( $id, 'product' ), array_map( 'absint', (array) get_post_meta( $tab->ID, '_jps_cs_products', true ) ) );
            return in_array( Plugin::canonical_id( $product->get_id(), 'product' ), $stored, true );
        }
        if ( 'categories' === $scope ) {
            $stored = array_map( static fn ( int $term_id ): int => Plugin::canonical_id( $term_id, 'product_cat' ), array_map( 'absint', (array) get_post_meta( $tab->ID, '_jps_cs_categories', true ) ) );
            return (bool) array_intersect( $stored, $expanded );
        }
        if ( 'variations' === $scope ) {
            return isset( $variations[ self::canonical_variation_of( $tab ) ] );
        }
        return 'all' === $scope;
    }

    private static function canonical_variation_of( \WP_Post $tab ): int {
        $stored = array_map( 'absint', (array) get_post_meta( $tab->ID, '_jps_cs_variations', true ) );
        return Plugin::canonical_id( $stored[0] ?? 0, 'product_variation' );
    }

    /**
     * @return array<int, int> Canonical variation ID => variation ID in the current language.
     */
    private static function variations_of( \WC_Product $product ): array {
        $variations = [];
        if ( $product->is_type( 'variable' ) ) {
            foreach ( $product->get_children() as $child_id ) {
                $variations[ Plugin::canonical_id( (int) $child_id, 'product_variation' ) ] = (int) $child_id;
            }
        }
        return $variations;
    }

    /**
     * Published shop-wide and category tabs that apply to the product, keyed by post ID.
     * No content, hidden or replacement filtering.
     *
     * @return \WP_Post[]
     */
    public static function global_tabs_for( \WC_Product $product ): array {
        $expanded = self::expanded_category_ids( $product->get_id() );
        $tabs     = [];
        foreach ( self::published_tabs() as $post ) {
            if ( in_array( self::scope( $post ), [ 'all', 'categories' ], true ) && self::applies( $post, $product, $expanded, [] ) ) {
                $tabs[ $post->ID ] = $post;
            }
        }
        return $tabs;
    }

    public static function tabs_for( \WC_Product $product, bool $exclude_hidden = true ): array {
        $expanded   = self::expanded_category_ids( $product->get_id() );
        $variations = self::variations_of( $product );
        $matched    = [];
        foreach ( self::published_tabs() as $post ) {
            if ( ! self::applies( $post, $product, $expanded, $variations ) ) {
                continue;
            }
            $scope = self::scope( $post );
            $content = apply_filters( 'jps_cs_tab_content', wp_filter_content_tags( do_shortcode( do_blocks( $post->post_content ) ) ), $post, $product );
            $visible = preg_replace( '/<(img|video|iframe|embed|object|svg)\b/i', 'x<$1', $content );
            if ( '' === trim( wp_strip_all_tags( (string) $visible ) ) ) {
                continue;
            }
            $priority = get_post_meta( $post->ID, '_jps_cs_priority', true );
            $matched[ $post->ID ] = [
                'post'      => $post,
                'content'   => $content,
                'priority'  => '' === $priority ? 50 : (int) $priority,
                'scope'     => $scope,
                'variation' => 'variations' === $scope ? ( $variations[ self::canonical_variation_of( $post ) ] ?? 0 ) : 0,
                'replaces'  => '',
            ];
        }
        $hidden_ids = array_map( static fn ( int $id ): int => Plugin::canonical_id( $id, 'jps_cs_tab' ), array_map( 'absint', (array) get_post_meta( Plugin::canonical_id( $product->get_id(), 'product' ), '_jps_cs_hidden_tabs', true ) ) );
        $global_scopes     = [ 'all', 'categories' ];
        $global_priorities = [];
        foreach ( $matched as $matched_id => $matched_tab ) {
            if ( in_array( $matched_tab['scope'], $global_scopes, true ) ) {
                $global_priorities[ Plugin::canonical_id( (int) $matched_id, 'jps_cs_tab' ) ] = $matched_tab['priority'];
            }
        }
        foreach ( $matched as $id => $tab ) {
            if ( 'products' === $tab['scope'] ) {
                $replace_id = absint( get_post_meta( $id, '_jps_cs_replaces', true ) );
                if ( $replace_id ) {
                    $canonical_replace = Plugin::canonical_id( $replace_id, 'jps_cs_tab' );
                    $replacement_priority = $global_priorities[ $canonical_replace ] ?? null;
                    foreach ( $matched as $global_id => $global_tab ) {
                        if ( in_array( $global_tab['scope'], $global_scopes, true ) && Plugin::canonical_id( (int) $global_id, 'jps_cs_tab' ) === $canonical_replace ) {
                            $replacement_priority = $global_tab['priority'];
                            unset( $matched[ $global_id ] );
                        }
                    }
                    if ( '' === get_post_meta( $id, '_jps_cs_priority', true ) ) {
                        $matched[ $id ]['priority'] = $replacement_priority ?? 50;
                    }
                }
            } elseif ( $exclude_hidden && in_array( $tab['scope'], $global_scopes, true ) && in_array( Plugin::canonical_id( (int) $id, 'jps_cs_tab' ), $hidden_ids, true ) ) {
                unset( $matched[ $id ] );
            }
        }
        foreach ( $matched as $id => $tab ) {
            if ( 'variations' !== $tab['scope'] ) {
                continue;
            }
            $replace_id = absint( get_post_meta( $id, '_jps_cs_replaces', true ) );
            if ( ! $replace_id ) {
                continue;
            }
            $canonical_replace = Plugin::canonical_id( $replace_id, 'jps_cs_tab' );
            foreach ( $matched as $target_id => $target_tab ) {
                if ( 'variations' === $target_tab['scope'] || Plugin::canonical_id( (int) $target_id, 'jps_cs_tab' ) !== $canonical_replace ) {
                    continue;
                }
                $matched[ $id ]['replaces'] = 'jps_cs_tab_' . $canonical_replace;
                if ( '' === get_post_meta( $id, '_jps_cs_priority', true ) ) {
                    $matched[ $id ]['priority'] = $target_tab['priority'];
                }
                break;
            }
        }
        uasort( $matched, static fn ( array $left, array $right ): int => $left['priority'] <=> $right['priority'] );
        return $matched;
    }

    public static function enqueue_variation_tabs_script(): void {
        if ( ! is_product() ) {
            return;
        }
        $product = wc_get_product( get_queried_object_id() );
        if ( ! $product instanceof \WC_Product || ! $product->is_type( 'variable' ) ) {
            return;
        }
        wp_enqueue_script( 'jps-cs-variation-tabs', plugins_url( 'assets/js/variation-tabs.js', PLUGIN_FILE ), [ 'jquery', 'wc-single-product', 'wc-add-to-cart-variation' ], VERSION, true );
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
        $entries  = self::tabs_for( $product );
        $replaced = [];
        foreach ( $entries as $entry ) {
            if ( '' !== $entry['replaces'] ) {
                $replaced[ $entry['replaces'] ] = true;
            }
        }
        foreach ( $entries as $id => $tab ) {
            $post    = $tab['post'];
            $content = $tab['content'];
            $key     = 'jps_cs_tab_' . Plugin::canonical_id( (int) $id, 'jps_cs_tab' );
            $attributes = '';
            if ( 'variations' === $tab['scope'] ) {
                $attributes = sprintf( ' data-jps-cs-tab="%1$s" data-jps-cs-variation="%2$s" data-jps-cs-product="%3$s"', esc_attr( $key ), esc_attr( (string) $tab['variation'] ), esc_attr( (string) $product->get_id() ) );
                if ( '' !== $tab['replaces'] ) {
                    $attributes .= sprintf( ' data-jps-cs-replaces="%s"', esc_attr( $tab['replaces'] ) );
                }
            } elseif ( isset( $replaced[ $key ] ) ) {
                $attributes = sprintf( ' data-jps-cs-tab="%s"', esc_attr( $key ) );
            }
            $tabs[ $key ] = [
                'title'    => get_the_title( $post ),
                'priority' => $tab['priority'],
                'callback' => static function () use ( $content, $attributes ): void {
                    if ( '' === $attributes ) {
                        echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Editor-trusted content is rendered through the block and shortcode APIs.
                        return;
                    }
                    echo '<div class="jps-cs-tab-content"' . $attributes . '>' . $content . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributes are escaped above; content is editor-trusted.
                },
            ];
        }
        uasort( $tabs, static fn ( array $left, array $right ): int => ( $left['priority'] ?? 0 ) <=> ( $right['priority'] ?? 0 ) );
        return apply_filters( 'jps_cs_product_tabs', $tabs, $product );
    }
}
