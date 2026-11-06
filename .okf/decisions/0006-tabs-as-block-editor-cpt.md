---
type: Decision
title: Tabs as block-editor CPT with access block
description: Global product tabs use a block-editor custom post type with category scope and priority.
tags: [product-tabs, wordpress]
timestamp: 2026-11-06T00:00:00Z
---

# Overview

Global tabs are `jps_cs_tab` posts edited in the block editor. Their settings define all-product or category scope and priority; matching expands child product categories to their ancestors. Rendering integrates through WooCommerce's product-tab filter, applies content and tab-list hooks, and omits empty content. REST access remains restricted to editors, while WPML translates posts and copies settings.

The initial implementation intentionally excludes product-specific scope, replacement/hiding controls, core-tab configuration, protected content and files.

# Citations

- ADR 0006: `docs/adr/0006-tabs-as-block-editor-cpt.md`.
