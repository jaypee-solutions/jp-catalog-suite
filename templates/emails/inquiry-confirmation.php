<?php
/** @var WC_Order $order */
/** @var WC_Email $email */
/** @var bool $sent_to_admin */
/** @var bool $plain_text */
/** @var string $template_dir */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email->get_heading(), $email );
?>
<p><?php esc_html_e( 'Thank you for your inquiry.', 'jp-catalog-suite' ); ?></p>
<?php wc_get_template( 'emails/inquiry-items.php', [ 'order' => $order, 'plain_text' => false ], '', $template_dir ); ?>
<?php if ( $order->get_customer_note() ) : ?>
    <h2><?php esc_html_e( 'Your message', 'jp-catalog-suite' ); ?></h2>
    <p><?php echo nl2br( esc_html( $order->get_customer_note() ) ); ?></p>
<?php endif; ?>
<?php wc_get_template( 'emails/inquiry-customer-details.php', [ 'order' => $order, 'plain_text' => false ], '', $template_dir ); ?>
<?php do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email ); ?>
<?php do_action( 'woocommerce_email_footer', $email ); ?>
