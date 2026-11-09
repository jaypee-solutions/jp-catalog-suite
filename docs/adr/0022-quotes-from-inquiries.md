# Quotes from Inquiries

## Status
Accepted

## Context
ADR 0002 and 0003 left the seller's Quote (Angebot) out of the plugin's scope, so the workflow ended with the Inquiry. Shop managers need to price an Inquiry and send the customer a binding offer the customer can accept without online payment.

## Decision
The Quote is now in scope. Status `jps-quote` ("Quote sent") and the order meta `_jps_cs_quote_sent` (timestamp), `_jps_cs_quote_valid_until` (`Y-m-d`, inclusive) and `_jps_cs_quote_message` carry it. Inquiries in `jps-inquiry` or `jps-quote` are editable in the order screen so the shop manager can set prices; the order action "Send quote to customer" moves the Inquiry to `jps-quote` and sends the Quote email, and can be repeated for a revised quote. Prices become visible in My Account and emails once `_jps_cs_quote_sent` is set. A logged-in customer accepts the Quote in My Account → order view; the order becomes `on-hold` (stock is reduced, no online payment) and the shop manager gets a "Quote accepted" email. Guests accept offline by replying to the email. No PDF is generated. The status and price visibility stay core (like `jps-inquiry`); the workflow (editing, sending, accepting, emails) runs with the Quote module.

## Consequences
ADR 0002 and 0003 stay valid for the Inquiry itself; only their scope statement about the Quote is amended. `jps-quote` is excluded from Analytics like `jps-inquiry` until the order is accepted. Accepted Quotes keep prices visible and are processed by hand from `on-hold`. The Total column stays hidden in the My Account order list.
