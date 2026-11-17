<?php
/** Quote mode wording integration tests. @package JayPee\CatalogSuite */

declare( strict_types = 1 );

final class Test_Quote_Mode extends WP_UnitTestCase {
    public function tear_down(): void {
        delete_option( 'jps_cs_quote_cart_label' );
        set_current_screen( 'front' );
        parent::tear_down();
    }

    private function woocommerce_string( string $translation, string $text = '' ): string {
        return apply_filters( 'gettext_woocommerce', $translation, '' === $text ? $translation : $text, 'woocommerce' );
    }

    public function test_variable_product_button_uses_the_inquiry_label(): void {
        $product = new WC_Product_Variable();
        $product->set_name( 'Quote mode rail' );
        $product->save();

        $this->assertSame( 'Add to inquiry', $product->single_add_to_cart_text() );
    }

    public function test_other_woocommerce_strings_lose_the_cart_noun_on_the_storefront(): void {
        $this->assertSame( 'Update inquiry basket', $this->woocommerce_string( 'Update cart' ) );
        $this->assertSame( 'Inquiry basket totals', $this->woocommerce_string( 'Cart totals' ) );
        $this->assertSame( 'Add to inquiry', $this->woocommerce_string( 'Add to cart' ) );

        update_option( 'jps_cs_quote_cart_label', 'Anfragekorb' );
        $this->assertSame( 'Ihr Anfragekorb ist leer.', $this->woocommerce_string( 'Ihr Warenkorb ist leer.', 'Your cart is empty.' ) );
    }

    public function test_admin_screens_keep_the_cart_wording(): void {
        set_current_screen( 'edit-post' );

        $this->assertSame( 'Update cart', $this->woocommerce_string( 'Update cart' ) );
    }
}
