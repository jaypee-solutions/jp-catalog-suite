<?php
/**
 * Audience-gated product tab content.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class Access_Block {

    public static function init(): void {
        add_action( 'init', [ self::class, 'register_block' ] );
    }

    public static function register_block(): void {
        wp_register_script( 'jps-cs-access-block', plugins_url( 'blocks/access/edit.js', PLUGIN_FILE ), [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ], VERSION, true );
        register_block_type(
            PLUGIN_DIR . 'blocks/access',
            [
                'editor_script' => 'jps-cs-access-block',
                'render_callback' => [ self::class, 'render' ],
            ]
        );
        wp_set_script_translations( 'jps-cs-access-block', 'jp-catalog-suite', PLUGIN_DIR . 'languages' );
        add_filter( 'woocommerce_registration_redirect', [ self::class, 'registration_redirect' ] );
        add_filter( 'registration_redirect', [ self::class, 'registration_redirect' ] );
    }

    public static function can_access( ?\WP_User $user, array $context = [] ): bool {
        return (bool) apply_filters( 'jps_cs_can_access_protected', $user instanceof \WP_User && $user->exists(), $user, $context );
    }

    public static function render( array $attributes, string $content, \WP_Block $block ): string {
        $user = wp_get_current_user();
        if ( is_user_logged_in() ) {
            self::mark_page_uncacheable();
        }
        $product = $block->context['postId'] ?? get_the_ID();
        $context = [ 'product' => wc_get_product( absint( $product ) ) ];
        $allowed = self::can_access( $user, $context );
        $mode = $attributes['mode'] ?? 'only-with-access';
        if ( ! in_array( $mode, [ 'only-with-access', 'only-without-access' ], true ) ) {
            $mode = 'only-with-access';
        }
        if ( ( 'only-with-access' === $mode ) === $allowed ) {
            return do_blocks( $content );
        }
        if ( empty( $attributes['showNotice'] ) ) {
            return '';
        }
        return self::notice( is_user_logged_in(), $context['product'] instanceof \WC_Product ? $context['product'] : null );
    }

    public static function notice( bool $logged_in, ?\WC_Product $product ): string {
        if ( $logged_in ) {
            return '<p class="jps-cs-protected-notice">' . esc_html__( 'You do not have permission to view this content.', 'jp-catalog-suite' ) . '</p>';
        }
        $url = $product ? get_permalink( $product->get_id() ) : '';
        if ( ! $url ) {
            $url = home_url( add_query_arg( [], $GLOBALS['wp']->request ?? '' ) );
        }
        $register = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : '';
        if ( $register && 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) {
            $register = add_query_arg( 'redirect_to', rawurlencode( $url ), $register );
        } elseif ( get_option( 'users_can_register' ) ) {
            $register = wp_registration_url();
            $register = add_query_arg( 'redirect_to', rawurlencode( $url ), $register );
        } else {
            $register = '';
        }
        $register = $register ? wp_validate_redirect( $register, '' ) : '';
        $template = (string) get_option( 'jps_cs_protected_notice', __( 'Please {login} or {register} to view this content.', 'jp-catalog-suite' ) );
        if ( ! $register ) {
            $template = preg_replace( '/\s+\S+\s+\{register\}/u', '', $template ) ?? $template;
        }
        $login_url = wp_login_url( $url );
        $tokens = [ '{login}' => '<a href="' . esc_url( $login_url ) . '">' . esc_html__( 'log in', 'jp-catalog-suite' ) . '</a>', '{register}' => $register ? '<a href="' . esc_url( $register ) . '">' . esc_html__( 'register', 'jp-catalog-suite' ) . '</a>' : '' ];
        $html = strtr( $template, $tokens );
        $html = apply_filters( 'jps_cs_protected_notice_html', $html, $product );
        return '<p class="jps-cs-protected-notice">' . wp_kses( $html, [ 'a' => [ 'href' => [], 'rel' => [], 'class' => [] ] ] ) . '</p>';
    }

    public static function registration_redirect( string $redirect ): string {
        // Registration submits to the account URL, whose query can include our validated product destination.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a redirect target, validated before use; no state changes.
        if ( ! isset( $_REQUEST['redirect_to'] ) || ! is_string( $_REQUEST['redirect_to'] ) ) {
            return $redirect;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The redirect destination is validated below.
        $requested = esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) );
        return wp_validate_redirect( $requested, $redirect );
    }


    private static function mark_page_uncacheable(): void {
        if ( ! defined( 'DONOTCACHEPAGE' ) ) {
            define( 'DONOTCACHEPAGE', true );
        }
        nocache_headers();
    }

    public static function settings(): array {
        return [
            [ 'title' => __( 'Protected notice', 'jp-catalog-suite' ), 'type' => 'title', 'id' => 'jps_cs_access_settings' ],
            [ 'title' => __( 'Protected notice', 'jp-catalog-suite' ), 'id' => 'jps_cs_protected_notice', 'type' => 'textarea', 'default' => __( 'Please {login} or {register} to view this content.', 'jp-catalog-suite' ), 'desc' => __( 'Use {login} and {register} for links.', 'jp-catalog-suite' ), 'sanitize_callback' => [ self::class, 'sanitize_notice' ] ],
            [ 'type' => 'sectionend', 'id' => 'jps_cs_access_settings' ],
        ];
    }

    public static function sanitize_notice( string $value ): string {
        return wp_kses( $value, [] );
    }
}
