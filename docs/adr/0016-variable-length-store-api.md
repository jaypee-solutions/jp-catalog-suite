# ADR 0016: Variable length through the Store API

## Status

Accepted

## Context

The Variable length input is rendered in block product forms, but Store API add-item requests do not carry classic form fields through WooCommerce's normal request globals. Without a Store API seam, these additions lose the customer-entered length and bypass length validation.

## Decision

Capture `jps_cs_length` from Store API add-item request parameters, including the `jp-catalog-suite` request extension, through `woocommerce_store_api_add_to_cart_data`. Reuse the existing cart-validation and cart-item-data path so product and variation limits, unit metadata, and cart-line uniqueness remain consistent with classic forms. Disable WooCommerce AJAX add-to-cart support on enabled simple and variable products. The product form remains the reliable fallback if a block omits its length field.

## Consequences

Store API adds preserve validated lengths and reject invalid values. Distinct lengths produce distinct cart lines. Sites whose add-to-cart-with-options block does not submit the input should use the Add to Cart form block for length products.
