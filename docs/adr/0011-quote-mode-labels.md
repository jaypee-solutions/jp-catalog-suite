# Quote-mode customer labels

## Status
Accepted

## Context
Quote mode must present inquiry language at customer-facing product, basket, checkout, and confirmation touchpoints, without rewriting unrelated WooCommerce wording.

## Decision
Provide translated default labels configurable through WooCommerce settings, with empty settings falling back to translated defaults. Apply only the agreed WooCommerce labels and expose the complete label map through `jps_cs_quote_labels`. Use WooCommerce Blocks checkout filters and translated WooCommerce text for Blocks wording. Rename cart and checkout page titles directly in WordPress rather than filtering page titles.

## Consequences
Administrators can customize the labels independently, and translations remain available when settings are empty. Unmapped WooCommerce wording remains unchanged.
