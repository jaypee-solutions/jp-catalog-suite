---
type: Decision
title: Variable length through the Store API
description: Store API add-item requests carry validated customer-entered lengths into cart item data.
tags: [variable-length, store-api, cart]
timestamp: 2026-11-06T00:00:00Z
---

# Overview

Capture the Store API request length at `woocommerce_store_api_add_to_cart_data` and reuse the existing validation and cart-item-data path. Different entered lengths remain distinct cart lines. When add-to-cart-with-options omits the input, customers should use the Add to Cart form block for length products.

# Citations
- The corresponding ADR is `docs/adr/0016-variable-length-store-api.md`.
