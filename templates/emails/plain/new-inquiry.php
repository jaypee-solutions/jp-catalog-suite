<?php
/** @var WC_Order $order */
/** @var WC_Email $email */
/** @var bool $sent_to_admin */
/** @var bool $plain_text */
/** @var string $template_dir */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email->get_heading(), $email );
echo esc_html__( 'A customer has submitted an inquiry.', 'jp-catalog-suite' ) . "\n\n";
wc_get_template( 'emails/inquiry-items.php', [ 'order' => $order, 'plain_text' => true ], '', $template_dir );
if ( $order->get_customer_note() ) {
    echo esc_html__( 'Customer message', 'jp-catalog-suite' ) . ":\n" . esc_html( $order->get_customer_note() ) . "\n\n";
}
wc_get_template( 'emails/inquiry-customer-details.php', [ 'order' => $order, 'plain_text' => true ], '', $template_dir );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_footer', $email );
