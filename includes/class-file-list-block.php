<?php
/**
 * Server-rendered protected file list block.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class File_List_Block {

    public static function init(): void {
        add_action( 'init', [ self::class, 'register_block' ] );
    }

    public static function register_block(): void {
        wp_register_script( 'jps-cs-file-list-block', plugins_url( 'blocks/file-list/edit.js', PLUGIN_FILE ), [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render', 'media-editor', 'media-views' ], VERSION, true );
        add_action( 'enqueue_block_editor_assets', 'wp_enqueue_media' );
        register_block_type(
            PLUGIN_DIR . 'blocks/file-list',
            [
                'editor_script'   => 'jps-cs-file-list-block',
                'render_callback' => [ self::class, 'render' ],
            ]
        );
        wp_set_script_translations( 'jps-cs-file-list-block', 'jp-catalog-suite', PLUGIN_DIR . 'languages' );
    }

    public static function render( array $attributes ): string {
        $ids = array_unique( array_filter( array_map( 'absint', (array) ( $attributes['files'] ?? [] ) ) ) );
        if ( ! $ids ) {
            return '';
        }
        $product_id = 0;
        if ( function_exists( 'is_product' ) && is_product() ) {
            $product_id = absint( get_queried_object_id() );
        } elseif ( isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof \WC_Product ) {
            $product_id = $GLOBALS['product']->get_id();
        } elseif ( isset( $GLOBALS['post'] ) && 'product' === get_post_type( $GLOBALS['post'] ) ) {
            $product_id = absint( $GLOBALS['post']->ID );
        }
        $items = [];
        foreach ( $ids as $id ) {
            $attachment = get_post( $id );
            $path = get_attached_file( $id );
            if ( ! $attachment || 'attachment' !== $attachment->post_type || ! $path || ! is_file( $path ) ) {
                continue;
            }
            $title = get_the_title( $id );
            $extension = strtoupper( pathinfo( $path, PATHINFO_EXTENSION ) );
            $size = size_format( (int) filesize( $path ) );
            $protected = '1' === get_post_meta( $id, '_jps_cs_protected', true );
            $url = '';
            $login_required = false;
            if ( $protected ) {
                if ( Access_Block::can_access( wp_get_current_user(), [ 'attachment_id' => $id ] ) ) {
                    $url = add_query_arg( 'jps_cs_file', $id, home_url( '/' ) );
                    if ( $product_id ) {
                        $url = add_query_arg( 'product', $product_id, $url );
                    }
                } else {
                    $login_required = true;
                }
            } else {
                $url = wp_get_attachment_url( $id );
            }
            $label = esc_html( $title ) . ' <span class="jps-cs-file-extension">(' . esc_html( $extension ) . ')</span> <span class="jps-cs-file-size">' . esc_html( $size ) . '</span>';
            if ( $login_required ) {
                $label .= ' <span class="jps-cs-login-required">(' . esc_html__( 'login required', 'jp-catalog-suite' ) . ')</span>';
            }
            $items[] = '<li>' . ( $url ? '<a href="' . esc_url( $url ) . '">' . $label . '</a>' : $label ) . '</li>';
        }
        return $items ? '<ul class="jps-cs-file-list">' . implode( '', $items ) . '</ul>' : '';
    }
}
