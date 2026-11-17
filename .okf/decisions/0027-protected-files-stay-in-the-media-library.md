---
type: decision
title: Protected files stay in the Media Library
description: Protected CAD files remain attachments; a custom file manager is rejected at this scale, with revisit triggers and hardening options.
tags: [protected-files]
timestamp: 2026-11-17T00:00:00Z
---

# Protected files stay in the Media Library

## Status

Accepted. Extends 0007 and 0020.

## Context

Protected CAD files are stored as attachments in `uploads/jps-cs-protected/`, which needs about 20 hooks to keep them out of previews, REST responses and attachment pages (0007, 0020). A custom upload and file manager was considered. Scale is small: one or two files per product, 50+ products, one uploader. Files must never be downloadable without authorization.

## Decision

- Keep the Media Library and attachments as the storage and data model for protected files. A custom store would need its own uploader, picker, deletion and migration, and loses what the block editor, backups and import tools already understand; that is not justified for about 100 files and one uploader.
- The protection guarantee stays the denied directory plus the download endpoint, verified by the Site Health check. On nginx the server rule in the hosting requirements is mandatory.
- Revisit if one of these appears: per-file permissions, file versioning, resumable uploads of very large files, or repeated leaks through core media surfaces.

## Consequences

Each core upload path (upload, sideload, REST, WP-CLI import) must keep being handled by hooks. Protection of files inside the web root depends on server configuration. Hardening options for later: store the files outside the web root through an absolute `_wp_attached_file` path (not yet tested with core's deletion and thumbnail code), and hide protected files from the Media Library grid with a `query-attachments` filter.
