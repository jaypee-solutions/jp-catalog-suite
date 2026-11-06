# ADR 0013: Variable-length variation overrides

## Status

Accepted

## Context

A variable product's length configuration must work with other choices such as colour. Editors need to constrain an individual variation without duplicating the parent configuration.

## Decision

The variable product enables Variable length and owns the inherited configuration. Each variation stores optional minimum, maximum, step, and default values; an empty value inherits from the parent. WooCommerce's available-variation payload sends the resolved limits to the product form, whose input follows selection and reset. Add-to-cart validation resolves configuration from the requested child variation on the server.

## Consequences

Variation-specific bounds are enforced both in the browser and at cart insertion. The unit and enablement remain inherited from the parent. Store API add-to-cart and price-per-length behavior remain out of scope.
