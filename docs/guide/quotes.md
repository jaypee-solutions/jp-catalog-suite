# Quotes

Answer an Inquiry with a priced offer (German: **Angebot**) and let the customer accept it in My Account. For shop admins; the Quote module is part of **Quote mode**.

## Before you start

- WooCommerce and Catalog Suite are active and **Quote mode** is enabled ([Quote mode](quote-mode.md)).
- An Inquiry exists, with a valid billing email address ([Inquiry checkout and emails](inquiry-checkout-and-emails.md)).
- The **Quote** email is enabled under **WooCommerce → Settings → Emails**. If it is disabled or the Inquiry has no valid billing email, nothing is sent and an order note says so.
- You need a shop-manager or administrator account.

## Tutorial: Price an Inquiry and send the Quote

1. Open the Inquiry under **WooCommerce → Orders**. While its status is **Inquiry** (German: **Anfrage**) or **Quote sent** (German: **Angebot gesendet**), the items are editable.
2. Select **Add item(s)** if needed, enter the prices (edit the line items) and select **Recalculate** to update taxes and totals.
3. In the **Quote** box (German: **Angebot**) set **Valid until** (German: **Gültig bis**) and write a **Message to the customer** (German: **Nachricht an den Kunden**). Both are optional.
4. In **Order actions**, choose **Send quote to customer** (German: **Angebot an Kunden senden**) and select **Update**.

**Try it:** Use the Inquiry with *Priced Bolt* (`9.99`) and quantity `2`, set **Valid until** to a date a week from now, write `Delivery in 2 weeks.` and send.

**What you should see:** The status becomes **Quote sent** and an order note reads "Quote sent to the customer." The customer receives the **Quote** email (subject `Your quote for inquiry #{order_number} at {site_title}`) with items, line prices, totals, your message and the valid-until date. The email links to the order in My Account when the Inquiry belongs to a customer account; for guest Inquiries it asks the customer to reply instead.

To correct a Quote, change the items or the box and choose **Send quote to customer** again; the customer receives the revised Quote and the order note reads "Revised quote sent to the customer." The valid-until day is inclusive.

## Tutorial: The customer accepts the Quote

1. Log in as the customer who submitted the Inquiry and open **My Account → Inquiries**, then the order.
2. Above the order details, the **Your quote** section shows your message and the valid-until date, and the order details now show prices and totals.
3. Select **Accept quote** (German: **Angebot annehmen**).

**What you should see:** The notice "Thank you. You have accepted the quote." appears and the order is **On hold**. WooCommerce reduces stock; no payment is taken online. The shop recipients receive the **Quote accepted** email (subject `[{site_title}]: Quote #{order_number} accepted`), replying to the customer's billing email. Process the order from **On hold** by hand, for example by setting it to Processing once payment arrives.

**Try it:** Accept the Quote a second time (reload and resubmit the form): the notice says "This quote can no longer be accepted." and nothing changes. The same happens after the valid-until day, where the section reads "This quote expired on …" and the button is gone.

Guests cannot accept online. Their Quote email asks them to reply; set the order to **On hold** yourself when they agree.

## Settings reference

| Setting | Where | Default | Effect |
|---|---|---|---|
| Valid until | Order edit screen → Quote box | Empty | Last day the customer can accept; empty means no limit. |
| Message to the customer | Order edit screen → Quote box | Empty | Shown in the Quote email and in My Account. |
| Quote email | WooCommerce → Settings → Emails → Quote | Enabled | Customer email with the priced Quote; subject `Your quote for inquiry #{order_number} at {site_title}`, heading `Your quote`. |
| Quote accepted email | WooCommerce → Settings → Emails → Quote accepted | Enabled | Shop email; recipients default to the site administrator email. |

## Good to know

- Prices stay hidden from the customer until the first Quote is sent. After that they stay visible in My Account and emails, also after acceptance. The Total column of the My Account orders list stays hidden; prices appear in the order view.
- Taxes are shown in a sent Quote even though Quote mode disables taxes on the storefront.
- No PDF is generated.
- Quotes are excluded from WooCommerce Analytics like Inquiries; an accepted Quote is an `on-hold` order and is counted.
- Disabling Quote mode keeps existing Quotes visible, but sending and accepting them needs Quote mode.
- Quote data is stored in the order meta `_jps_cs_quote_sent`, `_jps_cs_quote_valid_until` and `_jps_cs_quote_message`; see [ADR 0022](../adr/0022-quotes-from-inquiries.md).

## For developers

- `jps_cs_quote_accepted` — action after a customer accepted a Quote and the order moved to `on-hold`; receives the `WC_Order`. See the [README hook reference](../../README.md#hook-reference).
