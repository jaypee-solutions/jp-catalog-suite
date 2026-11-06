---
type: Decision
title: Module architecture
description: The plugin uses a central registry and WooCommerce-owned settings for toggleable modules.
tags: [architecture, modules, woocommerce]
timestamp: 2026-11-06T00:00:00Z
---

# Overview

The foundation keeps module classes in flat explicitly required includes, centralizes module activation and canonical IDs, and delegates settings persistence to WooCommerce. The text domain matches the plugin slug, front-end assets require no build, and the inherited Updates class retains its own token option.

# Citations

[1] ADR file: `docs/adr/0001-module-architecture.md`.
