---
type: decision
title: Quote-mode customer labels
description: Configurable translated inquiry labels for customer-facing WooCommerce surfaces.
tags: [quote-mode, labels, checkout]
timestamp: 2026-11-06T14:40:00Z
---

# Decision

Quote mode uses translated defaults for Inquiry basket, Add to inquiry, Continue to inquiry, and Send inquiry. WooCommerce settings override each label when non-empty. The `jps_cs_quote_labels` filter adjusts the resulting map. Cart and checkout page titles remain administrator-managed WordPress page titles.
