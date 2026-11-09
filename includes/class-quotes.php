<?php
/**
 * Priced Quotes sent from Inquiries and accepted by the customer in My Account.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class Quotes {
    private const STATUSES = [ 'jps-inquiry', 'jps-quote' ];

    private static bool $priced_view = false;

    public static function init(): void {
        add_filter( 'wc_order_is_editable', [ self::class, 'is_editable' ], 10, 2 );
        add_action( 'add_meta_boxes', [ self::class, 'add_meta_box' ], 10, 2 );
        add_action( 'woocommerce_process_shop_order_meta', [ self::class, 'save_meta_box' ], 45 );
        add_filter( 'woocommerce_order_actions', [ self::class, 'order_actions' ], 10, 2 );
        add_action( 'woocommerce_order_action_jps_cs_send_quote', [ self::class, 'send_quote' ] );
        add_filter( 'woocommerce_email_classes', [ self::class, 'register_emails' ] );
        add_action( 'woocommerce_view_order', [ self::class, 'render_account_quote' ], 5 );
        add_action( 'template_redirect', [ self::class, 'handle_accept' ], 5 );
        add_action( 'jps_cs_quote_accepted', [ self::class, 'send_accepted_notification' ] );
        add_action( 'woocommerce_order_details_before_order_table', [ self::class, 'begin_priced_view' ] );
        add_action( 'woocommerce_order_details_after_order_table', [ self::class, 'end_priced_view' ] );
        add_filter( 'wc_tax_enabled', [ self::class, 'tax_enabled' ], 20 );
    }

    public static function is_editable( bool $editable, \WC_Order $order ): bool {
        return self::is_open_inquiry( $order ) ? true : $editable;
    }

    /**
     * @param string                  $screen_id Order edit screen ID (HPOS or legacy).
     * @param \WP_Post|\WC_Order|null $source    Object being edited.
     */
    public static function add_meta_box( $screen_id, $source = null ): void {
        if ( wc_get_page_screen_id( 'shop-order' ) !== $screen_id ) {
            return;
        }
        $order = $source instanceof \WP_Post ? wc_get_order( $source->ID ) : $source;
        if ( ! $order instanceof \WC_Order || ! Inquiries::is_inquiry( $order ) ) {
            return;
        }
        add_meta_box( 'jps-cs-quote', __( 'Quote', 'jp-catalog-suite' ), [ self::class, 'render_meta_box' ], $screen_id, 'side', 'default' );
    }

    /** @param \WP_Post|\WC_Order $source Object being edited. */
    public static function render_meta_box( $source ): void {
        $order = $source instanceof \WP_Post ? wc_get_order( $source->ID ) : $source;
        if ( ! $order instanceof \WC_Order ) {
            return;
        }
        wp_nonce_field( 'jps_cs_quote_meta', 'jps_cs_quote_nonce' );
        ?>
        <p>
            <label for="jps_cs_quote_valid_until"><?php esc_html_e( 'Valid until', 'jp-catalog-suite' ); ?></label><br>
            <input type="date" id="jps_cs_quote_valid_until" name="jps_cs_quote_valid_until" value="<?php echo esc_attr( (string) $order->get_meta( '_jps_cs_quote_valid_until' ) ); ?>">
        </p>
        <p>
            <label for="jps_cs_quote_message"><?php esc_html_e( 'Message to the customer', 'jp-catalog-suite' ); ?></label><br>
            <textarea id="jps_cs_quote_message" name="jps_cs_quote_message" rows="4" style="width:100%"><?php echo esc_textarea( self::message( $order ) ); ?></textarea>
        </p>
        <?php
    }

    public static function save_meta_box( $order_id ): void {
        $nonce = isset( $_POST['jps_cs_quote_nonce'] ) ? sanitize_key( wp_unslash( $_POST['jps_cs_quote_nonce'] ) ) : '';
        // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this capability.
        if ( ! wp_verify_nonce( $nonce, 'jps_cs_quote_meta' ) || ! current_user_can( 'edit_shop_orders' ) ) {
            return;
        }
        $order = wc_get_order( (int) $order_id );
        if ( ! $order instanceof \WC_Order || ! Inquiries::is_inquiry( $order ) ) {
            return;
        }

        $valid_until = isset( $_POST['jps_cs_quote_valid_until'] ) ? sanitize_text_field( wp_unslash( $_POST['jps_cs_quote_valid_until'] ) ) : '';
        if ( self::is_valid_date( $valid_until ) ) {
            $order->update_meta_data( '_jps_cs_quote_valid_until', $valid_until );
        } else {
            $order->delete_meta_data( '_jps_cs_quote_valid_until' );
        }

        $message = isset( $_POST['jps_cs_quote_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['jps_cs_quote_message'] ) ) : '';
        if ( '' !== $message ) {
            $order->update_meta_data( '_jps_cs_quote_message', $message );
        } else {
            $order->delete_meta_data( '_jps_cs_quote_message' );
        }
        $order->save();
    }

    public static function order_actions( array $actions, $order = null ): array {
        if ( $order instanceof \WC_Order && self::is_open_inquiry( $order ) ) {
            $actions['jps_cs_send_quote'] = __( 'Send quote to customer', 'jp-catalog-suite' );
        }
        return $actions;
    }

    public static function send_quote( \WC_Order $order ): void {
        if ( ! self::is_open_inquiry( $order ) ) {
            return;
        }
        $email = WC()->mailer()->get_emails()['JPS_CS_Email_Quote'] ?? null;
        if ( ! $email || ! $email->is_enabled() || ! is_email( $order->get_billing_email() ) ) {
            $order->add_order_note( __( 'Quote not sent: the Quote email is disabled or the inquiry has no valid billing email.', 'jp-catalog-suite' ) );
            return;
        }
        $order->update_meta_data( '_jps_cs_quote_sent', (string) time() );
        if ( $order->has_status( 'jps-quote' ) ) {
            $order->add_order_note( __( 'Revised quote sent to the customer.', 'jp-catalog-suite' ) );
            $order->save();
        } else {
            $order->update_status( 'jps-quote', __( 'Quote sent to the customer.', 'jp-catalog-suite' ) );
        }
        $email->trigger( $order->get_id(), $order );
    }

    public static function message( \WC_Order $order ): string {
        return (string) $order->get_meta( '_jps_cs_quote_message' );
    }

    /** Formatted with the site date format; empty when no date is set. */
    public static function valid_until( \WC_Order $order ): string {
        $ymd = self::valid_until_ymd( $order );
        if ( '' === $ymd ) {
            return '';
        }
        return wp_date( wc_date_format(), ( new \DateTimeImmutable( $ymd, wp_timezone() ) )->getTimestamp() );
    }

    /** The valid-until day is inclusive. */
    public static function is_expired( \WC_Order $order ): bool {
        $ymd = self::valid_until_ymd( $order );
        return '' !== $ymd && wp_date( 'Y-m-d' ) > $ymd;
    }

    public static function register_emails( array $emails ): array {
        require_once PLUGIN_DIR . 'includes/class-email-quote.php';
        require_once PLUGIN_DIR . 'includes/class-email-quote-accepted.php';
        $emails['JPS_CS_Email_Quote']          = new Email_Quote();
        $emails['JPS_CS_Email_Quote_Accepted'] = new Email_Quote_Accepted();
        return $emails;
    }

    public static function render_account_quote( $order_id ): void {
        $order = wc_get_order( (int) $order_id );
        if ( ! $order instanceof \WC_Order || ! Inquiries::is_inquiry( $order ) || ! $order->has_status( 'jps-quote' ) ) {
            return;
        }
        wc_get_template(
            'myaccount/quote.php',
            [
                'order'       => $order,
                'message'     => self::message( $order ),
                'valid_until' => self::valid_until( $order ),
                'expired'     => self::is_expired( $order ),
            ],
            '',
            PLUGIN_DIR . 'templates/'
        );
    }

    public static function handle_accept(): void {
        if ( ! isset( $_POST['jps_cs_accept_quote'] ) ) {
            return;
        }
        $order_id = absint( wp_unslash( $_POST['jps_cs_accept_quote'] ) );
        $nonce    = isset( $_POST['_jps_cs_quote_nonce'] ) ? sanitize_key( wp_unslash( $_POST['_jps_cs_quote_nonce'] ) ) : '';
        $order    = $order_id ? wc_get_order( $order_id ) : false;
        // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this capability.
        $can_view = $order instanceof \WC_Order && current_user_can( 'view_order', $order_id );

        if (
            $can_view
            && wp_verify_nonce( $nonce, 'jps_cs_accept_quote_' . $order_id )
            && Inquiries::is_inquiry( $order )
            && $order->has_status( 'jps-quote' )
            && ! self::is_expired( $order )
        ) {
            $order->update_status( 'on-hold', __( 'Quote accepted by the customer.', 'jp-catalog-suite' ) );
            do_action( 'jps_cs_quote_accepted', $order );
            wc_add_notice( __( 'Thank you. You have accepted the quote.', 'jp-catalog-suite' ), 'success' );
        } else {
            wc_add_notice( __( 'This quote can no longer be accepted.', 'jp-catalog-suite' ), 'error' );
        }

        wp_safe_redirect( $can_view ? $order->get_view_order_url() : wc_get_page_permalink( 'myaccount' ) );
        exit;
    }

    public static function send_accepted_notification( \WC_Order $order ): void {
        $email = WC()->mailer()->get_emails()['JPS_CS_Email_Quote_Accepted'] ?? null;
        if ( $email ) {
            $email->trigger( $order->get_id(), $order );
        }
    }

    public static function begin_priced_view( \WC_Order $order ): void {
        self::$priced_view = Inquiries::is_inquiry( $order ) && Inquiries::quote_sent( $order );
    }

    public static function end_priced_view(): void {
        self::$priced_view = false;
    }

    public static function tax_enabled( bool $enabled ): bool {
        // Quote mode disables taxes on frontend requests, which would drop the VAT rows from a priced Quote.
        return self::$priced_view ? 'yes' === get_option( 'woocommerce_calc_taxes' ) : $enabled;
    }

    private static function is_open_inquiry( \WC_Order $order ): bool {
        return Inquiries::is_inquiry( $order ) && $order->has_status( self::STATUSES );
    }

    private static function is_valid_date( string $ymd ): bool {
        return 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $ymd, $parts ) && checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] );
    }

    private static function valid_until_ymd( \WC_Order $order ): string {
        $ymd = (string) $order->get_meta( '_jps_cs_quote_valid_until' );
        return self::is_valid_date( $ymd ) ? $ymd : '';
    }
}
