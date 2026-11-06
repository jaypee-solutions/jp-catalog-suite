---
type: Reference
title: E2E environment isolation
description: CI-specific fixture behavior and reliable E2E assertions for wp-env.
tags: [testing, e2e, wp-env]
timestamp: 2026-11-06T00:00:00Z
---

# wp-env CI fixtures

- Germanized's activation-time `_wc_gzd_setup_wizard_redirect` transient sends admin requests to its setup wizard; clear that transient for E2E so admin assertions work while preserving its checkout-specific terms and cancellation-policy behavior.
- Variable-length cart data is rendered on the cart line item, not on the product page after add-to-cart. Assert the consumer-visible value on the cart.
- E2E orders persist across suite runs. Use a per-run customer identifier and assert against that order instead of assuming the newest order is the first row.
- E2E specs that change WooCommerce options should restore the previous values even when assertions fail.
