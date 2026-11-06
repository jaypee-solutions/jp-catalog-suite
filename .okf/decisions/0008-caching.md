---
type: decision
title: Access content caching
description: Logged-in access variants bypass page caches; guests receive one public variant.
timestamp: 2026-11-06T00:00:00Z
tags:
  - access
  - caching
---

# Overview

The Access block can render a different product-tab variant for authorized visitors. Logged-in requests that render protected content receive `DONOTCACHEPAGE` and WordPress no-cache headers. Guests always receive the same public variant. Reverse proxies must pass WordPress-authenticated requests to avoid sharing cached variants.

# Citations

ADR 0008: `docs/adr/0008-caching.md`.
