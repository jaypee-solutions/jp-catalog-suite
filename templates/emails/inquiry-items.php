<?php
/**
 * Price-free Inquiry item table.
 *
 * @package JayPee\CatalogSuite
 *
 * @var WC_Order $order
 * @var bool     $plain_text
 */

defined( 'ABSPATH' ) || exit;

if ( $plain_text ) {
    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        echo wp_strip_all_tags( $item->get_name() ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo esc_html__( 'SKU', 'jp-catalog-suite' ) . ': ' . esc_html( $product ? $product->get_sku() : '' ) . "\n";
        echo esc_html__( 'Quantity', 'jp-catalog-suite' ) . ': ' . esc_html( (string) $item->get_quantity() ) . "\n";
        echo wp_strip_all_tags( wc_display_item_meta( $item, [ 'echo' => false, 'separator' => ', ' ] ) ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    return;
}
?>
<table cellspacing="0" cellpadding="6" style="width:100%;border:1px solid #e5e5e5" border="1">
    <thead><tr>
        <th scope="col" style="text-align:left"><?php esc_html_e( 'Product', 'jp-catalog-suite' ); ?></th>
        <th scope="col" style="text-align:left"><?php esc_html_e( 'SKU', 'jp-catalog-suite' ); ?></th>
        <th scope="col" style="text-align:left"><?php esc_html_e( 'Quantity', 'jp-catalog-suite' ); ?></th>
        <th scope="col" style="text-align:left"><?php esc_html_e( 'Details', 'jp-catalog-suite' ); ?></th>
    </tr></thead>
    <tbody>
    <?php foreach ( $order->get_items() as $item ) : ?>
        <?php $product = $item->get_product(); ?>
        <tr>
            <td><?php echo esc_html( $item->get_name() ); ?></td>
            <td><?php echo esc_html( $product ? $product->get_sku() : '' ); ?></td>
            <td><?php echo esc_html( (string) $item->get_quantity() ); ?></td>
            <td><?php echo wp_kses_post( wc_display_item_meta( $item, [ 'echo' => false, 'separator' => '<br>' ] ) ); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
