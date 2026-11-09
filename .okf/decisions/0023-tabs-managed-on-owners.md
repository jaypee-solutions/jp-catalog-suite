---
type: decision
title: Tabs managed on their owners
description: Single-owner tabs managed on product, category and settings screens through a block-editor dialog.
tags: [product-tabs, admin-ui]
timestamp: 2026-11-10T00:00:00Z
---

# Tabs managed on their owners

## Status

Accepted. Supersedes in part the "Products → Product tabs" management list of 0006 and the multi-product assignment of 0012.

## Context

Tabs (`jps_cs_tab` posts) were managed in a separate list under Products → Product tabs, while the product's Tabs data panel only hid global tabs and linked out. Editors expect to manage tabs where they apply: on the product and product category edit screens.

## Decision

- Each tab has exactly one owner: shop-wide (`all`), one category (it also shows on products in subcategories) or one product. `_jps_cs_categories` and `_jps_cs_products` hold at most one ID.
- Tabs stay `jps_cs_tab` posts as hidden storage (no menu entry), so the block editor, Access block and File list block keep working. The product Tabs panel, the category edit screen and WooCommerce → Settings → Catalog Suite → Product tabs (shop-wide tabs) list the owner's tabs. Add, Edit and Replace open the block editor in a `<dialog>` iframe; saving refreshes the list through `postMessage` and an AJAX endpoint. Delete trashes through an AJAX endpoint.
- A one-time migration (`jps_cs_tabs_single_owner_migrated`) splits tabs that listed several owners into one copy per owner. Products keep seeing what they saw: duplicate copies are hidden through `_jps_cs_hidden_tabs`, and product tabs that replaced a split tab replace the copy that applies to their product.
- Owned tabs are deleted with their product or category (raw-ID comparison, so deleting a translation keeps tabs stored against the original).
- Frontend resolution, meta keys, `wpml-config.xml` and uninstall are unchanged.

## Consequences

The separate management interface is gone and tab ownership is unambiguous. Sharing one tab across several categories or products now means maintaining copies. Migration is not retried after a partial failure. WordPress versions without the `editor.savePost` action refresh the list only when the dialog closes.
