<?php
/**
 * Managing product tabs on their owners: products, product categories and the settings page.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class Tab_Admin {

    private const SCOPES         = [ 'all', 'categories', 'products', 'variations' ];
    private const OWNER_TYPES    = [ 'categories' => 'product_cat', 'products' => 'product', 'variations' => 'product_variation' ];
    private const STATUSES       = [ 'publish', 'draft', 'pending', 'future', 'private' ];
    private const NONCE_ACTION   = 'jps_cs_tab_manager';
    private const DEFAULT_PRIORITY = 50;

    /**
     * @var array<int, array> Product_Tabs::tabs_for() per parent product, memoized for the request.
     */
    private static array $parent_tabs = [];

    public static function init(): void {
        add_action( 'add_meta_boxes_jps_cs_tab', [ self::class, 'add_meta_box' ] );
        add_action( 'save_post_jps_cs_tab', [ self::class, 'save_meta' ] );
        add_filter( 'woocommerce_product_data_tabs', [ self::class, 'product_data_tabs' ] );
        add_action( 'woocommerce_product_data_panels', [ self::class, 'product_data_panel' ] );
        add_action( 'woocommerce_admin_process_product_object', [ self::class, 'save_product_data' ] );
        add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_manager_assets' ] );
        add_action( 'enqueue_block_editor_assets', [ self::class, 'enqueue_embed_assets' ] );
        add_action( 'product_cat_edit_form_fields', [ self::class, 'render_category_field' ], 10, 2 );
        add_action( 'woocommerce_product_after_variable_attributes', [ self::class, 'render_variation_field' ], 10, 3 );
        add_action( 'woocommerce_admin_field_jps_cs_tab_list', [ self::class, 'render_settings_field' ] );
        add_action( 'wp_ajax_jps_cs_tab_list', [ self::class, 'ajax_tab_list' ] );
        add_action( 'wp_ajax_jps_cs_trash_tab', [ self::class, 'ajax_trash_tab' ] );
        add_filter( 'default_title', [ self::class, 'prefill_replacement' ], 10, 2 );
        add_filter( 'default_content', [ self::class, 'prefill_replacement' ], 10, 2 );
        add_action( 'admin_init', [ Tab_Migration::class, 'maybe_migrate' ] );
        add_action( 'before_delete_post', [ self::class, 'delete_tabs_of_deleted_product' ], 10, 2 );
        add_action( 'delete_product_cat', [ self::class, 'delete_tabs_of_deleted_category' ] );
    }

    /* ---------------------------------------------------------------- Owner model */

    /**
     * @return array{0: string, 1: int} Scope and target ID (0 for shop-wide tabs).
     */
    private static function owner_of( \WP_Post $tab ): array {
        $scope = Product_Tabs::scope( $tab );
        $key   = self::owner_meta_key( $scope );
        if ( null === $key ) {
            return [ $scope, 0 ];
        }
        $ids = array_map( 'absint', (array) get_post_meta( $tab->ID, $key, true ) );
        return [ $scope, $ids[0] ?? 0 ];
    }

    private static function owner_meta_key( string $scope ): ?string {
        return [ 'categories' => '_jps_cs_categories', 'products' => '_jps_cs_products', 'variations' => '_jps_cs_variations' ][ $scope ] ?? null;
    }

    /**
     * Returns the owner when it exists, null otherwise.
     *
     * @return array{0: string, 1: int}|null
     */
    private static function valid_owner( string $scope, int $target ): ?array {
        if ( 'all' === $scope ) {
            return [ 'all', 0 ];
        }
        if ( 'categories' === $scope && term_exists( $target, 'product_cat' ) ) {
            return [ $scope, $target ];
        }
        if ( 'products' === $scope && 'product' === get_post_type( $target ) ) {
            return [ $scope, $target ];
        }
        if ( 'variations' === $scope && 'product_variation' === get_post_type( $target ) ) {
            return [ $scope, $target ];
        }
        return null;
    }

    /**
     * @return \WP_Post[]
     */
    private static function tabs_owned_by( string $scope, int $target, array $statuses ): array {
        $owned = [];
        $type  = self::OWNER_TYPES[ $scope ] ?? 'product';
        $canonical_target = 'all' === $scope ? 0 : Plugin::canonical_id( $target, $type );
        foreach ( get_posts( [ 'post_type' => 'jps_cs_tab', 'post_status' => $statuses, 'numberposts' => -1, 'suppress_filters' => false ] ) as $tab ) {
            [ $tab_scope, $tab_target ] = self::owner_of( $tab );
            if ( $tab_scope !== $scope ) {
                continue;
            }
            if ( 'all' === $scope || Plugin::canonical_id( $tab_target, $type ) === $canonical_target ) {
                $owned[] = $tab;
            }
        }
        usort( $owned, static fn ( \WP_Post $left, \WP_Post $right ): int => self::compare( self::effective_priority( $left ), $left->post_title, self::effective_priority( $right ), $right->post_title ) );
        return $owned;
    }

    private static function compare( int $left_priority, string $left_title, int $right_priority, string $right_title ): int {
        return $left_priority === $right_priority ? strcasecmp( $left_title, $right_title ) : $left_priority <=> $right_priority;
    }

    /**
     * @return \WP_Post[]
     */
    private static function owned_tabs( string $scope, int $target ): array {
        return self::tabs_owned_by( $scope, $target, self::STATUSES );
    }

    private static function effective_priority( \WP_Post $tab ): int {
        $priority = get_post_meta( $tab->ID, '_jps_cs_priority', true );
        return '' === $priority ? self::DEFAULT_PRIORITY : (int) $priority;
    }

    private static function new_tab_url( string $scope, int $target, int $replaces = 0 ): string {
        $args = [ 'post_type' => 'jps_cs_tab', 'jps_cs_scope' => $scope, 'jps_cs_target' => $target ];
        if ( $replaces ) {
            $args['jps_cs_replaces'] = $replaces;
        }
        return admin_url( 'post-new.php?' . http_build_query( $args, '', '&' ) );
    }

    private static function can_create(): bool {
        $type = get_post_type_object( 'jps_cs_tab' );
        return $type && current_user_can( $type->cap->create_posts );
    }

    private static function title_of( \WP_Post $tab ): string {
        $title = get_the_title( $tab );
        return '' === $title ? '#' . $tab->ID : $title;
    }

    private static function term_name( int $term_id ): string {
        $term = get_term( $term_id, 'product_cat' );
        return $term instanceof \WP_Term ? $term->name : '';
    }

    /* ---------------------------------------------------------------- Tab list markup */

    public static function manager_html( string $scope, int $target ): string {
        ob_start();
        ?>
        <div class="jps-cs-tab-manager" data-scope="<?php echo esc_attr( $scope ); ?>" data-target="<?php echo esc_attr( (string) $target ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( self::NONCE_ACTION ) ); ?>" data-confirm-trash="<?php echo esc_attr__( 'Move this tab to the trash?', 'jp-catalog-suite' ); ?>">
            <div class="jps-cs-tab-manager__list"><?php echo self::list_html( $scope, $target ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building the list. ?></div>
            <dialog class="jps-cs-tab-dialog">
                <div class="jps-cs-tab-dialog__header"><button type="button" class="button jps-cs-tab-dialog__close"><?php esc_html_e( 'Close', 'jp-catalog-suite' ); ?></button></div>
                <iframe class="jps-cs-tab-dialog__frame" title="<?php echo esc_attr__( 'Tab editor', 'jp-catalog-suite' ); ?>"></iframe>
            </dialog>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private static function list_html( string $scope, int $target ): string {
        $rows = self::list_rows( $scope, $target );
        usort( $rows, static fn ( array $left, array $right ): int => self::compare( $left['priority'], $left['title'], $right['priority'], $right['title'] ) );
        ob_start();
        if ( [] === $rows ) {
            echo '<p>' . esc_html__( 'No tabs yet.', 'jp-catalog-suite' ) . '</p>';
        } else {
            ?>
            <table class="widefat striped jps-cs-tab-list">
                <thead><tr>
                    <th><?php esc_html_e( 'Title', 'jp-catalog-suite' ); ?></th>
                    <th><?php esc_html_e( 'Shown on', 'jp-catalog-suite' ); ?></th>
                    <th><?php esc_html_e( 'Priority', 'jp-catalog-suite' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'jp-catalog-suite' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'jp-catalog-suite' ); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ( $rows as $row ) : ?>
                    <tr>
                        <td><?php echo esc_html( $row['title'] ); ?></td>
                        <td><?php echo esc_html( $row['shown_on'] ); ?></td>
                        <td><?php echo esc_html( (string) $row['priority'] ); ?></td>
                        <td><?php echo esc_html( $row['status'] ); ?></td>
                        <td><?php echo $row['actions']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building the row. ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        }
        if ( self::can_create() ) {
            printf( '<p><a class="button jps-cs-tab-open" href="%1$s" target="_blank" rel="noopener">%2$s</a></p>', esc_url( self::new_tab_url( $scope, $target ) ), esc_html__( 'Add tab', 'jp-catalog-suite' ) );
        }
        return (string) ob_get_clean();
    }

    /**
     * @return array<int, array{title: string, shown_on: string, priority: int, status: string, actions: string}>
     */
    private static function list_rows( string $scope, int $target ): array {
        if ( 'products' === $scope ) {
            return self::product_rows( $target );
        }
        if ( 'variations' === $scope ) {
            return self::variation_rows( $target );
        }
        if ( 'categories' === $scope ) {
            return self::category_rows( $target );
        }
        return array_map(
            fn ( \WP_Post $tab ): array => self::row( $tab, __( 'All products', 'jp-catalog-suite' ), self::effective_priority( $tab ), self::own_actions( $tab ) ),
            self::owned_tabs( 'all', 0 )
        );
    }

    private static function row( \WP_Post $tab, string $shown_on, int $priority, string $actions ): array {
        $status = 'publish' === $tab->post_status ? null : get_post_status_object( $tab->post_status );
        return [
            'title'    => self::title_of( $tab ),
            'shown_on' => $shown_on,
            'priority' => $priority,
            'status'   => $status ? (string) $status->label : '',
            'actions'  => $actions,
        ];
    }

    private static function open_link( string $url, string $label ): string {
        return sprintf( '<a class="jps-cs-tab-open" href="%1$s" target="_blank" rel="noopener">%2$s</a>', esc_url( $url ), esc_html( $label ) );
    }

    private static function edit_action( \WP_Post $tab ): string {
        return current_user_can( 'edit_post', $tab->ID ) ? self::open_link( (string) get_edit_post_link( $tab->ID, 'raw' ), __( 'Edit', 'jp-catalog-suite' ) ) : '';
    }

    private static function own_actions( \WP_Post $tab ): string {
        $actions = [ self::edit_action( $tab ) ];
        if ( current_user_can( 'delete_post', $tab->ID ) ) {
            $actions[] = sprintf( '<button type="button" class="button-link jps-cs-tab-trash" data-tab="%1$d">%2$s</button>', $tab->ID, esc_html__( 'Delete', 'jp-catalog-suite' ) );
        }
        return implode( ' ', array_filter( $actions ) );
    }

    private static function product_rows( int $product_id ): array {
        $rows  = [];
        $own   = self::owned_tabs( 'products', $product_id );
        $replacements = [];
        foreach ( $own as $tab ) {
            $replaced_id = absint( get_post_meta( $tab->ID, '_jps_cs_replaces', true ) );
            $replaced    = $replaced_id ? get_post( $replaced_id ) : null;
            $shown_on    = __( 'This product', 'jp-catalog-suite' );
            $priority    = self::effective_priority( $tab );
            if ( $replaced instanceof \WP_Post ) {
                /* translators: %s: title of the replaced tab. */
                $shown_on .= ' · ' . sprintf( __( 'Replaces %s', 'jp-catalog-suite' ), self::title_of( $replaced ) );
                if ( '' === get_post_meta( $tab->ID, '_jps_cs_priority', true ) ) {
                    $priority = self::effective_priority( $replaced );
                }
                if ( 'publish' === $tab->post_status ) {
                    $replacements[ Plugin::canonical_id( $replaced_id, 'jps_cs_tab' ) ] = $tab;
                }
            }
            $rows[] = self::row( $tab, $shown_on, $priority, self::own_actions( $tab ) );
        }
        $product = wc_get_product( $product_id );
        if ( ! $product instanceof \WC_Product ) {
            return $rows;
        }
        $hidden = array_map( static fn ( int $id ): int => Plugin::canonical_id( $id, 'jps_cs_tab' ), array_map( 'absint', (array) get_post_meta( Plugin::canonical_id( $product_id, 'product' ), '_jps_cs_hidden_tabs', true ) ) );
        foreach ( Product_Tabs::global_tabs_for( $product ) as $tab ) {
            $canonical = Plugin::canonical_id( $tab->ID, 'jps_cs_tab' );
            [ $tab_scope, $tab_target ] = self::owner_of( $tab );
            if ( 'categories' === $tab_scope ) {
                /* translators: %s: category name. */
                $shown_on = sprintf( __( 'Category: %s', 'jp-catalog-suite' ), self::term_name( $tab_target ) );
            } else {
                $shown_on = __( 'All products', 'jp-catalog-suite' );
            }
            if ( isset( $replacements[ $canonical ] ) ) {
                /* translators: %s: title of the replacing product tab. */
                $actions = esc_html( sprintf( __( 'Replaced by %s', 'jp-catalog-suite' ), self::title_of( $replacements[ $canonical ] ) ) );
            } else {
                $actions = sprintf(
                    '<label><input type="checkbox" name="_jps_cs_hidden_tabs[]" value="%1$s" %2$s /> %3$s</label>',
                    esc_attr( (string) $canonical ),
                    checked( in_array( $canonical, $hidden, true ), true, false ),
                    esc_html__( 'Hide on this product', 'jp-catalog-suite' )
                );
                if ( self::can_create() ) {
                    $actions .= ' ' . self::open_link( self::new_tab_url( 'products', $product_id, $tab->ID ), __( 'Replace', 'jp-catalog-suite' ) );
                }
            }
            $rows[] = self::row( $tab, $shown_on, self::effective_priority( $tab ), $actions );
        }
        return $rows;
    }

    /**
     * @return array<int, array{post: \WP_Post, scope: string, priority: int}> Tabs shown on the parent product, variation tabs included.
     */
    private static function parent_tabs( \WC_Product $parent_product ): array {
        self::$parent_tabs[ $parent_product->get_id() ] ??= Product_Tabs::tabs_for( $parent_product );
        return self::$parent_tabs[ $parent_product->get_id() ];
    }

    private static function variation_rows( int $variation_id ): array {
        $parent  = wc_get_product( wp_get_post_parent_id( $variation_id ) );
        $inherited = [];
        foreach ( $parent instanceof \WC_Product ? self::parent_tabs( $parent ) : [] as $entry ) {
            if ( 'variations' !== $entry['scope'] ) {
                $inherited[ Plugin::canonical_id( $entry['post']->ID, 'jps_cs_tab' ) ] = $entry;
            }
        }
        $rows         = [];
        $replacements = [];
        foreach ( self::owned_tabs( 'variations', $variation_id ) as $tab ) {
            $replaced_id = absint( get_post_meta( $tab->ID, '_jps_cs_replaces', true ) );
            $replaced    = $replaced_id ? get_post( $replaced_id ) : null;
            $shown_on    = __( 'This variation', 'jp-catalog-suite' );
            $priority    = self::effective_priority( $tab );
            if ( $replaced instanceof \WP_Post ) {
                /* translators: %s: title of the replaced tab. */
                $shown_on .= ' · ' . sprintf( __( 'Replaces %s', 'jp-catalog-suite' ), self::title_of( $replaced ) );
                $canonical = Plugin::canonical_id( $replaced_id, 'jps_cs_tab' );
                if ( '' === get_post_meta( $tab->ID, '_jps_cs_priority', true ) && isset( $inherited[ $canonical ] ) ) {
                    $priority = $inherited[ $canonical ]['priority'];
                }
                if ( 'publish' === $tab->post_status ) {
                    $replacements[ $canonical ] = $tab;
                }
            }
            $rows[] = self::row( $tab, $shown_on, $priority, self::own_actions( $tab ) );
        }
        foreach ( $inherited as $canonical => $entry ) {
            $post = $entry['post'];
            if ( 'categories' === $entry['scope'] ) {
                /* translators: %s: category name. */
                $shown_on = sprintf( __( 'Category: %s', 'jp-catalog-suite' ), self::term_name( self::owner_of( $post )[1] ) );
            } elseif ( 'products' === $entry['scope'] ) {
                $shown_on = __( 'Parent product', 'jp-catalog-suite' );
            } else {
                $shown_on = __( 'All products', 'jp-catalog-suite' );
            }
            if ( isset( $replacements[ $canonical ] ) ) {
                /* translators: %s: title of the replacing variation tab. */
                $actions = esc_html( sprintf( __( 'Replaced by %s', 'jp-catalog-suite' ), self::title_of( $replacements[ $canonical ] ) ) );
            } elseif ( self::can_create() ) {
                $actions = self::open_link( self::new_tab_url( 'variations', $variation_id, $post->ID ), __( 'Replace', 'jp-catalog-suite' ) );
            } else {
                $actions = '';
            }
            $rows[] = self::row( $post, $shown_on, $entry['priority'], $actions );
        }
        return $rows;
    }

    private static function category_rows( int $term_id ): array {
        $rows = [];
        foreach ( self::owned_tabs( 'categories', $term_id ) as $tab ) {
            $rows[] = self::row( $tab, __( 'This category', 'jp-catalog-suite' ), self::effective_priority( $tab ), self::own_actions( $tab ) );
        }
        foreach ( get_ancestors( $term_id, 'product_cat', 'taxonomy' ) as $ancestor_id ) {
            foreach ( self::tabs_owned_by( 'categories', (int) $ancestor_id, [ 'publish' ] ) as $tab ) {
                /* translators: %s: parent category name. */
                $rows[] = self::row( $tab, sprintf( __( 'Parent category: %s', 'jp-catalog-suite' ), self::term_name( (int) $ancestor_id ) ), self::effective_priority( $tab ), self::edit_action( $tab ) );
            }
        }
        foreach ( self::tabs_owned_by( 'all', 0, [ 'publish' ] ) as $tab ) {
            $rows[] = self::row( $tab, __( 'All products', 'jp-catalog-suite' ), self::effective_priority( $tab ), self::edit_action( $tab ) );
        }
        return $rows;
    }

    /* ---------------------------------------------------------------- Screens */

    public static function product_data_tabs( array $tabs ): array {
        $tabs['jps_cs_tabs'] = [ 'label' => __( 'Tabs', 'jp-catalog-suite' ), 'target' => 'jps-cs-product-tabs', 'class' => [], 'priority' => 80 ];
        return $tabs;
    }

    public static function product_data_panel(): void {
        global $post;
        if ( ! $post instanceof \WP_Post ) {
            return;
        }
        echo '<div id="jps-cs-product-tabs" class="panel woocommerce_options_panel">';
        echo self::manager_html( 'products', $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building the markup.
        echo '</div>';
    }

    public static function save_product_data( \WC_Product $product ): void {
        // WooCommerce verifies the product editor nonce before this callback runs.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $hidden = isset( $_POST['_jps_cs_hidden_tabs'] ) ? array_map( static fn ( int $id ): int => Plugin::canonical_id( $id, 'jps_cs_tab' ), array_map( 'absint', (array) wp_unslash( $_POST['_jps_cs_hidden_tabs'] ) ) ) : [];
        update_post_meta( Plugin::canonical_id( $product->get_id(), 'product' ), '_jps_cs_hidden_tabs', array_values( array_unique( array_filter( $hidden ) ) ) );
    }

    public static function render_category_field( \WP_Term $term, string $taxonomy ): void {
        unset( $taxonomy );
        echo '<tr class="form-field"><th scope="row">' . esc_html__( 'Tabs', 'jp-catalog-suite' ) . '</th><td>';
        echo self::manager_html( 'categories', $term->term_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building the markup.
        echo '</td></tr>';
    }

    public static function render_variation_field( int $loop, array $variation_data, \WP_Post $variation ): void {
        unset( $loop, $variation_data );
        echo '<div class="form-row form-row-full jps-cs-variation-tabs"><p><strong>' . esc_html__( 'Tabs', 'jp-catalog-suite' ) . '</strong></p>';
        echo self::manager_html( 'variations', $variation->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building the markup.
        echo '</div>';
    }

    public static function render_settings_field( array $value ): void {
        echo '<tr><th scope="row" class="titledesc">' . esc_html( (string) ( $value['title'] ?? '' ) ) . '</th><td class="forminp">';
        echo self::manager_html( 'all', 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building the markup.
        echo '</td></tr>';
    }

    /* ---------------------------------------------------------------- Assets */

    public static function enqueue_manager_assets( string $hook_suffix ): void {
        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }
        $on_product  = in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) && 'product' === $screen->post_type;
        $on_category = 'term.php' === $hook_suffix && 'product_cat' === $screen->taxonomy;
        $on_settings = 'woocommerce_page_wc-settings' === $hook_suffix && isset( $_GET['tab'] ) && 'jps_cs' === sanitize_key( wp_unslash( $_GET['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading the settings tab only selects assets.
        if ( ! $on_product && ! $on_category && ! $on_settings ) {
            return;
        }
        wp_enqueue_style( 'jps-cs-tab-manager', plugins_url( 'assets/css/tab-manager.css', PLUGIN_FILE ), [], VERSION );
        wp_enqueue_script( 'jps-cs-tab-manager', plugins_url( 'assets/js/tab-manager.js', PLUGIN_FILE ), [], VERSION, true );
    }

    public static function enqueue_embed_assets(): void {
        $screen = get_current_screen();
        if ( ! $screen || 'jps_cs_tab' !== $screen->post_type ) {
            return;
        }
        wp_enqueue_style( 'jps-cs-tab-editor-embed', plugins_url( 'assets/css/tab-editor-embed.css', PLUGIN_FILE ), [], VERSION );
        wp_enqueue_script( 'jps-cs-tab-editor-embed', plugins_url( 'assets/js/tab-editor-embed.js', PLUGIN_FILE ), [ 'wp-hooks' ], VERSION, true );
    }

    /* ---------------------------------------------------------------- AJAX */

    private static function deny(): void {
        wp_send_json_error( [ 'message' => __( 'Sorry, you are not allowed to manage these tabs.', 'jp-catalog-suite' ) ], 403 );
    }

    private static function can_manage( string $scope, int $target ): bool {
        if ( 'products' === $scope ) {
            return 'product' === get_post_type( $target ) && current_user_can( 'edit_post', $target );
        }
        if ( 'variations' === $scope ) {
            return 'product_variation' === get_post_type( $target ) && current_user_can( 'edit_post', $target );
        }
        if ( 'categories' === $scope ) {
            return (bool) term_exists( $target, 'product_cat' ) && current_user_can( 'edit_term', $target );
        }
        // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this capability.
        return current_user_can( 'manage_woocommerce' );
    }

    public static function ajax_tab_list(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );
        $scope  = isset( $_GET['scope'] ) ? sanitize_key( wp_unslash( $_GET['scope'] ) ) : '';
        $target = isset( $_GET['target'] ) ? absint( wp_unslash( $_GET['target'] ) ) : 0;
        if ( ! in_array( $scope, self::SCOPES, true ) ) {
            wp_send_json_error( null, 400 );
        }
        if ( ! self::can_manage( $scope, $target ) ) {
            self::deny();
        }
        wp_send_json_success( [ 'html' => self::list_html( $scope, $target ) ] );
    }

    public static function ajax_trash_tab(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );
        $tab_id = isset( $_POST['tab_id'] ) ? absint( wp_unslash( $_POST['tab_id'] ) ) : 0;
        if ( 'jps_cs_tab' !== get_post_type( $tab_id ) || ! current_user_can( 'delete_post', $tab_id ) ) {
            self::deny();
        }
        if ( ! wp_trash_post( $tab_id ) ) {
            wp_send_json_error( null, 500 );
        }
        wp_send_json_success();
    }

    /* ---------------------------------------------------------------- Tab settings metabox */

    public static function add_meta_box(): void {
        add_meta_box( 'jps_cs_tab_settings', __( 'Tab settings', 'jp-catalog-suite' ), [ self::class, 'render_meta_box' ], 'jps_cs_tab', 'side' );
    }

    /**
     * The owner requested through the "Add tab" link wins on auto-drafts.
     *
     * @return array{0: string, 1: int}
     */
    private static function editing_owner( \WP_Post $tab ): array {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- These query parameters only preselect fields; saving remains protected by the tab nonce.
        if ( 'auto-draft' === $tab->post_status && isset( $_GET['jps_cs_scope'], $_GET['jps_cs_target'] ) ) {
            $requested = self::valid_owner( sanitize_key( wp_unslash( $_GET['jps_cs_scope'] ) ), absint( wp_unslash( $_GET['jps_cs_target'] ) ) );
            if ( $requested ) {
                return $requested;
            }
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        return self::owner_of( $tab );
    }

    private static function owner_label( string $scope, int $target ): string {
        if ( 'categories' === $scope ) {
            /* translators: %s: category name. */
            return sprintf( __( '%s and its subcategories', 'jp-catalog-suite' ), self::term_name( $target ) );
        }
        if ( 'products' === $scope ) {
            /* translators: %s: product name. */
            return sprintf( __( 'Product: %s', 'jp-catalog-suite' ), get_the_title( $target ) );
        }
        if ( 'variations' === $scope ) {
            /* translators: %s: variation name. */
            return sprintf( __( 'Variation: %s', 'jp-catalog-suite' ), get_the_title( $target ) );
        }
        return __( 'All products', 'jp-catalog-suite' );
    }

    public static function render_meta_box( \WP_Post $post ): void {
        wp_nonce_field( 'jps_cs_tab_meta', 'jps_cs_tab_nonce' );
        [ $scope, $target ] = self::editing_owner( $post );
        $priority = get_post_meta( $post->ID, '_jps_cs_priority', true );
        ?>
        <p><strong><?php esc_html_e( 'Shown on:', 'jp-catalog-suite' ); ?></strong> <?php echo esc_html( self::owner_label( $scope, $target ) ); ?></p>
        <?php
        printf( '<input type="hidden" name="jps_cs_scope" value="%s" />', esc_attr( $scope ) );
        printf( '<input type="hidden" name="jps_cs_target" value="%s" />', esc_attr( (string) $target ) );
        if ( in_array( $scope, [ 'products', 'variations' ], true ) ) {
            $replace = absint( get_post_meta( $post->ID, '_jps_cs_replaces', true ) );
            if ( 'auto-draft' === $post->post_status && isset( $_GET['jps_cs_replaces'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only preselects the field.
                $replace = absint( wp_unslash( $_GET['jps_cs_replaces'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only preselects the field.
            }
            $candidates = [];
            if ( 'products' === $scope ) {
                $product    = wc_get_product( $target );
                $candidates = $product instanceof \WC_Product ? Product_Tabs::global_tabs_for( $product ) : [];
            } else {
                $parent = wc_get_product( wp_get_post_parent_id( $target ) );
                foreach ( $parent instanceof \WC_Product ? self::parent_tabs( $parent ) : [] as $entry ) {
                    if ( 'variations' !== $entry['scope'] ) {
                        $candidates[] = $entry['post'];
                    }
                }
            }
            $label = 'products' === $scope ? __( 'Replace global tab', 'jp-catalog-suite' ) : __( 'Replace tab', 'jp-catalog-suite' );
            ?>
            <p><label for="jps-cs-replace-tab"><?php echo esc_html( $label ); ?></label>
            <select id="jps-cs-replace-tab" name="jps_cs_replaces">
                <option value="0"><?php esc_html_e( 'None', 'jp-catalog-suite' ); ?></option>
                <?php foreach ( $candidates as $candidate ) : ?>
                    <option value="<?php echo esc_attr( (string) $candidate->ID ); ?>" <?php selected( $replace, $candidate->ID ); ?>><?php echo esc_html( get_the_title( $candidate ) ); ?></option>
                <?php endforeach; ?>
            </select></p>
            <?php
        }
        ?>
        <p><label for="jps-cs-priority"><?php esc_html_e( 'Priority', 'jp-catalog-suite' ); ?></label>
        <input id="jps-cs-priority" name="jps_cs_priority" type="number" value="<?php echo esc_attr( (string) $priority ); ?>" /></p>
        <?php
    }

    public static function save_meta( int $post_id ): void {
        if ( ! isset( $_POST['jps_cs_tab_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jps_cs_tab_nonce'] ) ), 'jps_cs_tab_meta' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        $scope = isset( $_POST['jps_cs_scope'] ) ? sanitize_key( wp_unslash( $_POST['jps_cs_scope'] ) ) : 'all';
        if ( ! in_array( $scope, self::SCOPES, true ) ) {
            $scope = 'all';
        }
        $target = isset( $_POST['jps_cs_target'] ) ? absint( wp_unslash( $_POST['jps_cs_target'] ) ) : 0;
        $owner  = self::valid_owner( $scope, $target );
        if ( $owner ) {
            update_post_meta( $post_id, '_jps_cs_scope', $owner[0] );
            update_post_meta( $post_id, '_jps_cs_categories', 'categories' === $owner[0] ? [ $owner[1] ] : [] );
            update_post_meta( $post_id, '_jps_cs_products', 'products' === $owner[0] ? [ $owner[1] ] : [] );
            update_post_meta( $post_id, '_jps_cs_variations', 'variations' === $owner[0] ? [ $owner[1] ] : [] );
        }
        update_post_meta( $post_id, '_jps_cs_priority', isset( $_POST['jps_cs_priority'] ) ? sanitize_text_field( wp_unslash( $_POST['jps_cs_priority'] ) ) : '' );
        $replace      = isset( $_POST['jps_cs_replaces'] ) ? absint( wp_unslash( $_POST['jps_cs_replaces'] ) ) : 0;
        $replace_post = $replace ? get_post( $replace ) : null;
        $valid_replace = $replace_post instanceof \WP_Post
            && 'jps_cs_tab' === $replace_post->post_type
            && in_array( Product_Tabs::scope( $replace_post ), self::replaceable_scopes( Product_Tabs::scope( get_post( $post_id ) ) ), true );
        update_post_meta( $post_id, '_jps_cs_replaces', $valid_replace ? $replace : 0 );
    }

    /**
     * Scopes of the tabs a tab of the given scope may replace.
     *
     * @return string[]
     */
    private static function replaceable_scopes( string $owner_scope ): array {
        return [
            'products'   => [ 'all', 'categories' ],
            'variations' => [ 'all', 'categories', 'products' ],
        ][ $owner_scope ] ?? [];
    }

    /**
     * "Replace" starts from a copy of the replaced tab.
     *
     * @param string  $value Default title or content.
     * @param object  $post  Post being created.
     */
    public static function prefill_replacement( string $value, object $post ): string {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only prefills the new editor; saving remains protected by the tab nonce.
        $replaces = isset( $_GET['jps_cs_replaces'] ) ? absint( wp_unslash( $_GET['jps_cs_replaces'] ) ) : 0;
        if ( ! $replaces || 'jps_cs_tab' !== ( $post->post_type ?? '' ) ) {
            return $value;
        }
        $source = get_post( $replaces );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only prefills the new editor; saving remains protected by the tab nonce.
        $owner_scope = sanitize_key( wp_unslash( $_GET['jps_cs_scope'] ?? '' ) );
        if ( ! $source instanceof \WP_Post || 'jps_cs_tab' !== $source->post_type || ! in_array( Product_Tabs::scope( $source ), self::replaceable_scopes( $owner_scope ), true ) || ! current_user_can( 'edit_post', $source->ID ) ) {
            return $value;
        }
        return 'default_title' === current_filter() ? $source->post_title : $source->post_content;
    }

    /* ---------------------------------------------------------------- Owner deletion */

    public static function delete_tabs_of_deleted_product( int $post_id, \WP_Post $post ): void {
        if ( 'product' === $post->post_type ) {
            self::delete_tabs_with_raw_owner( '_jps_cs_products', $post_id );
        } elseif ( 'product_variation' === $post->post_type ) {
            self::delete_tabs_with_raw_owner( '_jps_cs_variations', $post_id );
        }
    }

    public static function delete_tabs_of_deleted_category( int $term_id ): void {
        self::delete_tabs_with_raw_owner( '_jps_cs_categories', $term_id );
    }

    /**
     * Compares the stored ID as is: deleting a translation must not delete tabs stored against the original.
     */
    private static function delete_tabs_with_raw_owner( string $meta_key, int $owner_id ): void {
        $tab_ids = get_posts( [ 'post_type' => 'jps_cs_tab', 'post_status' => array_values( get_post_stati() ), 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => true ] );
        foreach ( $tab_ids as $tab_id ) {
            $stored = array_map( 'absint', (array) get_post_meta( $tab_id, $meta_key, true ) );
            if ( ( $stored[0] ?? 0 ) === $owner_id ) {
                wp_delete_post( $tab_id, true );
            }
        }
    }
}
