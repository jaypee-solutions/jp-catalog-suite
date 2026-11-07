# Germanized integration

## Status
Accepted

## Context

Germanized adds legal shopmarks to product, catalog, cart, checkout, and order views and registers checkout legal checkboxes. Those notices conflict with Quote mode's price-free Inquiry flow, while the privacy checkbox may still be required. Germanized must not be changed when Quote mode is inactive or Germanized is not active.

## Decision

Initialize a small compatibility adapter only when the installed `WooCommerce_Germanized` class exists. While Quote mode is active, remove registered legal checkboxes other than IDs returned by `jps_cs_germanized_kept_checkboxes` (default `privacy`), and remove each Germanized `Shopmark` returned by its location accessors on `wp` at `PHP_INT_MAX`. Quote mode retains control of the order button label.

The installed Germanized 4.1.6 source verifies the class, the `woocommerce_gzd_registered_legal_checkboxes` action, the manager's `get_checkboxes()` and `remove( $id )` methods, the `woocommerce_gzd_legal_checkboxes_settings` option, all eight `wc_gzd_get_*_shopmarks()` accessors, and `Shopmark::remove()`. No Germanized display-option or button-text fallback is enabled: those are reserved for use only if the end-to-end checks demonstrate that the primary hooks fail.

German Market is unsupported because its proprietary integration cannot be verified. Extensions should integrate through `jps_cs_inquiry_created` and `jps_cs_quote_mode_active`.

## Consequences

Germanized notices and non-kept legal checkboxes are suppressed only in Quote mode and only when Germanized is loaded. The privacy checkbox remains unless a filter changes the kept IDs. Germanized changes to its class, action, accessor functions, or shopmark object API may require an adapter update.

Germanized 4.1.6 registers its built-in privacy checkbox for account registration by default and hides it on checkout unless account creation is active. The end-to-end preservation case explicitly enables its checkout location and account registration; this is fixture setup, not a plugin option added by Catalog Suite.
