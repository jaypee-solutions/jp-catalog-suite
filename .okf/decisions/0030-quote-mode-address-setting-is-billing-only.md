---
type: decision
title: Quote mode address setting is billing-only
description: The Quote mode Address setting applies to billing fields only; the second address line is never required and shipping addresses stay untouched.
tags: [quote-mode, checkout]
timestamp: 2026-11-25T00:00:00Z
---

# Quote mode address setting is billing-only

## Status

Accepted. Extends 0014.

## Context

The Quote mode Address setting (hidden, optional, required) was applied through `woocommerce_default_address_fields` and `woocommerce_get_country_locale`. Both feed billing and shipping forms, so the My Account shipping address inherited the setting. With "required" it also made the second address line mandatory, and customers could not save a shipping address ("Adresszusatz ist ein Pflichtfeld"). The country locale cannot tell billing from shipping, and WooCommerce caches it per request.

## Decision

- The setting applies to billing fields only, through `woocommerce_billing_fields` (`Quote_Mode::billing_fields()`): hidden removes `billing_address_1`, `billing_address_2`, `billing_city`, `billing_state` and `billing_postcode`; optional and required set `required` on them.
- The second address line is never required.
- `woocommerce_default_address_fields` is no longer filtered.
- `woocommerce_get_country_locale` is still filtered for the Blocks checkout, which has no per-type locale. It is skipped on My Account pages and the classic checkout, where the classic forms (including shipping fields and their JavaScript locale) must stay untouched.
- Blocks checkout needs no shipping handling: `woocommerce_cart_needs_shipping` is `false` in quote mode, so the Store API reports `needs_shipping: false`, the Blocks checkout renders no shipping address and the Store API does not validate one.

## Consequences

Shipping addresses in My Account and the classic checkout behave as in plain WooCommerce. The Blocks locale is shared by both address types, which is harmless as long as quote mode suppresses shipping; if a future change lets quote mode request shipping, the Address setting would also hide or require the Blocks shipping fields and this decision must be revisited. For classic forms, the locale JavaScript sets billing required state from WooCommerce defaults, so "optional" is enforced by the server but may still show required asterisks client-side.
