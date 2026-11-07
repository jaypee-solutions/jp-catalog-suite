---
type: decision
title: Protected-file lifecycle and API hardening
description: Hardens protected-file storage and delivery, access hooks, privacy logging, and length validation.
timestamp: 2026-11-07T00:00:00Z
tags:
  - security
  - protected-files
  - variable-length
---

# ADR 0020: Protected-file lifecycle and API hardening

## Status

Accepted

## Context

Protected files, access-dependent blocks, download-log privacy controls, and length validation must remain safe independently of the Product tabs module toggle. Protected uploads must not expose public paths, overwrite unrelated attachments, or be accepted through an unsafe MIME override. Stored endpoint links must survive unprotection without exposing arbitrary attachments.

## Decision

Register protected-file routing, access blocks, file-list blocks, and Download-log privacy/retention hooks as plugin-core behavior. Gate only Download-log recording and its admin page on the Product tabs module. Record log entries at the end of the delivery action, after access and extension checks.

Use a denylist for dangerous MIME extensions, sanitize the MIME option on save, preserve the stored option for users without `manage_options`, and honor both upload capabilities and the caller MIME list. Route protected uploads into randomized subdirectories; move files using local filesystem operations and unique destination names, updating attachment paths and metadata only after a successful move. Keep former-protected markers so saved endpoint URLs redirect after unprotection, while refusing endpoint access to unrelated attachments. Redact protected REST media data, hide protected media from guests, use noindex/404 on unauthorized attachment pages, and add a Site Health check for the server deny rule. Use PHP streaming whenever X-Sendfile is unavailable.

The access filter receives `(bool $allowed, int $user_id, array $context)`. Block context uses integer `product_id`; file access uses `attachment_id` and never trusts a product identifier for authorization. The notice filter receives `(string $html, array $context)` and output is sanitized with `wp_kses_post`.

Length validation rejects non-positive input, requires Store API lengths, and refuses invalid min/max bounds at settings and product/variation save. Non-form cart additions without a length are left to WooCommerce. The price filter follows `(price, length, item)`; `config()` retains the existing `enabled` array contract to avoid widening the change across every length caller, and exposes `price_per_unit` plus a default falling back to min.

Uninstall drops the plugin-owned log table before deleting files and uses `WP_Filesystem_Direct` for local uploads.

## Consequences

Protected-file behavior and privacy cleanup stay active even if Product tabs are disabled. Randomized storage paths and unique moves prevent predictable access and accidental replacement. Existing endpoint URLs continue working after unprotection. The length `config()` contract remains array-shaped for existing callers; extensions should use its `enabled` and `price_per_unit` fields. The web server must still enforce the protected-directory deny rule, and Site Health reports when it does not.
