<?php
/**
 * Priced Quote with the accept form, shown above the order details in My Account.
 *
 * @package JayPee\CatalogSuite
 *
 * @var WC_Order $order
 * @var string   $message
 * @var string   $valid_until
 * @var bool     $expired
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="jps-cs-quote">
    <h2><?php esc_html_e( 'Your quote', 'jp-catalog-suite' ); ?></h2>
    <?php if ( '' !== $message ) : ?>
        <p><?php echo nl2br( esc_html( $message ) ); ?></p>
    <?php endif; ?>
    <?php if ( '' !== $valid_until ) : ?>
        <p>
            <?php
            echo esc_html(
                sprintf(
                    $expired
                        /* translators: %s: date */
                        ? __( 'This quote expired on %s.', 'jp-catalog-suite' )
                        /* translators: %s: date */
                        : __( 'This quote is valid until %s.', 'jp-catalog-suite' ),
                    $valid_until
                )
            );
            ?>
        </p>
    <?php endif; ?>
    <?php if ( ! $expired ) : ?>
        <form method="post" action="<?php echo esc_url( $order->get_view_order_url() ); ?>">
            <?php wp_nonce_field( 'jps_cs_accept_quote_' . $order->get_id(), '_jps_cs_quote_nonce' ); ?>
            <input type="hidden" name="jps_cs_accept_quote" value="<?php echo esc_attr( (string) $order->get_id() ); ?>">
            <button type="submit" class="button wp-element-button"><?php esc_html_e( 'Accept quote', 'jp-catalog-suite' ); ?></button>
        </form>
    <?php endif; ?>
</section>
