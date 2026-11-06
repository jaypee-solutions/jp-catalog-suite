# Shop-wide Core tab settings

## Status

Accepted

## Context

WooCommerce's Description, Additional information and Reviews Core tabs need shop-wide presentation controls while product-specific Product tabs remain independent.

## Decision

Store each Core tab's optional replacement title, priority and hidden state in WooCommerce settings options. Apply settings through `woocommerce_product_tabs` after Product_Tabs adds Product tabs, then sort the resulting list using priorities. Empty titles and priorities preserve WooCommerce's defaults; Product tabs do not replace Core tabs.

## Consequences

Core tab settings are saved through WooCommerce's settings API and title options are declared as WPML admin texts. Core tab changes apply shop-wide and cannot be configured per product.
