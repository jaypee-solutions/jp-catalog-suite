<?php
/** @var WC_Order $order */
/** @var WC_Email $email */
/** @var bool $sent_to_admin */
/** @var bool $plain_text */
/** @var string $template_dir */
/** @var string $quote_message */
/** @var string $quote_valid_until */
/** @var string $accept_url */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email->get_heading() ) ) . "\n";
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
echo esc_html__( 'Thank you for your inquiry. Here is our quote.', 'jp-catalog-suite' ) . "\n\n";
if ( '' !== $quote_message ) {
    echo esc_html( $quote_message ) . "\n\n";
}
wc_get_template( 'emails/inquiry-items.php', [ 'order' => $order, 'plain_text' => true, 'show_prices' => true ], '', $template_dir );
if ( '' !== $quote_valid_until ) {
    echo esc_html( sprintf( /* translators: %s: date */ __( 'This quote is valid until %s.', 'jp-catalog-suite' ), $quote_valid_until ) ) . "\n\n";
}
if ( '' !== $accept_url ) {
    echo esc_html( sprintf( /* translators: %s: URL */ __( 'Review and accept this quote in your account: %s', 'jp-catalog-suite' ), $accept_url ) ) . "\n\n";
} else {
    echo esc_html__( 'Reply to this email to accept this quote.', 'jp-catalog-suite' ) . "\n\n";
}
wc_get_template( 'emails/inquiry-customer-details.php', [ 'order' => $order, 'plain_text' => true ], '', $template_dir );
echo esc_html( wp_strip_all_tags( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) );
