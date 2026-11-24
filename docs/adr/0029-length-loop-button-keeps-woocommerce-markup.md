# Length loop button keeps WooCommerce markup

## Status

Accepted. Amends 0009.

## Context

Product listings showed length-enabled products with an unstyled underlined link "Choose length" (German "Länge auswählen"), while other variable products showed WooCommerce's styled "Select options" button. The `woocommerce_loop_add_to_cart_link` filter replaced the whole button HTML with a bare `<a class="button">`; the Product Button block runs that filter on its finished markup, so the block wrapper and its `wp-block-button__link wp-element-button` classes were lost. The replacement also covered variable products, where the customer picks a variant before the length, so "Choose length" mislabelled the first step.

## Decision

- The listing button keeps WooCommerce's markup. Variable length changes only its text (`woocommerce_product_add_to_cart_text`, priority 20, after quote mode's "Add to inquiry" at 10) and its URL (`woocommerce_product_add_to_cart_url`, the product permalink).
- Only length-enabled simple products read "Choose length". Length-enabled variable products keep WooCommerce's "Select options", which already links to the product page.
- AJAX add to cart stays disabled for length-enabled products, so the Product Button block renders a link to `add_to_cart_url()` instead of an AJAX button.

## Consequences

Listing buttons look the same for all products and follow theme and block styling. The product page button is unaffected (`woocommerce_product_single_add_to_cart_text`). Variable products give no hint in the listing that a length is also required; the product page asks for it after the variant.
