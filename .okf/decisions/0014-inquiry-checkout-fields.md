---
type: decision
title: Inquiry checkout fields
description: Configurable inquiry checkout details in classic and Blocks checkout.
tags: [checkout, inquiry]
timestamp: 2026-11-06T00:00:00Z
---

# Inquiry checkout fields

## Context
Inquiry checkout needs configurable customer details across WooCommerce's classic and Blocks checkouts without changing the required identity fields.

## Decision
Store Company, Phone, Address, and Message preferences as WooCommerce options. Use checkout field filters for classic checkout and country-locale field metadata for both checkout implementations. Keep name and email required. Blocks order notes remain controlled by the Checkout block configuration.

## Consequences
Admins can select optional, required, or hidden for Company and Phone, and hidden, optional, or required for Address. Blocks cannot hide Country and cannot require order notes. Classic checkout removes hidden address fields.
