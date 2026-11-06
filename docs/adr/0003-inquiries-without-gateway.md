# Inquiries without gateway

## Status
Accepted

## Context
Inquiry checkout must work through WooCommerce classic and Store API checkout without payment while preserving standard stock and backorder rules.

## Decision
Quote mode makes carts not need payment. Mark checkout orders with `_jps_cs_inquiry`; when WooCommerce calls `payment_complete()` for a pending inquiry, move it to `jps-inquiry` from `woocommerce_pre_payment_complete` and fire `jps_cs_inquiry_created`. Register the Inquiry status and Analytics exclusion independently of the module toggle. Do not reduce stock, set a paid date, or run paid-order processing.

## Consequences
No payment gateway is required. Existing Inquiry records remain identifiable and listed after Quote mode is disabled. The plugin covers intake only; downstream offer and order lifecycle is outside scope.
