<?php
/**
 * Inquiry item table; prices only for sent Quotes.
 *
 * @package JayPee\CatalogSuite
 *
 * @var WC_Order $order
 * @var bool     $plain_text
 * @var bool     $show_prices Optional; true renders the price column and totals.
 */

defined( 'ABSPATH' ) || exit;

$show_prices = ! empty( $show_prices );

if ( $plain_text ) {
    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        echo wp_strip_all_tags( $item->get_name() ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo esc_html__( 'SKU', 'jp-catalog-suite' ) . ': ' . esc_html( $product ? $product->get_sku() : '' ) . "\n";
        echo esc_html__( 'Quantity', 'jp-catalog-suite' ) . ': ' . esc_html( (string) $item->get_quantity() ) . "\n";
        if ( $show_prices ) {
            echo esc_html__( 'Price', 'jp-catalog-suite' ) . ': ' . wp_strip_all_tags( $order->get_formatted_line_subtotal( $item ) ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo wp_strip_all_tags( wc_display_item_meta( $item, [ 'echo' => false, 'separator' => ', ' ] ) ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    if ( $show_prices ) {
        foreach ( $order->get_order_item_totals() as $total ) {
            echo wp_strip_all_tags( $total['label'] ) . ' ' . wp_strip_all_tags( $total['value'] ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo "\n";
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
        <?php if ( $show_prices ) : ?>
            <th scope="col" style="text-align:left"><?php esc_html_e( 'Price', 'jp-catalog-suite' ); ?></th>
        <?php endif; ?>
    </tr></thead>
    <tbody>
    <?php foreach ( $order->get_items() as $item ) : ?>
        <?php $product = $item->get_product(); ?>
        <tr>
            <td><?php echo esc_html( $item->get_name() ); ?></td>
            <td><?php echo esc_html( $product ? $product->get_sku() : '' ); ?></td>
            <td><?php echo esc_html( (string) $item->get_quantity() ); ?></td>
            <td><?php echo wp_kses_post( wc_display_item_meta( $item, [ 'echo' => false, 'separator' => '<br>' ] ) ); ?></td>
            <?php if ( $show_prices ) : ?>
                <td><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?></td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <?php if ( $show_prices ) : ?>
        <tfoot>
        <?php foreach ( $order->get_order_item_totals() as $total ) : ?>
            <tr>
                <th scope="row" colspan="4" style="text-align:left"><?php echo wp_kses_post( $total['label'] ); ?></th>
                <td><?php echo wp_kses_post( $total['value'] ); ?></td>
            </tr>
        <?php endforeach; ?>
        </tfoot>
    <?php endif; ?>
</table>
