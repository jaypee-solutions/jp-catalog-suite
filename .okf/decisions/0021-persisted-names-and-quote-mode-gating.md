---
type: decision
title: Persisted names and Quote mode ownership
description: Canonical persisted names, hidden-tab metadata, and Quote module lifecycle ownership.
tags: [decisions, persisted-data, quote-mode]
timestamp: 2026-11-07T00:00:00Z
---

# Persisted names and Quote mode ownership

## Status
Accepted

## Context
The persisted option and metadata names are part of the plugin's long-lived integration surface. Before release, the shipped names for Core tab settings, product-tab replacement, and inquiry checkout fields did not match the names specified for Catalog Suite. Inquiry-specific account labels and email behavior also remained active outside the Quote module's lifecycle.

## Decision
Use `_jps_cs_replaces` for product-tab replacement metadata, `jps_cs_tabs_core_{key}_*` for Core tab settings, and `jps_cs_quote_field_{company,phone,address,message}` for inquiry checkout settings. Copy product-tab selection/replacement/hide metadata and `_jps_cs_length_price_per_unit` through WPML. Persist hidden tab IDs and the hidden-tab set on canonical product and tab IDs. Initialize Inquiry emails and account label/column behavior with Quote mode's `Inquiry_Intake` lifecycle. Preserve the store's `pre_option` result when Quote mode is inactive.

## Consequences
The persisted names now match the spec before release; no compatibility aliases are retained. Existing test fixtures, editor forms, integrations, WPML configuration, and documentation must use the canonical names. Inquiry account presentation and notifications are inactive with the Quote module, while the Inquiry status and price-free handling of existing Inquiries remain independent.