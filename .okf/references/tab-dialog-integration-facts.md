---
type: reference
title: Tab editor dialog integration facts
description: Verified WordPress and WooCommerce behaviors the tab management dialog relies on.
tags: [product-tabs, admin-ui, woocommerce]
timestamp: 2026-11-10T00:00:00Z
---

# Tab editor dialog integration facts

- `WC_Admin_Settings::save_fields` skips settings entries with `'is_option' => false`; the shop-wide tab list field (`jps_cs_tab_list`) uses that so saving the page creates no option.
- A custom settings `type` renders through `do_action( 'woocommerce_admin_field_' . $type, $value )`.
- The block editor runs `editor.savePost` actions (via `doActionAsync`) after the edit-post metabox save at priority 10; the embed script hooks priority 20 and posts `jps-cs-tab-saved` to the parent window.
- `jps_cs_tab` keeps `show_ui => true` with `show_in_menu => false`: `post.php`/`post-new.php` must stay reachable for the iframe.
- `default_title`/`default_content` receive the created auto-draft; the "Replace" link prefills from the replaced tab through them.
- Firing `admin_init` in integration tests also runs core's `wp_admin_headers` and privacy-policy registration; tests detach the former and expect the incorrect-usage notice of the latter.
- Inserting copies in the migration must bypass kses (`kses_remove_filters()` / `kses_init()`), otherwise copies made without an `unfiltered_html` user lose block markup.

Decision: ADR 0022 (`decisions/0022-tabs-managed-on-owners.md`).
