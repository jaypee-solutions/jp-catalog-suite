<?php
/**
 * Customer email carrying the priced Quote.
 *
 * @package JayPee\CatalogSuite
 */

declare( strict_types = 1 );

namespace JayPee\CatalogSuite;

final class Email_Quote extends \WC_Email {

    public function __construct() {
        $this->id             = 'jps_cs_quote';
        $this->title          = __( 'Quote', 'jp-catalog-suite' );
        $this->description    = __( 'Sent to the customer when you send a quote from an inquiry.', 'jp-catalog-suite' );
        $this->customer_email = true;
        $this->heading        = __( 'Your quote', 'jp-catalog-suite' );
        $this->subject        = __( 'Your quote for inquiry #{order_number} at {site_title}', 'jp-catalog-suite' );
        $this->template_base  = PLUGIN_DIR . 'templates/';
        $this->template_html  = 'emails/quote.php';
        $this->template_plain = 'emails/plain/quote.php';
        parent::__construct();
    }

    public function trigger( $order_id, $order = false ): void {
        $this->object = $order instanceof \WC_Order ? $order : ( $order_id instanceof \WC_Order ? $order_id : wc_get_order( $order_id ) );
        if ( ! $this->object || ! Inquiries::is_inquiry( $this->object ) ) {
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
        return $this->render( $this->template_html, false );
    }

    public function get_content_plain(): string {
        return $this->render( $this->template_plain, true );
    }

    private function render( string $template, bool $plain_text ): string {
        Quotes::begin_priced_view( $this->object );
        $content = wc_get_template_html(
            $template,
            [
                'order'             => $this->object,
                'email'             => $this,
                'sent_to_admin'     => false,
                'plain_text'        => $plain_text,
                'template_dir'      => $this->template_base,
                'quote_message'     => Quotes::message( $this->object ),
                'quote_valid_until' => Quotes::valid_until( $this->object ),
                'accept_url'        => $this->object->get_customer_id() ? $this->object->get_view_order_url() : '',
            ],
            '',
            $this->template_base
        );
        Quotes::end_priced_view();
        return $content;
    }
}
