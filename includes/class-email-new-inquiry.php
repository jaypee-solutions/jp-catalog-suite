<?php
/**
 * Admin notification for a new Inquiry.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class Email_New_Inquiry extends \WC_Email {

    public function __construct() {
        $this->id             = 'jps_cs_new_inquiry';
        $this->title          = __( 'New inquiry', 'jp-catalog-suite' );
        $this->description    = __( 'Sent to the shop manager when a customer submits an inquiry.', 'jp-catalog-suite' );
        $this->customer_email = false;
        $this->heading        = __( 'New inquiry', 'jp-catalog-suite' );
        $this->subject        = __( '[{site_title}]: New inquiry #{order_number}', 'jp-catalog-suite' );
        $this->template_base  = PLUGIN_DIR . 'templates/';
        $this->template_html  = 'emails/new-inquiry.php';
        $this->template_plain = 'emails/plain/new-inquiry.php';
        $this->recipient      = get_option( 'admin_email' );
        parent::__construct();
    }

    public function init_form_fields(): void {
        parent::init_form_fields();
        $this->form_fields['recipient'] = [
            'title'       => __( 'Recipient(s)', 'jp-catalog-suite' ),
            'type'        => 'text',
            'description' => __( 'Enter recipients (comma-separated). Defaults to the site administrator email address.', 'jp-catalog-suite' ),
            'default'     => get_option( 'admin_email' ),
        ];
    }

    public function trigger( $order_id, $order = false ): void {
        $this->object = $order instanceof \WC_Order ? $order : ( $order_id instanceof \WC_Order ? $order_id : wc_get_order( $order_id ) );
        if ( ! $this->object || 'yes' !== $this->object->get_meta( '_jps_cs_inquiry' ) ) {
            return;
        }
        $this->setup_locale();
        $this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
        $this->placeholders['{order_number}'] = $this->object->get_order_number();
        $this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
        if ( $this->is_enabled() && $this->get_recipient() ) {
            $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), '' );
        }
        $this->restore_locale();
    }

    public function get_content_html(): string {
        return wc_get_template_html( $this->template_html, [ 'order' => $this->object, 'email' => $this, 'sent_to_admin' => true, 'plain_text' => false, 'template_dir' => $this->template_base ], '', $this->template_base );
    }

    public function get_content_plain(): string {
        return wc_get_template_html( $this->template_plain, [ 'order' => $this->object, 'email' => $this, 'sent_to_admin' => true, 'plain_text' => true, 'template_dir' => $this->template_base ], '', $this->template_base );
    }

    public function get_headers(): string {
        $headers = parent::get_headers();
        $reply_to = $this->object ? sanitize_email( $this->object->get_billing_email() ) : '';
        if ( $reply_to && is_email( $reply_to ) ) {
            $headers .= 'Reply-To: ' . $reply_to . "\r\n";
        }
        return $headers;
    }
}
