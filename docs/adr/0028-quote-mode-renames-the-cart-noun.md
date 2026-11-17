# Quote mode renames the cart noun

## Status

Accepted. Extends 0021.

## Context

Quote mode relabelled a fixed list of WooCommerce strings (Cart, View cart, Add to cart, Proceed to checkout, Place order). Everything else still said "cart" or, in German, "Warenkorb": the Add to cart button of variable products (only simple products and variations were covered), "Update cart", "Cart totals", "added to your cart", mini-cart texts and accessibility labels. Customers saw a shopping cart in an inquiry shop.

## Decision

- Besides the fixed list, the `gettext_woocommerce`/`ngettext_woocommerce` PHP filters and the matching `i18n.gettext_woocommerce`/`i18n.ngettext_woocommerce` JavaScript filters (`assets/js/quote-blocks.js`) replace the noun in every other WooCommerce string with the Inquiry basket label: "Warenkorb" anywhere, and the English word "cart(s)". An English lower-case "cart" gets the label with a lower-case first letter ("Update inquiry basket").
- The replacement does not run on admin screens (except admin-ajax requests), so WooCommerce settings keep their wording.
- `single_add_to_cart_text` also covers variable products. The product list button of variable products keeps "Select options".
- Page titles stay WordPress content; sites rename them under Pages.

## Consequences

No cart wording is left in the storefront strings that go through the WooCommerce text domain. The replacement is a text substitution, so a label that needs another grammatical form (German articles, cases) may read slightly off in compound phrases; the Inquiry basket label should be a noun that fits "Ihr ___ ist leer". Strings outside the WooCommerce text domain, theme strings and page content are not touched.
