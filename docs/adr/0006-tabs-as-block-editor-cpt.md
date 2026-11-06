# 0006 — Tabs as block-editor CPT with access block

## Status

Accepted

## Context

Editors need reusable content for product pages, managed in WordPress rather than copied into each product. The tab content must support the block editor and remain inaccessible to visitors through the editing REST API.

## Decision

Represent each Global tab as a `jps_cs_tab` custom post type with the block editor enabled. Store scope, category assignments, and priority as post metadata, and resolve matching tabs at the WooCommerce product-tabs seam. Include ancestor categories when matching a product assigned to a child category. Render block content once, omit tabs that have no text or media, and expose `jps_cs_tab_content` and `jps_cs_product_tabs` filters. Keep global tabs read-protected through REST and make posts translatable while copying their settings.

## Consequences

Editors can maintain public product content centrally and organize it by category. The ticket intentionally delivers only global and category scopes; product-specific tabs, access-controlled blocks, and replacement or hiding behavior remain separate work. The tab metadata is not exposed through REST, and tab deletion follows the existing uninstall opt-in.
