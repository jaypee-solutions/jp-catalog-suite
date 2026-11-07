<?php
/** @var WC_Order $order */
/** @var WC_Email $email */
/** @var bool $sent_to_admin */
/** @var bool $plain_text */
/** @var string $template_dir */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email->get_heading() ) ) . "\n";
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
echo esc_html__( 'Thank you for your inquiry.', 'jp-catalog-suite' ) . "\n\n";
wc_get_template( 'emails/inquiry-items.php', [ 'order' => $order, 'plain_text' => true ], '', $template_dir );
if ( $order->get_customer_note() ) {
    echo esc_html__( 'Your message', 'jp-catalog-suite' ) . ":\n" . esc_html( $order->get_customer_note() ) . "\n\n";
}
wc_get_template( 'emails/inquiry-customer-details.php', [ 'order' => $order, 'plain_text' => true ], '', $template_dir );
echo esc_html( wp_strip_all_tags( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) );
