---
type: Decision
title: Protected files
description: Protected uploads are delivered through an authorized plugin endpoint.
tags:
  - files
  - security
timestamp: 2026-11-06T00:00:00Z
---

# Overview

CAD attachments are stored beneath uploads in a denied subdirectory and their URLs resolve to the plugin endpoint. The endpoint uses user-and-file access decisions, supports X-Sendfile or PHP streaming up to 256 MB, and redirects attachments after they are unprotected.

# Citations

- Source: `docs/adr/0007-protected-files.md`.
