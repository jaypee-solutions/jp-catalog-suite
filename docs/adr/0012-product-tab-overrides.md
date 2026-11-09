# Product-specific tabs replace and hide global tabs

## Status

Accepted. Superseded in part by 0023.

## Context

Global and category-scoped tabs need per-product customization without duplicating the shared tab content. Tab and product identifiers may refer to translated objects under WPML or Polylang.

## Decision

Add a Products scope to product tabs, storing selected product IDs and an optional replaced global tab ID. Resolve both sides through canonical IDs. An empty product-tab priority inherits the replaced global tab's priority; otherwise the default remains 50. Store hidden global tab IDs in `_jps_cs_hidden_tabs` on the product. Expose these controls in the WooCommerce product data editor, and remove this metadata when uninstall deletion is opted into.

## Consequences

A product-specific tab replaces only a global/category tab, never another product tab. Hiding applies only to the selected product. Product tab content remains managed through the existing block-editor post type.
