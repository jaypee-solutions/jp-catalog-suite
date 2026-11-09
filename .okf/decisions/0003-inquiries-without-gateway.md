---
type: Decision
title: Inquiries without gateway
description: Inquiry checkout and status transitions require no payment gateway.
tags: [inquiry, checkout]
timestamp: 2026-11-06T14:30:00Z
---

# Overview

Quote mode removes payment requirements. A pending, marked order transitions to `jps-inquiry` before payment-completion processing, leaving paid date and stock unchanged. Core Inquiry status registration and Analytics exclusion are independent of the module toggle. ADR 0022 adds the `jps-quote` status with the same independence.

# Citations

- ADR: `docs/adr/0003-inquiries-without-gateway.md`
