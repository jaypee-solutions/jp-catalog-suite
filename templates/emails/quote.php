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

do_action( 'woocommerce_email_header', $email->get_heading(), $email );
?>
<p><?php esc_html_e( 'Thank you for your inquiry. Here is our quote.', 'jp-catalog-suite' ); ?></p>
<?php if ( '' !== $quote_message ) : ?>
    <p><?php echo nl2br( esc_html( $quote_message ) ); ?></p>
<?php endif; ?>
<?php wc_get_template( 'emails/inquiry-items.php', [ 'order' => $order, 'plain_text' => false, 'show_prices' => true ], '', $template_dir ); ?>
<?php if ( '' !== $quote_valid_until ) : ?>
    <p><?php echo esc_html( sprintf( /* translators: %s: date */ __( 'This quote is valid until %s.', 'jp-catalog-suite' ), $quote_valid_until ) ); ?></p>
<?php endif; ?>
<?php if ( '' !== $accept_url ) : ?>
    <p><a href="<?php echo esc_url( $accept_url ); ?>"><?php esc_html_e( 'Accept quote', 'jp-catalog-suite' ); ?></a></p>
<?php else : ?>
    <p><?php esc_html_e( 'Reply to this email to accept this quote.', 'jp-catalog-suite' ); ?></p>
<?php endif; ?>
<?php wc_get_template( 'emails/inquiry-customer-details.php', [ 'order' => $order, 'plain_text' => false ], '', $template_dir ); ?>
<?php do_action( 'woocommerce_email_footer', $email ); ?>
