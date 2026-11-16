---
type: decision
title: STL uploads and the core type check
description: STL joins the default CAD types, and the upload type override now applies when core passes no MIME list.
tags: [protected-files]
timestamp: 2026-11-16T00:00:00Z
---

# STL uploads and the core type check

## Status

Accepted. Extends 0020.

## Context

Uploading an STL file failed. Two causes:

- STL was neither a default allowed type nor a default automatically protected extension.
- WordPress compares a file's detected content type with the type of its extension. Binary STL is detected as `application/octet-stream`, which is accepted only for `application/*`, `video/*` and `audio/*`, so `model/stl` is rejected; text STEP and ASCII STL are detected as `text/plain` and rejected as well. The plugin's `wp_check_filetype_and_ext` override exists for this, but returned early unless the caller passed an explicit MIME list. Core passes none for ordinary uploads, so the override never ran.

## Decision

- The override treats a missing caller list as the site's allowed types (`get_allowed_mime_types()`). A caller list still wins (0020: honor both upload capabilities and the caller MIME list).
- New installs allow `stl=model/stl` and protect `stl` automatically. Defaults live in `Protected_Files::DEFAULT_MIMES` and `DEFAULT_PROTECTED_EXTS`, used by the settings page and the code.

## Consequences

Configured CAD types upload whatever their content looks like; the extension, the allowed list and the upload capability remain the gate, as 0020 intended. Sites that already saved the settings keep their values and must add `stl` to both options; until then STL stays rejected, or, if only the MIME setting has it, stored publicly.
