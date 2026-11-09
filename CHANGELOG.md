# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Every `v*` tag needs a matching `## [X.Y.Z]` section here — the release workflow
extracts it as the GitHub release notes and fails without it.

## [Unreleased]

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
