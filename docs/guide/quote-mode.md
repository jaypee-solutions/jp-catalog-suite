# Quote mode

Use **Quote mode** (German: **Angebotsmodus**) to turn the shop into an Inquiry shop: customers can request an offer without seeing prices or paying online. For shop admins and editors.

## Before you start

- WooCommerce and Catalog Suite must be active.
- You need a shop-manager or administrator account to change WooCommerce settings and products.
- Decide which products are available for Inquiries. Quote mode hides prices across the storefront but does not publish draft products or make external/grouped products purchasable.

## Tutorial: Enable Quote mode and prepare a price-free catalog

1. In wp-admin, open **WooCommerce → Settings → Catalog Suite → General** (German: **Allgemein**).
2. Enable **Quote mode** and save changes.
3. Open **Products → All Products**, edit a product, and confirm it is published. Add a regular price if the product has a price; a published simple product or variation without a price can also be added to an Inquiry basket in Quote mode.
4. Open the product on the storefront. Its price is hidden. Repeat for any other products you want customers to select.
5. Ensure WooCommerce Cart and Checkout pages are assigned under **WooCommerce → Settings → Advanced → Page setup**. In Quote mode the cart and checkout buttons and links use the Inquiry labels; the page titles stay as set under **Pages**.

**Try it:** Publish a simple product named *Quote Profile* with SKU `QP-1` and no price, then publish *Priced Bolt* with SKU `QP-2` and regular price `9.99`. Both can be selected while Quote mode is on; neither displays a price to the customer.

**What you should see:** Product and catalog prices are absent, including for price-free purchasable simple products and variations. Quote mode removes shipping, tax and coupon behavior from the customer-facing flow. WooCommerce stock and backorder rules still decide what can be added, but sending an Inquiry does not reduce stock; stock is reduced only if you later move the Inquiry to Processing, Completed or On hold.

## Tutorial: Add products and send an Inquiry

1. With Quote mode enabled, open a published simple product and select **Add to inquiry** (German: **Zur Anfrage hinzufügen**). Variable products keep WooCommerce's own button label.
2. Add another product if needed. Open the **Inquiry basket** (German: **Anfragekorb**) and review the selected products and quantities.
3. Select **Continue to inquiry** (German: **Weiter zur Anfrage**).
4. Enter the required first name, last name and email address on checkout. Complete any other fields the shop has configured.
5. Select **Send inquiry** (German: **Anfrage absenden**). No payment method is needed.

**Try it:** Add *Quote Profile* and *Priced Bolt*, set each quantity to `1`, and submit the Inquiry using `Alex Editor` and `alex@example.com` as the contact details.

**What you should see:** The thank-you page confirms receipt. The Inquiry has status **Inquiry** (German: **Anfrage**), has no payment due, and is excluded from WooCommerce Analytics. If a customer is logged in, the My Account menu entry is **Inquiries** (German: **Anfragen**) while Quote mode is active.

## Tutorial: Find Inquiries and turn Quote mode off

1. In wp-admin, open **WooCommerce → Orders**. Find the submitted record; its status is **Inquiry**. It remains listed if Quote mode is subsequently disabled.
2. To restore normal shop behavior, open **WooCommerce → Settings → Catalog Suite → General**, clear **Quote mode**, and save.
3. Check a product and checkout as a customer: standard WooCommerce price, basket, payment and checkout behavior resumes for new activity.

**Try it:** Search WooCommerce → Orders for the customer email `alex@example.com`, verify the Inquiry status, then disable Quote mode and check that the same record is still listed.

**What you should see:** Existing Inquiries stay visible and keep their status; disabling Quote mode does not convert or delete them. The My Account menu uses standard order wording again when Quote mode is off.

## Tutorial: Configure and translate the four customer labels

1. Open **WooCommerce → Settings → Catalog Suite → Quote mode**.
2. Enter your preferred wording in **Inquiry basket label**, **Add to inquiry label**, **Continue to inquiry label**, and **Send inquiry label**. Leave any field empty to use its translated default.
3. Save changes and check the product page, basket, checkout, and mini-cart.
4. For German, the default first-mention labels are **Inquiry basket** (Anfragekorb), **Add to inquiry** (Zur Anfrage hinzufügen), **Continue to inquiry** (Weiter zur Anfrage), and **Send inquiry** (Anfrage absenden). Translate/customize the strings through your site's translation workflow, or use the settings fields for site-specific wording.

**Try it:** Set the four fields to `Request basket`, `Add item to request`, `Review request`, and `Submit request`; leave the first field empty instead if you want to test its translated default.

**What you should see:** The configured labels appear in the corresponding WooCommerce customer-facing controls. Cart and checkout page titles are WordPress page titles; change those separately under **Pages**.

## Tutorial: Verify the complete storefront flow

1. Open the shop catalog as a guest and check that product prices are not shown.
2. Open a published simple product and verify its add button uses the configured label.
3. Add two products, then open the Inquiry basket. Check that products and quantities remain visible without item prices or totals.
4. Continue to checkout and verify the configured continuation label and submit label.
5. Send a test Inquiry and check the thank-you message. For checkout fields and email delivery, follow [Inquiry checkout and emails](inquiry-checkout-and-emails.md).

**Try it:** Repeat these checks with *Quote Profile* (no price) and *Priced Bolt* (`9.99`) in both the shop and product pages.

**What you should see:** Both products can be selected, and no price is displayed in the catalog, product page, Inquiry basket, checkout, or confirmation.







## Tutorial: Confirm that an Inquiry is price-free

1. Open the submitted Inquiry in WooCommerce → Orders and confirm its status is **Inquiry**.
2. Inspect the customer-facing order confirmation and the customer's My Account view.
3. If you are testing email content, follow the email guide and check both the shop and customer messages.

**Try it:** Log in as a customer, submit an Inquiry, then open My Account → **Inquiries** and find it there without prices. Then submit one while logged out: a guest Inquiry is not linked to any account, so find it under WooCommerce → Orders.

**What you should see:** Customer-facing totals are not displayed for an Inquiry. Inquiries are excluded from Analytics, but they remain order records in the WooCommerce administration.






## Settings reference

| Setting | Where | Default | Effect |
|---|---|---|---|
| Quote mode | WooCommerce → Settings → Catalog Suite → General | Off (`no`) | Enables price-free Inquiry behavior when enabled. |
| Inquiry basket label | Catalog Suite → Quote mode | Empty | Overrides the translated **Inquiry basket** label. |
| Add to inquiry label | Catalog Suite → Quote mode | Empty | Overrides the translated **Add to inquiry** label. |
| Continue to inquiry label | Catalog Suite → Quote mode | Empty | Overrides the translated checkout-navigation label. |
| Send inquiry label | Catalog Suite → Quote mode | Empty | Overrides the submit button label. |

## Good to know

- Guest checkout follows WooCommerce's guest-checkout setting. Name and email remain required.
- Quote mode does not change product publication status. External and grouped products are not made purchasable by this module. If a product cannot be selected, confirm that it is published and supported.
- Quote mode removes taxes on frontend, WooCommerce AJAX and Store API requests; back-office tax behavior is not globally changed. Coupons and shipping are disabled in Quote mode.
- Inquiry status registration and Analytics exclusion remain active when Quote mode is off. See [Inquiry checkout and emails](inquiry-checkout-and-emails.md) for customer fields and notifications.
- With Germanized active, Quote mode removes Germanized shopmarks and all Germanized legal checkboxes except `privacy`. See [Germanized](germanized.md).
- Prices are hidden, not confidential. The WooCommerce Store API product and cart JSON still contains the prices of priced products, and the Blocks cart and checkout hide prices with CSS, so they remain in the page source.
- If you want a customer's Inquiry in My Account, the customer must be logged in when they submit it. Guest Inquiries are not linked to an account; find those under WooCommerce → Orders.
- Inquiries remain price-free in customer-facing order views and WooCommerce emails when Quote mode is off. Admin order screens retain prices.
- The label filter adjusts the complete label map. Keep the keys `cart`, `add_to_cart`, `proceed`, and `place_order` in the returned map so each customer-facing control receives a value.
- `jps_cs_quote_mode_applies_to_product` controls per-product behavior, such as price display, price-free purchasability, and the **Add to inquiry** label. Basket and checkout behavior stays shop-wide while Quote mode is active: basket prices are hidden, payment, shipping, taxes and coupons are off, and every checkout becomes an Inquiry. This filter cannot run Quote mode for selected products only.


## For developers

Feature hooks are documented in the [README hook reference](../../README.md#hook-reference).

- `jps_cs_quote_mode_active` — filter whether Quote mode is active. It only takes effect while the module is enabled and cannot enable Quote mode on its own.
- `jps_cs_quote_mode_applies_to_product` — filter whether per-product Quote mode behavior applies to a product. It does not limit basket or checkout behavior.
- `jps_cs_quote_labels` — filter the complete customer-facing label map (`cart`, `add_to_cart`, `proceed`, `place_order`).
- `jps_cs_inquiry_created` — action after an Inquiry is created.
