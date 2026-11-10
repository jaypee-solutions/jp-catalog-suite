# Germanized and Quote mode

When Germanized is active alongside Quote mode, Catalog Suite removes Germanized's shopmarks and unregisters every Germanized legal checkbox whose ID is not kept (default: `privacy`), in every location (checkout, pay for order, register form, reviews). For shop admins and developers.

## Before you start

- WooCommerce, Catalog Suite, and WooCommerce Germanized must be active.
- Enable **Quote mode** (German: **Angebotsmodus**) under **WooCommerce → Settings → Catalog Suite → General**. The integration has no effect while Quote mode is off.
- To test checkbox retention, open **WooCommerce → Settings → Germanized → Legal Checkboxes** (Checkboxen), edit **New account** (Neues Kundenkonto), enable it and add the **Checkout** (Kasse) location. Germanized shows this checkbox at checkout only to logged-out visitors who create an account there, so also tick **Account creation → During checkout** under **WooCommerce → Settings → Accounts & Privacy**, and test in a logged-out browser.

## Tutorial: Check Germanized notices with Quote mode

1. Confirm **WooCommerce Germanized** is active under **Plugins → Installed Plugins**.
2. Open **WooCommerce → Settings → Catalog Suite → General**, enable **Quote mode**, and save.
3. Open a product page that normally displays Germanized price or shipping notices.
4. Add the product to the Inquiry basket and open checkout.

**Try it:** Use a published priced product such as *Priced Bolt* (SKU `QP-2`, price `9.99`) and visit its product page, then add it and open checkout.

**What you should see:** Catalog Suite hides product prices in Quote mode and Germanized shopmarks/legal notices are removed from product, catalog, basket, checkout and order views. No payment is required to send the Inquiry.

## Tutorial: Confirm checkout checkboxes

1. In **WooCommerce → Settings → Germanized → Legal Checkboxes**, edit **New account**, enable it and add the **Checkout** location. Under **WooCommerce → Settings → Accounts & Privacy**, tick **Account creation → During checkout**.
2. Ensure Quote mode remains enabled. In a logged-out browser, visit checkout with an item in the Inquiry basket. If guest checkout is enabled, tick WooCommerce's create-account option so Germanized shows the checkbox.
3. Inspect the checkout checkbox list before submitting.
4. If your store has custom Germanized checkbox IDs, see the developer filter below before deciding which ones should remain.

**Try it:** With the New account checkbox enabled for checkout, open checkout logged out and choose to create an account. The privacy checkbox remains; Germanized's legal checkbox for Terms and Conditions and Cancellation Policy is removed by default in Quote mode.

**What you should see:** The privacy checkbox remains when Germanized has enabled it, while other Germanized legal checkboxes are removed by default. Germanized-specific controls run only if Germanized is active. German Market is not supported.

## Tutorial: Compare with Quote mode off

1. Record the current Quote mode setting before changing it.
2. Clear **Quote mode** in **WooCommerce → Settings → Catalog Suite → General** and save.
3. Open the same product and checkout pages again.
4. Compare the Germanized notices and legal checkbox behavior with the Quote mode test.
5. Restore the original Quote mode setting after the comparison.

**Try it:** Use the same product and checkout country for both visits so you are comparing the same Germanized configuration.

**What you should see:** With Quote mode off, Catalog Suite's Germanized removal callbacks do not remove Germanized shopmarks or checkboxes. Germanized itself still controls whether particular notices and checkboxes are enabled.

The integration is conditional on both plugins: Germanized must be active, and Quote mode must be active for the cleanup methods to run. If a notice remains, first verify both conditions and then check whether the notice is a Germanized shopmark in one of the supported product, catalog, basket, checkout or order views.

Do not assume this behavior modifies legal text embedded in page content or supplied by unrelated extensions; this integration targets Germanized's shopmark getters and legal checkbox manager.


1. Ask your developer to identify the Germanized checkbox ID you need to retain.
2. Have the developer include that ID in the list returned by `jps_cs_germanized_kept_checkboxes`.
3. With Quote mode active, repeat the logged-out checkout test and verify the selected checkbox remains.

**Try it:** If the required checkbox is not `privacy`, have your developer retain its actual ID and inspect checkout while creating an account.

**What you should see:** Germanized keeps the checkbox IDs in the filter's returned list and removes other IDs while Quote mode is active.

The filter only controls checkbox retention. It does not restore Germanized shopmarks, prices, payment, taxes or shipping behavior removed by Quote mode. When Germanized is disabled or Quote mode is off, Catalog Suite does not remove its checkboxes.


## Tutorial: Retest after changing settings

1. Save changes to Quote mode or Germanized checkbox settings.
2. Add a product to the Inquiry basket and open checkout in a logged-out browser. Choose to create an account so Germanized shows its New account checkbox.
3. Verify the privacy checkbox is present and the Germanized legal checkbox for Terms and Conditions and Cancellation Policy is absent under the default integration.
4. Submit a test Inquiry and check its thank-you page and order view for Germanized shopmarks.

**Try it:** With the New account checkbox enabled for checkout and Quote mode on, submit an Inquiry logged out with account creation selected and the privacy checkbox ticked. This also creates a customer account.

**What you should see:** The privacy checkbox remains, other Germanized legal checkboxes are removed by default, and legal notices/shopmarks are absent from Quote mode views.

## Settings reference

| Setting | Where | Default | Effect |
|---|---|---|---|
| Quote mode | WooCommerce → Settings → Catalog Suite → General | Off (`no`) | Activates the Catalog Suite changes to Germanized checkout and shopmarks. |
| Germanized privacy checkbox (**New account**) | WooCommerce → Settings → Germanized → Legal Checkboxes → New account | Off; location Register form | Kept by default. Shown at checkout only after you add the Checkout location, and only to logged-out visitors creating an account. |
| Kept Germanized checkbox IDs | Developer filter `jps_cs_germanized_kept_checkboxes` | `privacy` | Selects Germanized legal checkbox IDs not removed during Quote mode. |

## Good to know

- This integration removes Germanized's price/legal shopmarks throughout the customer-facing product, catalog, basket, checkout and order views while Quote mode is active.
- By default, the only retained Germanized legal checkbox ID is `privacy`. If Germanized's checkbox ID differs due to another extension or customization, adjust the filter to retain the intended ID.
- While Quote mode is on, the removed checkboxes are also missing from **WooCommerce → Settings → Germanized → Legal Checkboxes**. Switch Quote mode off to edit them.
- German Market is unsupported. Do not assume this compatibility behavior applies to German Market.
- For checkout Company, Phone, Address and Message settings, see [Inquiry checkout and emails](inquiry-checkout-and-emails.md). For the full price-free customer flow, see [Quote mode](quote-mode.md).




## For developers

Feature hooks are documented in the [README hook reference](../../README.md#hook-reference).

- `jps_cs_germanized_kept_checkboxes` — filter the list of Germanized checkbox IDs retained in Quote mode; defaults to `privacy`.
