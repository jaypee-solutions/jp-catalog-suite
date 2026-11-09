<?php
/**
 * Admin notification for an accepted Quote.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class Email_Quote_Accepted extends \WC_Email {

    public function __construct() {
        $this->id             = 'jps_cs_quote_accepted';
        $this->title          = __( 'Quote accepted', 'jp-catalog-suite' );
        $this->description    = __( 'Sent to the shop manager when a customer accepts a quote.', 'jp-catalog-suite' );
        $this->customer_email = false;
        $this->heading        = __( 'Quote accepted', 'jp-catalog-suite' );
        $this->subject        = __( '[{site_title}]: Quote #{order_number} accepted', 'jp-catalog-suite' );
        $this->template_base  = PLUGIN_DIR . 'templates/';
        $this->template_html  = 'emails/quote-accepted.php';
        $this->template_plain = 'emails/plain/quote-accepted.php';
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
        if ( ! $this->object || ! Inquiries::is_inquiry( $this->object ) ) {
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
        return $this->render( $this->template_html, false );
    }

    public function get_content_plain(): string {
        return $this->render( $this->template_plain, true );
    }

    public function get_headers(): string {
        $headers = preg_replace( '/^Reply-to:.*\r\n/mi', '', parent::get_headers() );
        $reply_to = $this->object ? sanitize_email( $this->object->get_billing_email() ) : '';
        if ( $reply_to && is_email( $reply_to ) ) {
            $headers .= 'Reply-To: ' . $reply_to . "\r\n";
        }
        return $headers;
    }

    private function render( string $template, bool $plain_text ): string {
        Quotes::begin_priced_view( $this->object );
        $content = wc_get_template_html( $template, [ 'order' => $this->object, 'email' => $this, 'sent_to_admin' => true, 'plain_text' => $plain_text, 'template_dir' => $this->template_base ], '', $this->template_base );
        Quotes::end_priced_view();
        return $content;
    }
}
