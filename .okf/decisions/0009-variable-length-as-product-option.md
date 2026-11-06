---
type: decision
title: Variable length as product option
description: Decision to model customer-entered length as simple product cart item data.
---

# Variable length as product option

Variable length is implemented for simple products. WooCommerce quantity counts pieces; each piece's submitted length is validated against the resolved product configuration and retained on its cart line and order item. Store defaults are configurable, and `jps_cs_length_config` exposes the resolved values. Variation products, Store API add-to-cart, and price-per-unit calculation are excluded from this phase.

See [ADR 0009](/docs/adr/0009-variable-length-as-product-option.md).
