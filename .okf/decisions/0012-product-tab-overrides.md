---
type: decision
title: Product-specific tabs replace and hide global tabs
description: Product tabs can replace global tabs or hide global tabs on individual products.
timestamp: 2026-11-06T00:00:00Z
tags:
  - woocommerce
  - product-tabs
---

# Context

Global and category-scoped tabs need per-product customization, with multilingual product and tab IDs resolved canonically.

# Decision

Product tabs can be assigned through Products scope and optionally replace a global or category tab. Empty priority inherits the replaced tab priority; otherwise it defaults to 50. Hidden global tab IDs are stored in `_jps_cs_hidden_tabs` on products. These controls are part of WooCommerce's product data editor.

# Consequences

Replacement does not target other product tabs, and hiding is local to one product. Opt-in uninstall removes the hidden-tab metadata.
