---
type: reference
title: WordPress upload behavior facts
description: Verified WordPress behaviors that protected-file upload routing and type checks rely on.
tags: [protected-files, wordpress]
timestamp: 2026-11-23T00:00:00Z
---

# WordPress upload behavior facts

- `wp_upload_dir()` applies the `upload_dir` filter on every call, not only when it rebuilds its cache (`wp-includes/functions.php`, WordPress 7). State that routes one upload must therefore live until `wp_handle_upload`/`wp_handle_sideload` finishes. Resetting it in the first `upload_dir` call, including the plugin's own cache refresh, sends the file to the public directory.
- `wp_handle_upload_prefilter` runs at the start of `_wp_handle_upload`, before its `wp_upload_dir()` call; the Media Library uploader (`async-upload.php`), REST and `media_handle_upload` all pass through it. `media_handle_sideload` uses `wp_handle_sideload_prefilter`.
- `wp_check_filetype_and_ext` receives `false` as `$mimes` for ordinary uploads. Core compares the detected content type with the extension's type and drops type and extension for mismatches such as `application/octet-stream` with `model/*` or `text/plain` with `application/*` (`wp-includes/functions.php`).
- `wp-admin/includes/class-wp-filesystem-direct.php` extends `WP_Filesystem_Base` without loading it; load `class-wp-filesystem-base.php` first when using the class outside `WP_Filesystem()`.
- An upload that ends in a PHP fatal shows in the Media Library as "The web server cannot process this file" (German: "Der Webserver kann diese Datei nicht verarbeiten"), because `async-upload.php` answers HTTP 500.
