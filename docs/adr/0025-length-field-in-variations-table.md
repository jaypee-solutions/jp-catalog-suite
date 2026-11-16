# Length field in the variations table

## Status

Accepted. Extends 0013.

## Context

The product page length input was unstyled and validated only on submit and on the server. Variable products show their attributes in WooCommerce's `table.variations`; the length input sat below it with different styling. WooCommerce's `variable.php` has no hook inside that table (only `woocommerce_after_variations_table` after it), so PHP cannot add a row.

## Decision

- On variable products `assets/js/variable-length.js` moves the label and input into their own row of `table.variations` (label in the label column, input and message in the value column). Without JavaScript, or without that table (block-based variation selectors), the field stays below the description as before.
- Validation runs while typing and uses native constraint validity. Range errors (empty, bad input, below min, above max) take precedence over step errors, as on the server.
- Messages are the server-formatted strings, shipped as `data-range-message`/`data-step-message` on the input and as `range_message`/`step_message` in the variation data. No JavaScript i18n is needed. The text is shown in an inline `aria-live` message below the field and set as custom validity, so the native submit bubble shows the same text.
- The input mirrors the CSS values WooCommerce applies to the variation select (`assets/css/variable-length.css`): height `3em`, padding `.9em 1.1em`, small font size, UA border, transparent background, `min-width: 75%` in the table. Invalid fields get a red border (`#cc1818`).
- The script and stylesheet load on simple and variable products with Variable length enabled.

## Consequences

The field jumps into the table after the script runs (brief layout shift on load). Style parity assumes WooCommerce's current select rules; a WooCommerce or theme change to them needs a CSS update. The E2E spec compares computed styles of input and select to catch drift.
