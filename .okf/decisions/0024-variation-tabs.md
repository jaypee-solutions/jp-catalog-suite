---
type: decision
title: Variation tabs
description: Variations own tabs, managed in the variation panel and shown (or replacing a parent tab) while the variation is selected.
tags: [product-tabs, admin-ui]
timestamp: 2026-11-10T00:00:00Z
---

# Variation tabs

## Status

Accepted. Extends 0023.

## Context

Tabs can be owned by the shop, a category or a product (0023). Variations of variable products could not own tabs, so content that differs per variation (data sheets, downloads) needed a workaround.

## Decision

- New scope `variations`. `_jps_cs_variations` holds at most one variation ID.
- Variation tabs are managed in the variation's panel on the product edit screen, with the same list and block-editor dialog as products and categories. The parent product's Tabs panel does not list them.
- On the product page a variation tab shows only while its variation is selected. Without a selected variation, all variation tabs are hidden.
- A variation tab may replace one tab shown on the parent product (a global tab or a product tab) while its variation is selected. Both tabs stay in the markup; `assets/js/variation-tabs.js` toggles them. If the target is hidden, empty or itself replaced, the variation tab shows as an additional tab. A variation tab cannot be replaced.
- There is no per-variation Hide.
- Classic tabs and the WooCommerce 11 accordion Product Details layout are supported. Variation selection is detected through the classic variation form (`found_variation`, `reset_data`), like Variable length. With the "Add to Cart + Options" block, variation tabs stay hidden and replaced tabs stay visible. Without JavaScript all tabs show.
- Variation tabs are deleted with their variation (raw-ID comparison, as for products and categories).

## Consequences

Variations own tabs without a new post type. Replacement is client-side, so the markup of a variable product holds every variation's tabs. Block-based variation selectors are not supported yet.
