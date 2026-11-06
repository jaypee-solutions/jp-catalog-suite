# Price-free Inquiry emails

## Status
Accepted

## Context
Customers and the sales team need confirmations when a submitted Inquiry becomes `jps-inquiry`. Standard order email templates can expose item prices, which do not belong in an Inquiry email.

## Decision
Register two WooCommerce `WC_Email` notifications for the pending-to-Inquiry transition: an admin New inquiry email and a customer Inquiry confirmation. Render dedicated HTML and plain templates that show products, SKU, quantity, item metadata, customer message and customer details without price columns or price helpers. Respect WooCommerce email settings, including enabled state, and remove both email settings on uninstall.

## Consequences
The admin recipient is configurable as comma-separated addresses and defaults to the site admin email; the message replies to the customer's billing email. The customer confirmation goes to the billing email. Emails preserve variable-length item details through WooCommerce's item-meta renderer. Email content is generated at delivery time and is not stored separately.
