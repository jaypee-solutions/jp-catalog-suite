# Inquiry checkout and emails

Configure the customer details collected with an Inquiry and the two price-free Inquiry notifications. The Quote emails are described in [Quotes](quotes.md). For shop admins.

## Before you start

- WooCommerce and Catalog Suite must be active, and **Quote mode** must be enabled in **WooCommerce → Settings → Catalog Suite → General**.
- You need a shop-manager or administrator account to edit settings and WooCommerce email templates/settings.
- Checkout requires first name, last name and email. Company, Phone, Address and Message can be configured separately.

## Tutorial: Configure Inquiry checkout fields

1. Open **WooCommerce → Settings → Catalog Suite → Quote mode**.
2. Set **Company** (German: **Unternehmen**) to **Optional**, **Required** (German: **Erforderlich**), or **Hidden** (German: **Ausgeblendet**).
3. Set **Phone** (German: **Telefon**) to **Optional**, **Required**, or **Hidden**.
4. Set **Address** (German: **Adresse**) to **Optional**, **Required**, or **Hidden**. Address covers address line 1 and 2, city, state and postcode; **Required** makes address line 2 mandatory too. Country remains available and required.
5. Enable **Message** (German: **Nachricht**) to let customers enter an order note, or clear it to remove the field in Classic checkout.
6. Save changes and open the storefront checkout to verify the fields and required markers.

**Try it:** Set Company to **Optional**, Phone to **Required**, Address to **Hidden**, and leave **Message** enabled. Open checkout and enter `030123456` as the phone number.

**What you should see:** Company is optional, Phone is required, the address fields are hidden, and Country remains available. First name, last name and email are still required. Message is available when the relevant checkout interface permits it.

## Tutorial: Send an Inquiry and inspect notifications

1. Add a product to the Inquiry basket and continue to checkout.
2. Fill the required name and email fields, and complete any configured required fields. Add a Message if enabled.
3. Submit using **Send inquiry**. No payment method is required.
4. Check the shop recipients' inbox for the **New inquiry** (German: **Neue Anfrage**) email (subject `[{site_title}]: New inquiry #{order_number}`). By default it is sent to the site administrator email address.
5. Check the customer's billing email inbox for the **Inquiry confirmation** (German: **Anfragebestätigung**) email (subject `Your inquiry at {site_title}`).

**Try it:** Use a test recipient you can access, submit an Inquiry with `alex@example.com`, company `Example GmbH`, and message `Please advise availability.` Verify both email destinations.

**What you should see:** The shop receives a New inquiry email and the customer receives an Inquiry confirmation email. Emails list products, SKU, quantity, item details, customer message and customer details, without prices. The shop email replies to the customer's billing email address.

## Tutorial: Change email recipients, subject, heading, or enabled state

1. In wp-admin, open **WooCommerce → Settings → Emails**.
2. Select **New inquiry** to edit the shop notification, or **Inquiry confirmation** to edit the customer notification.
3. For **New inquiry**, enter one or more **Recipient(s)** (German: **Empfänger**) separated by commas. The initial recipient is the site administrator email address.
4. Set the email's enabled state, **Subject**, or **Email heading** as needed. Each email has its own settings.
5. Save changes and submit a test Inquiry to verify delivery and the updated content.

**Try it:** Set New inquiry **Recipient(s)** to `orders@example.com, manager@example.com`, and set its subject to `[{site_title}]: Inquiry #{order_number}`. Keep Inquiry confirmation enabled for the customer.

**What you should see:** New inquiry goes to the configured recipient list, while Inquiry confirmation goes to the billing email on the Inquiry. Changing a recipient does not change the customer confirmation destination.

## Tutorial: Verify the final content

1. Read the New inquiry message as a shop recipient and confirm it contains the customer's email address for reply.
2. Read the Inquiry confirmation as the customer and confirm the product and quantity details are recognizable.
3. Check both HTML and plain-text email formats if your mail client or delivery service uses both.
4. If a message does not arrive, confirm that email type is enabled, check its recipient, and review your site's WooCommerce email delivery configuration.

**Try it:** Submit a new Inquiry with one product, quantity `2`, and message `Please advise availability.` Check that the product, quantity, and message appear in both notifications without any price.

**What you should see:** The shop email can be replied to at the customer's billing email; the confirmation is delivered to that billing address. Both messages describe the Inquiry without prices.

The subject's `{site_title}` and `{order_number}` placeholders are replaced by WooCommerce for each Inquiry. Do not include customer personal information in a subject; the body is where customer details and the message appear.

If an email was disabled when an Inquiry was submitted, enabling it does not resend that older notification. Submit a fresh test Inquiry after changing email settings.

## Tutorial: Check notification settings

1. In **WooCommerce → Settings → Emails**, open each email's settings page.
2. Confirm the enabled toggle is on for each message you expect to send.
3. Check that the New inquiry recipient list is current; comma-separated addresses are accepted.
4. Save the email settings and send a new test Inquiry. Each message is sent for the new Inquiry, so use a fresh submission when checking changes.

**Try it:** Submit one Inquiry to `alex@example.com` after setting New inquiry recipients to `orders@example.com, manager@example.com`.

**What you should see:** The shop message is addressed to the configured shop recipients. The confirmation is addressed to the billing email entered for that Inquiry.

WooCommerce placeholders such as `{site_title}` and `{order_number}` are used in the default email subjects. When changing a subject, keep a placeholder if you want those values included in the delivered subject.

The messages are separate WooCommerce email types: disabling one does not change the enabled state or recipient of the other. Check each type individually after editing.

## Tutorial: Check a checkout configuration

1. Save the settings, then open the storefront checkout with an item in the Inquiry basket.
2. Confirm the visible Company and Phone fields match their Optional/Required/Hidden selections.
3. If Address is Hidden, confirm Country still appears; if Address is Required, verify the address fields show as required where WooCommerce's country locale supports them.
4. Confirm the Message field's visibility using the appropriate Classic checkout or Checkout block control.
5. Submit a test Inquiry to confirm that a required field is actually enforced, not merely displayed.

**Try it:** Set Phone to **Required**, submit without entering a phone number and confirm checkout asks for it. Enter `030123456` and continue.

**What you should see:** The checkout accepts the configured field values and retains the required name and email fields.

## Settings reference

| Setting | Where | Default | Effect |
|---|---|---|---|
| Company | WooCommerce → Settings → Catalog Suite → Quote mode | Optional | Show optional/required company field or hide it. |
| Phone | Catalog Suite → Quote mode | Optional | Show optional/required phone field or hide it. |
| Address | Catalog Suite → Quote mode | Hidden | Show address fields as optional/required or hide them; Country remains. |
| Message | Catalog Suite → Quote mode | Enabled (`yes`) | Controls the message/order-notes field where supported. |
| New inquiry enabled | WooCommerce → Settings → Emails → New inquiry | Enabled (`yes`) | Enables or disables the shop notification. |
| New inquiry recipient(s) | New inquiry email settings | Site administrator email | Comma-separated shop notification recipients. |
| New inquiry subject / Email heading | New inquiry email settings | `[{site_title}]: New inquiry #{order_number}` / `New inquiry` | Controls shop email subject and heading. |
| Inquiry confirmation enabled | WooCommerce → Settings → Emails → Inquiry confirmation | Enabled (`yes`) | Enables or disables the customer notification. |
| Inquiry confirmation subject / Email heading | Inquiry confirmation email settings | `Your inquiry at {site_title}` / `Thank you for your inquiry` | Controls customer email subject and heading. |

## Good to know

- WooCommerce Classic checkout honors the Message setting. Blocks checkout does not remove the order-notes field through that setting; use the Checkout block's own order-notes setting to control whether the message field appears.
- Blocks checkout cannot hide Country, which stays preselected to the store country. It cannot make order notes required. The Address setting overrides WooCommerce's per-country address rules: it applies the same visibility and required state to address line 1 and 2, city, state and postcode in every country (for example, **Required** makes State mandatory for Germany, and **Optional** shows State for Denmark, where WooCommerce normally hides it).
- First name, last name and email remain required; customer delivery of Inquiry confirmation depends on a valid billing email and successful WooCommerce email delivery.
- While Quote mode is on, the Company and Phone settings here replace the Checkout block's own Company and Phone options; changing those in the block editor has no effect. If you use a caching layer, test checkout as a guest after saving; cached checkout content may not reflect the latest settings.
- Prices are omitted from both Inquiry emails; the Quote email sent later carries them ([Quotes](quotes.md)). See [Quote mode](quote-mode.md) for catalog, Inquiry basket and label behavior.




## For developers

Feature hooks are documented in the [README hook reference](../../README.md#hook-reference).

- `jps_cs_inquiry_created` — action after the Inquiry has been created; the registered notifications are triggered for that Inquiry.
