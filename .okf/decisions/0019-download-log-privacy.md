---
type: Decision
title: Download log privacy
description: Minimal protected-file download records with configurable retention and WordPress privacy tools.
tags: [privacy, protected-files, retention]
timestamp: 2026-11-06
---

# Overview

The Download log stores user, file, untrusted product ID and UTC timestamp only. It is opt-in, supports configurable daily retention, and exposes WordPress personal-data export/erasure plus account-deletion cleanup. The table is dropped only when uninstall deletion is enabled.

# Citations

- `docs/adr/0019-download-log-privacy.md` (ADR 0019).
