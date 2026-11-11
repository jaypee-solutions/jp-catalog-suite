# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Every `v*` tag needs a matching `## [X.Y.Z]` section here — the release workflow
extracts it as the GitHub release notes and fails without it.

## [Unreleased]

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
