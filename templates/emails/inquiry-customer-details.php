<?php
/**
 * Customer details for Inquiry email templates.
 *
 * @package JayPee\CatalogSuite
 *
 * @var WC_Order $order
 * @var bool     $plain_text
 */

defined( 'ABSPATH' ) || exit;

$details = array_filter(
    [
        __( 'Name', 'jp-catalog-suite' )    => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
        __( 'Email', 'jp-catalog-suite' )   => $order->get_billing_email(),
        __( 'Phone', 'jp-catalog-suite' )   => $order->get_billing_phone(),
        __( 'Company', 'jp-catalog-suite' ) => $order->get_billing_company(),
    ]
);
$address = $order->get_formatted_billing_address();
if ( $plain_text ) {
    echo esc_html__( 'Customer details', 'jp-catalog-suite' ) . "\n";
    foreach ( $details as $label => $value ) {
        echo esc_html( $label ) . ': ' . esc_html( $value ) . "\n";
    }
    if ( $address ) {
        echo esc_html__( 'Billing address', 'jp-catalog-suite' ) . ': ' . esc_html( wp_strip_all_tags( $address ) ) . "\n";
    }
    echo "\n";
    return;
}
?>
<h2><?php esc_html_e( 'Customer details', 'jp-catalog-suite' ); ?></h2>
<ul>
    <?php foreach ( $details as $label => $value ) : ?>
        <li><strong><?php echo esc_html( $label ); ?>:</strong> <?php echo esc_html( $value ); ?></li>
    <?php endforeach; ?>
    <?php if ( $address ) : ?>
        <li><strong><?php esc_html_e( 'Billing address', 'jp-catalog-suite' ); ?>:</strong> <?php echo wp_kses_post( $address ); ?></li>
    <?php endif; ?>
</ul>
