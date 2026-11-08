---
type: Reference
title: WooCommerce 11.2.2 source facts
description: Verified WooCommerce source locations used by the Catalog Suite design.
tags: [woocommerce, source]
timestamp: 2026-11-06T00:00:00Z
---

# Verified in WC 11.2.2

- `src/StoreApi/Routes/V1/Checkout.php:686` sets `pending` before `payment_complete()`, so both checkouts transition pending to `jps-inquiry`.
- `src/Internal/Admin/Orders/ListTable.php:575-586` and `includes/admin/list-tables/class-wc-admin-list-table-orders.php:646-655` list only registered statuses; My Account orders queries do the same by default.
- `src/StoreApi/Schemas/V1/ProductSchema.php:952-972` has no filter for Store API prices.
- `src/StoreApi/Utilities/CartController.php:337-345,1232-1237` applies `woocommerce_add_to_cart_validation` and `woocommerce_add_cart_item_data`; `src/StoreApi/Routes/V1/CartAddItem.php:116-127` passes `( $data, $request )` to `woocommerce_store_api_add_to_cart_data`.
- `src/Blocks/BlockTypes/MiniCart.php` has no `integration_registry_identifier`.
- Analytics reads `woocommerce_excluded_report_order_statuses` through `get_option( …, [ 'pending', 'failed', 'cancelled' ] )` (`src/Admin/API/Reports/DataStore.php:812`). A store that never saved the setting has no row, and core then applies only `default_option_{name}`, never `option_{name}`; filter both. `woocommerce_analytics_settings_default_excluded_order_statuses` does not exist.

# Citations

Paths and line numbers are from the WooCommerce 11.2.2 source reviewed during design.
