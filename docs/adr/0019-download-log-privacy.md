# Download log privacy

## Status
Accepted

## Context
Shop administrators need an auditable record of protected-file downloads, while data minimization and retention remain necessary. The product URL value is untrusted and must not influence authorization.

## Decision
Store only user ID, attachment ID, untrusted product ID (or zero), and UTC timestamp in a dedicated table. Do not store IP addresses. Logging is disabled by default; configurable retention deletes old rows daily, with zero disabling automatic deletion. Provide user deletion, WordPress personal-data export and erasure, and a capability-protected WooCommerce page. Create/check the table through activation and initialization; drop it only during opt-in uninstall.

## Consequences
Download auditing is opt-in. Exported and displayed timestamps use the site's local timezone. Privacy requests and account deletion remove records without retaining anonymized rows.
