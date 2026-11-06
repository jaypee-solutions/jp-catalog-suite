---
type: decision
title: Germanized integration
description: Suppress Germanized legal notices and checkout checkboxes only during Quote mode.
timestamp: "2026-11-06T00:00:00Z"
tags:
  - germanized
  - quote-mode
---

# Germanized integration

## Context

Germanized renders legal shopmarks across shopping and order views and registers checkout legal checkboxes. Quote mode must suppress those notices without disabling Germanized outside the Inquiry flow or removing the privacy checkbox by default.

## Decision

Initialize compatibility only when `WooCommerce_Germanized` exists. When Quote mode is active, remove checkboxes registered through `woocommerce_gzd_registered_legal_checkboxes` except IDs in `jps_cs_germanized_kept_checkboxes` (default `privacy`). At `wp` priority `PHP_INT_MAX`, remove shopmarks from the eight verified Germanized location accessors. No display-option or button-text fallback is used unless end-to-end checks show that the primary integration fails.

German Market is unsupported. Integrations should use `jps_cs_inquiry_created` and `jps_cs_quote_mode_active`.

## Consequences

Germanized changes to its class, action, accessors, or `Shopmark::remove()` may require updating this adapter. Suppression is limited to active Germanized and Quote mode.

The built-in privacy checkbox is registration-only by default and is hidden from checkout unless account creation is active; the E2E fixture enables its checkout location and registration. This does not add a Catalog Suite option.

## Citations

- `docs/adr/0005-germanized-integration.md`
- Installed Germanized 4.1.6 source: `includes/class-wc-gzd-legal-checkbox-manager.php`, `includes/wc-gzd-core-functions.php`, and `src/Shopmark.php`.
