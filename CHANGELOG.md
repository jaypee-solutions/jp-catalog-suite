# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Every `v*` tag needs a matching `## [X.Y.Z]` section here — the release workflow
extracts it as the GitHub release notes and fails without it.

## [Unreleased]

### Changed
- Development tooling updated from wp-plugin-template v1.4.0 to v1.7.0 (`copier update`): `just` recipes and release scripts, `scripts/verify-release-zip.sh` with `scripts/release-paths.txt` / `scripts/release-dev-only.txt` (the release ZIP is checked against the checkout in CI and the release workflow), extended `scripts/check-version.sh`, two more lint checks (`check-ignored-sources.sh`, `check-code-rules.sh`), wp-env ports derived from the plugin slug (8800–8899; linked worktrees keep 8900–8999), core language packs for `de_DE` installed in wp-env, `loginAs()`/`probe()` E2E helpers, a weekly CI run, and the template's reviewer rules and agent docs. No runtime changes.

### Fixed
- Quote mode: with the address field set to "required", the second address line (Adresszusatz) became mandatory too, blocking saving addresses in My Account and checkout. Only the first line, city, state and postcode are required now.
- Quote mode: the Address setting also changed the shipping address in My Account and the classic checkout. It now applies to the billing address only; Blocks checkout still follows it for its address form.

## [0.5.6] - 2026-11-24

### Fixed
- Protected files: the browser's save dialog for the download button of a core File block named the file `index.php` (the last segment of the endpoint URL) until the response arrived. The button now carries the file name in its `download` attribute.

## [0.5.5] - 2026-11-23

### Fixed
- Protected files: the core File block of a protected file showed its link and download button to every visitor; a guest got a link to the login page and a button that downloaded the login page as `index.php`. Guests now see the file name with "login required" and no link; Authorized users see the block unchanged and download under the file's own name.

## [0.5.4] - 2026-11-23

### Fixed
- Protected files: uploading a protected file in the block editor (File block, media upload) stayed on "Uploading" forever. The editor reads a file name from the URL path and crashed on the endpoint URL `/?jps_cs_file=<id>`; the endpoint URL of a protected attachment now is `/index.php?jps_cs_file=<id>` (the old form still works).

## [0.5.3] - 2026-11-23

### Fixed
- Protected files: sites that had saved the file type settings before 0.5.0 could not upload STL and the other 3D formats ("not allowed to upload this file type"), because their stored settings replaced the new defaults. The first admin page load after updating now appends the missing 3D types to both settings once; types the site removed or customised are kept (ADR 0026).

## [0.5.2] - 2026-11-23

### Fixed
- Protected files: uploads of protected types (STL, STEP, DXF and the other 3D formats) in the Media Library failed with "The web server cannot process this file" (HTTP 500). The protected-directory routing was cancelled by the plugin's own upload-directory refresh, so the file landed in the public directory and the follow-up move crashed on a missing WordPress filesystem class. Both are fixed.

## [0.5.1] - 2026-11-17

### Fixed
- Quote mode: no cart wording is left on the storefront. Variable products get the Add to inquiry button, and other WooCommerce texts such as "Update cart", "Cart totals" or "added to your cart" (German "Warenkorb") use the Inquiry basket label, in PHP and in the Cart, Checkout and mini-cart blocks (ADR 0028).

## [0.5.0] - 2026-11-17

### Added
- Variable length: the length field validates while typing with an inline message, looks like the variation select, and sits in the variations table on variable products (ADR 0025).
- Protected files: STL, OBJ, 3MF, PLY, glTF (`glb`, `gltf`), FBX, Collada (`dae`), IGES (`igs`, `iges`) and 3DS join the default allowed types and automatically protected extensions for new installs (ADR 0026). Existing sites keep their stored settings; add the extensions to both.

### Fixed
- Protected files: configured types whose content WordPress cannot match to their MIME type, such as binary STL (`model/stl`) or text STEP, were rejected on upload because the type override ignored uploads without an explicit MIME list.
- Quote mode: the mini-cart checkout button uses the Quote mode label again on WooCommerce 11.3, which renamed it from "Go to checkout" to "Checkout".

## [0.4.1] - 2026-11-11

### Fixed
- Variable length: the settings were unreachable on variable products when WooCommerce hid the General tab (for example with taxes disabled). They now sit in their own option group, so the tab stays visible.

## [0.4.0] - 2026-11-10

### Added
- Variation tabs: a variation of a variable product can own tabs, managed in its panel on the product edit screen. A variation tab shows only while its variation is selected and may replace one tab shown on the parent product (ADR 0024). Needs the classic variation form; with the "Add to Cart + Options" block, variation tabs stay hidden.

## [0.3.0] - 2026-11-10

### Added
- Quotes: the shop manager sets prices on an Inquiry and sends a priced Quote (status "Quote sent"); a logged-in customer accepts it in My Account, which turns it into an `on-hold` order (ADR 0022).
- User guide with a task-based tutorial for every feature (`docs/guide/`).

### Changed
- Product tabs are managed on the product edit screen, the product category edit screen and WooCommerce → Settings → Catalog Suite → Product tabs through a block-editor dialog; the Products → Product tabs menu is removed. Each tab has exactly one owner; existing multi-owner tabs are split into copies once (ADR 0023).

### Fixed
- The confirmation page of an Inquiry says "Inquiry received", "Inquiry #:", "Inquiry number:" and "Inquiry details" instead of WooCommerce's order wording (Blocks and Classic; German: "Anfrage eingegangen", "Anfrage-Nr.:", "Anfragenummer:", "Anfragedetails").
- Variable length: the minimum and maximum default lengths are validated as the submitted pair, so inverted values are rejected and moving both bounds together is accepted.

## [0.2.0] - 2026-11-09

### Added
- Quote mode supports guest Inquiry checkout without payment, shipping, taxes, or coupons.
- Global product tabs with all-product or category scope and priority.
- Variable-length product inputs with validated cart and order data.
- Added Germanized compatibility, price-free Inquiry emails and checkout fields, protected product tabs/files, access controls, and opt-in download logging.
- Variable-length variation overrides, Store API support, and optional price-per-length-unit calculations.
- Translatable Inquiry labels and customer price hiding.
- Shop-wide Core tab controls and per-product tab overrides.
- Inquiry checkout fields configurable for Company, Phone, Address, and Message.
- Added lint checks for ADR mirrors, README hook references, translation catalog coverage, formal German catalog identity, and OKF conformance.

### Fixed
- Inquiries are excluded from WooCommerce Analytics for stores that never saved the excluded-statuses setting.

## [0.1.0] - 2026-11-06

### Added
- Initial scaffolding from [wp-plugin-template](https://github.com/jaypee-solutions/wp-plugin-template).
