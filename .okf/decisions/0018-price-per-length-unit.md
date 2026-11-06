---
type: decision
title: Price per length unit
description: Opt-in pricing based on each cart item's customer-entered length.
tags:
  - pricing
  - variable-length
timestamp: 2026-11-06
---

# Price per length unit

The shop-level `jps_cs_length_pricing_enabled` option and per-product `_jps_cs_length_price_per_unit` flag both enable per-length pricing. When both are active, capture the product's current edit-context price as `jps_cs_unit_price` in cart item data. At cart totals priority 20, multiply that captured unit price by the cart item's `jps_cs_length` value and pass it through `jps_cs_length_item_price`; WooCommerce applies quantity. Price HTML appends the resolved length unit. Quote mode's higher-priority price hiding remains authoritative.

The global option is removed by the `jps_cs_` option cleanup. The product flag is included in the existing length-meta deletion when the uninstall deletion opt-in is active.
