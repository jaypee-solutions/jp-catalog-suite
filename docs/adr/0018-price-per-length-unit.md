# ADR 0018: Price per length unit

## Status

Accepted

## Context

Variable-length products store the customer's length on each cart item. Shops that sell those products by length need a global opt-in and a product-level opt-in without changing Quote mode's price-free views.

## Decision

Add `jps_cs_length_pricing_enabled` to the existing Variable length settings section and `_jps_cs_length_price_per_unit` to eligible product metadata. When both toggles are active, capture the product's current edit-context price as `jps_cs_unit_price` in cart item data. At cart totals priority 20, multiply that captured unit price by the cart item's length and pass it through `jps_cs_length_item_price`; WooCommerce then multiplies by quantity. Append the product's resolved length unit to normal price HTML at priority 20. Quote mode's later price-hiding filter remains authoritative.

## Consequences

Price-per-length pricing is disabled by default and requires both toggles. Pricing uses the length already carried in cart item data, including data supplied by Store API integrations. No price is transformed when either toggle is off. The global option is removed by the existing `jps_cs_` uninstall cleanup, and the product flag is removed by the existing length-meta cleanup when uninstall deletion is opted into.
