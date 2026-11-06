<?php
/**
 * Price-free Inquiry notifications.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class Inquiry_Emails {
    private static array $sent_order_ids = [];

    public static function init(): void {
        add_action( 'jps_cs_inquiry_created', [ self::class, 'send' ] );
        add_action( 'woocommerce_order_status_pending_to_jps-inquiry_notification', [ self::class, 'send' ], 10, 2 );
        add_filter( 'woocommerce_email_actions', [ self::class, 'email_actions' ] );
        add_filter( 'woocommerce_email_classes', [ self::class, 'register_emails' ] );
    }

    public static function email_actions( array $actions ): array {
        $actions[] = 'woocommerce_order_status_pending_to_jps-inquiry';
        return $actions;
    }

    public static function send( $order_id, $order = false ): void {
        $order = $order instanceof \WC_Order ? $order : ( $order_id instanceof \WC_Order ? $order_id : wc_get_order( $order_id ) );
        if ( ! $order || 'yes' !== $order->get_meta( '_jps_cs_inquiry' ) || isset( self::$sent_order_ids[ $order->get_id() ] ) ) {
            return;
        }
        self::$sent_order_ids[ $order->get_id() ] = true;
        $emails = WC()->mailer()->get_emails();
        if ( isset( $emails['JPS_CS_Email_New_Inquiry'], $emails['JPS_CS_Email_Inquiry_Confirmation'] ) ) {
            $emails['JPS_CS_Email_New_Inquiry']->trigger( $order->get_id(), $order );
            $emails['JPS_CS_Email_Inquiry_Confirmation']->trigger( $order->get_id(), $order );
        }
    }


    public static function register_emails( array $emails ): array {
        require_once PLUGIN_DIR . 'includes/class-email-new-inquiry.php';
        require_once PLUGIN_DIR . 'includes/class-email-inquiry-confirmation.php';
        $emails['JPS_CS_Email_New_Inquiry']            = new Email_New_Inquiry();
        $emails['JPS_CS_Email_Inquiry_Confirmation'] = new Email_Inquiry_Confirmation();
        return $emails;
    }
}
