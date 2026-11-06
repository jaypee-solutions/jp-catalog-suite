# ADR 0009: Variable length as product option

## Status
Accepted

## Context
Customers need to specify the length of each piece for simple products while WooCommerce quantity continues to count pieces. Length configuration is product metadata, while store defaults belong to Catalog Suite settings.

## Decision
Implement Variable length only for simple products in this phase. Validate the submitted length against product/store minimum, maximum, and step; preserve it as cart item data and order item metadata. Keep Store API add-to-cart, variable products, and price-per-unit calculation out of this module. The public `jps_cs_length_config` filter allows consumers to adjust the resolved configuration.

## Consequences
Each distinct length creates a distinct cart line. The value is displayed in cart and order details and stored with a stable machine-readable key. Future work can extend product-type support and pricing without changing quantity semantics.
