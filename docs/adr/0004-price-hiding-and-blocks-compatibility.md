# Price hiding and Blocks compatibility

## Status
Accepted

## Context
Quote mode is an inquiry flow, so customer-facing product and inquiry screens must not present prices. WooCommerce renders prices through PHP templates, Blocks, structured data and account/email order views; existing inquiries must remain price-free even when the quote-mode toggle is later disabled.

## Decision
Hide quote-mode product prices with WooCommerce price, variation, sale-badge, cart, structured-data and catalog-sorting filters. Add a quote-mode body class and scoped CSS for Blocks totals that have no PHP filter. The Store API is not rewritten: WooCommerce product and cart JSON may expose raw prices for priced products. For orders flagged as Inquiries, remove order item totals and line subtotals in customer-facing order views and emails, and hide the formatted order total outside wp-admin; leave administrator order screens unchanged. In Quote mode, label the My Account Orders endpoint Inquiries and remove its total column.

## Consequences
Price hiding is presentation only, not a confidentiality boundary; API consumers can still see raw prices. CSS selectors must follow WooCommerce Blocks markup changes. Inquiry price suppression is keyed to Inquiry status/metadata and remains independent of the Quote mode toggle.
