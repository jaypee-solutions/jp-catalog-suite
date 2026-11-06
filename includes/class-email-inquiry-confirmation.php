<?php
/**
 * Customer confirmation for a new Inquiry.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class Email_Inquiry_Confirmation extends \WC_Email {

    public function __construct() {
        $this->id             = 'jps_cs_inquiry_confirmation';
        $this->title          = __( 'Inquiry confirmation', 'jp-catalog-suite' );
        $this->description    = __( 'Sent to the customer when an inquiry is received.', 'jp-catalog-suite' );
        $this->customer_email = true;
        $this->heading        = __( 'Thank you for your inquiry', 'jp-catalog-suite' );
        $this->subject        = __( 'Your inquiry at {site_title}', 'jp-catalog-suite' );
        $this->template_base  = PLUGIN_DIR . 'templates/';
        $this->template_html  = 'emails/inquiry-confirmation.php';
        $this->template_plain = 'emails/plain/inquiry-confirmation.php';
        parent::__construct();
    }

    public function trigger( $order_id, $order = false ): void {
        $this->object = $order instanceof \WC_Order ? $order : ( $order_id instanceof \WC_Order ? $order_id : wc_get_order( $order_id ) );
        if ( ! $this->object || 'yes' !== $this->object->get_meta( '_jps_cs_inquiry' ) ) {
            return;
        }
        $this->setup_locale();
        $this->recipient = $this->object->get_billing_email();
        $this->placeholders['{order_number}'] = $this->object->get_order_number();
        $this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
        if ( $this->is_enabled() && $this->get_recipient() ) {
            $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), '' );
        }
        $this->restore_locale();
    }

    public function get_content_html(): string {
        return wc_get_template_html( $this->template_html, [ 'order' => $this->object, 'email' => $this, 'sent_to_admin' => false, 'plain_text' => false, 'template_dir' => $this->template_base ], '', $this->template_base );
    }

    public function get_content_plain(): string {
        return wc_get_template_html( $this->template_plain, [ 'order' => $this->object, 'email' => $this, 'sent_to_admin' => false, 'plain_text' => true, 'template_dir' => $this->template_base ], '', $this->template_base );
    }
}
