---
type: decision
title: Variable-length variation overrides
description: Optional variation limits inherit from the variable product and are validated on the server.
tags: [variable-length, variations]
timestamp: 2026-11-06T00:00:00Z
---

# Variable-length variation overrides

## Context

Variable products enable Variable length on the parent, while each variation can narrow minimum, maximum, step, and default length.

## Decision

Store only explicit variation overrides; empty values inherit from the parent configuration. Expose resolved variation limits through WooCommerce's available-variation payload, update/reset the product input as variation selection changes, and validate the submitted length using the chosen variation on the server.

## Consequences

Units and enablement continue to come from the parent. Store API add-to-cart and price-per-length behavior remain outside this change.

## Related

- [Variable length decision](0009-variable-length-as-product-option.md)
