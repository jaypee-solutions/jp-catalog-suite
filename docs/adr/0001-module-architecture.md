# 0001 — Module architecture

## Status

Accepted

## Context

The plugin needs one stable foundation for independently enabled catalog features. WooCommerce owns settings persistence, while all plugin options use the `jps_cs_` prefix. WordPress.org requires the text domain to match the plugin slug. The existing template update checker is maintained upstream and already owns its token option.

## Decision

Keep the plugin in flat, explicitly required `includes/class-*.php` files under the `JayPee\CatalogSuite` namespace. `Plugin` owns a central module registry, module activation lookup, canonical-ID mapping through WPML's filter API, and lifecycle hooks. WooCommerce settings live under a `jps_cs` settings page, with a General section and module-specific sections. The text domain remains `jp-catalog-suite`. Front-end assets use plain JS/CSS without a compilation pipeline. The template-owned Updates class and its `jp_catalog_suite_github_token` option remain unchanged.

## Consequences

Modules can be enabled independently without changing WooCommerce's normal settings-save flow. New modules add a registry entry and settings section. Explicit includes make load order visible and avoid introducing autoloading infrastructure. Translation catalogs and release packaging remain checked-in/build-time concerns. Uninstall removes plugin options and the update token, while order data remains intact.
