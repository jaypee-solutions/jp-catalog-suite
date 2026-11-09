# Variable length

Let customers enter the Length of each piece; WooCommerce Quantity still counts pieces. Administrators and shop managers configure Variable length for simple or variable products.

## Before you start

- Enable the module at **WooCommerce → Settings → Catalog Suite → General** by ticking **Variable length** (Variable Länge) and saving. Its settings are in the **Variable length** section next to General.
- You need a simple or variable product and an Administrator or Shop manager account; by default, WordPress Editors cannot edit WooCommerce products or settings.
- Choose whether customers buy at prices or submit an Inquiry in Quote mode. Quote mode hides prices; it does not remove the length input or its validation.

## Tutorial: Set store defaults

Use these defaults whenever a product field is left blank. Fresh installation defaults are unit `cm`, minimum `1`, maximum `1000`, and step `1`.

1. Open **WooCommerce → Settings → Catalog Suite → Variable length**.
2. In **Variable length defaults** (Standardeinstellungen für variable Länge), set **Unit** (Einheit) to `cm`, **Minimum length** (Mindestlänge) to `1`, **Maximum length** (Höchstlänge) to `1000`, and **Length step** (Längenschritt) to `1`.
3. Save changes. WooCommerce checks the minimum and maximum as a pair: the minimum must be greater than zero and not above the maximum. If the pair is invalid, neither value is saved and the previously saved values stay.

**Try it:** Set unit to `cm`, minimum to `10`, maximum to `300`, step to `1`. A product without overrides then accepts lengths from 10 through 300 cm in increments of 1 cm.

**What you should see:** The saved defaults are used by enabled products whose corresponding fields are blank.

## Tutorial: Enable Variable length on a simple product

1. Open **Products → All Products**, edit a simple product, and open **Product data → General**.
2. Check **Variable length** (Variable Länge). This enables length input for the product.
3. Set **Length unit** (Längeneinheit), or choose **Use store default** (Shop-Standard verwenden). Set **Minimum length**, **Maximum length**, **Length step**, and **Default length** (Standardlänge) as needed. Leave a field blank to inherit its store default; when no default length is stored, the minimum is used as the initial input value.
4. Update the product.

**Try it:** On a profile product, enable Variable length, set unit `cm`, minimum `10`, maximum `300`, step `1`, and default length `120`.

**What you should see:** The product page shows a required number input labelled **Length (cm)** (Länge (cm)), initially `120`, with `min` 10, `max` 300 and `step` 1. The browser blocks empty, out-of-range or misaligned values with its own validation message. A request that bypasses the browser check is rejected by the server with “Please enter a length between 10 and 300 cm.” (Bitte geben Sie eine Länge zwischen 10 und 300 cm ein.) or “The length must be a multiple of 1 cm.” (Die Länge muss ein Vielfaches von 1 cm sein.)

## Tutorial: Configure a variable product and variation limits

1. Edit a variable product and, in **Product data → General**, check **Variable length**. Set its unit and any parent-level minimum, maximum, step, and default. These parent values provide the inherited configuration.
2. Open **Product data → Variations** and expand a variation.
3. Optionally set **Minimum length override** (Abweichende Mindestlänge), **Maximum length override** (Abweichende Höchstlänge), **Length step override** (Abweichender Längenschritt), or **Default length override** (Abweichende Standardlänge). Leave an override blank to inherit that value from the parent.
4. Save the variations and update the product. Select a variation on the product page.

**Try it:** Set parent bounds to 10–300 cm, step 1 and default 10. For the Blue variation, set maximum override to `200` and default override to `20`.

**What you should see:** Selecting Blue updates the form's active maximum and default; clearing the selection restores the parent configuration. The server validates against the selected variation's limits when the customer adds it.

## Tutorial: Add pieces and review their Length

1. Visit the product page and enter a valid Length for one piece.
2. Set WooCommerce **Quantity** to the number of pieces and select **Add to cart** (on simple products in Quote mode: **Add to inquiry**, Zur Anfrage hinzufügen). Repeat with another Length if needed.
3. Open the cart (the Inquiry basket in Quote mode). Submit checkout to create an order (an Inquiry in Quote mode), then open it under **WooCommerce → Orders**.

**Try it:** Add the same profile at `120 cm`, then again at `150 cm`, quantity `1` each.

**What you should see:** Cart lines show each Length separately (`120 cm` and `150 cm`); a different Length forms a separate line even for the same product. Quantity counts pieces, not combined length. The order or Inquiry line displays **Length: 120 cm** in admin and in WooCommerce and Inquiry emails that list item details.

## Tutorial: Use the loop button and block add-to-cart

1. In a product listing, locate a product with Variable length enabled. Select **Choose length** (Länge auswählen) to open its product page instead of adding a piece without a Length.
2. On a product page, use the visible Length input and the product form's **Add to cart** button (**Add to inquiry** on simple products in Quote mode). Length-enabled products have WooCommerce AJAX add-to-cart disabled.
3. If your product block's add-to-cart action does not submit the Length (for example the **Add to Cart + Options (Beta)** block), use WooCommerce's **Add to Cart with Options** block (`woocommerce/add-to-cart-form`, used by the default single-product template) for that product. Check that its request includes the entered Length.
4. For Store API integrations, pass `jps_cs_length` or the `extensions.jp-catalog-suite.jps_cs_length` request extension. The integration must send the selected variation attributes when applicable; server-side validation uses its resolved limits.

**Try it:** On the product you configured in the simple-product tutorial (unit `cm`, 10–300, step 1), enter `120` and add it using its product form; then check the cart or Inquiry basket for `120 cm`.

**What you should see:** Add-to-cart preserves the submitted Length and validates it against the active product or variation bounds. A Store API add-to-cart request without a valid Length is rejected with the length error, so a block that omits the Length cannot add the product; use the **Add to Cart with Options** block (`woocommerce/add-to-cart-form`).

## Tutorial: Price pieces by length (optional)

1. Open **WooCommerce → Settings → Catalog Suite → Variable length** and enable **Price per length unit** (Preis pro Längeneinheit). Its default is off.
2. Edit an eligible simple or variable product at **Products → All Products → Edit → Product data → General** and check the product-level **Price per length unit** option, which appears when the global option is enabled. The unit price is the product's current WooCommerce price: for a simple product, its regular price (or active sale price) in **Product data → General**; for a variable product, each variation's regular price (or active sale price) in **Product data → Variations**.
3. Save and test outside Quote mode. Catalog Suite sets each piece's price to unit price × entered Length; WooCommerce then applies Quantity: unit price × Length × Quantity.

**Try it:** Set a `cm` product's regular price to `0.50`, enable its product-level pricing flag, and add Length `120` with Quantity `2`. The line amount is `0.50 × 120 × 2 = 120` in the store currency.

**What you should see:** The product price display appends ` / cm`; cart totals use the computed piece price. Quote mode hides prices, so customers do not see the unit price or calculated price in that mode. Length pricing requires the global **Price per length unit** setting plus the product's **Variable length** and **Price per length unit** checkboxes.

## Settings reference

| Setting | Where | Default | Effect |
|---|---|---:|---|
| Variable length | WooCommerce → Settings → Catalog Suite → General | Off | Activates the module. |
| Unit | Variable length → Variable length defaults | `cm` | Default unit; available units are `mm`, `cm`, `m`. |
| Minimum length | Variable length defaults | `1` | Inclusive lower bound; must be greater than zero and not above the maximum submitted with it. |
| Maximum length | Variable length defaults | `1000` | Inclusive upper bound; must not be below the minimum submitted with it. |
| Length step | Variable length defaults | `1` | Permitted increment from the minimum. |
| Variable length | Product data → General | Off | Enables the input on a simple or variable product. |
| Length unit | Product data → General | Store default | Product-level unit; blank inherits store unit. |
| Minimum / Maximum / Length step | Product data → General | Store defaults | Product bounds and increment; blank fields inherit. |
| Default length | Product data → General | Minimum | Initial input value; blank falls back to the minimum. |
| Minimum / Maximum / Length step / Default length override | Variation panel | Inherited | Optional variation-specific values; blank inherits parent. |
| Price per length unit | Variable length settings | Off | Allows product-level pricing opt-in. |
| Price per length unit | Product data → General | Off | Enables length multiplication for that product when global option is on. |

## Good to know

- Length is per piece; Quantity counts pieces. Each length is included in cart item data and stored with the order item.
- A variation inherits unit and enablement from its parent; its four numeric overrides control its bounds, step, and initial value.
- The browser input communicates required bounds and step. Server validation enforces positive Length, inclusive bounds, and alignment to `minimum + n × step`.
- WooCommerce AJAX add-to-cart is disabled for products with Variable length. Some product blocks may omit the input in their Store API request; use the **Add to Cart with Options** block (`woocommerce/add-to-cart-form`) when that happens.
- Price per length unit is optional and disabled by default. It requires the global setting, the product's **Variable length** checkbox and its **Price per length unit** flag. The product's current WooCommerce price (each variation's price on variable products, including an active sale price) is the unit price; quantity is applied after multiplying by Length.
- Quote mode hides prices. For its customer-facing behavior, see [Quote mode](quote-mode.md).
- For every tutorial, use a Length that fits the selected product or variation's own limits; product overrides take precedence over store defaults.

## For developers

- `jps_cs_length_config` filters the resolved configuration (`enabled`, `unit`, `min`, `max`, `step`, `default`, `price_per_unit`) of a simple or variable product. For variations it receives the parent product, and non-blank variation overrides are applied after the filter.
- `jps_cs_length_item_price` adjusts calculated length-based cart-item pricing.
- See the [README hook reference](../../README.md#hook-reference) for hook signatures and the complete reference.
