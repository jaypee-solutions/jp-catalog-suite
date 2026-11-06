---
type: Decision
title: Price hiding and Blocks compatibility
description: Customer-facing price suppression spans WooCommerce templates and Blocks while inquiry price views remain toggle-independent.
tags: [quote-mode, inquiry, blocks, prices]
timestamp: 2026-11-06T00:00:00Z
---

# Overview

Quote mode hides product prices through WooCommerce filters and scoped CSS for Blocks totals. Inquiry item totals and subtotals remain price-free in customer views and emails regardless of the Quote mode toggle; administrator order screens retain prices. Store API JSON may expose raw prices, so hiding is not confidentiality.

# Citations

[1] ADR 0004: `docs/adr/0004-price-hiding-and-blocks-compatibility.md` (the repository-level decision record).
