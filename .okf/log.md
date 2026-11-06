# Catalog Suite knowledge log

## 2026-11-06
* **Creation**: Established the OKF bundle with the module architecture decision and WooCommerce source reference.
* **Variable length**: Recorded ADR 0009 for simple-product length configuration and cart/order data.
* **Inquiry intake**: Added quote-mode inquiry processing, the core Inquiry status, and Analytics exclusion.
* **Global product tabs**: Added the block-editor tab post type, category-scoped resolution, and restricted REST access.
* **E2E isolation**: Documented Germanized onboarding, cart-line assertions, and persistent order isolation.
* **Variable-length variations**: Recorded ADR 0013 for inheritable variation-specific limits and server validation.
* **Product tab overrides**: Added product-scoped tabs, replacement, per-product hiding, and canonical-ID comparisons.
* **Quote-mode labels**: Added configurable translated inquiry wording across PHP and WooCommerce Blocks.
* **Inquiry emails**: Added configurable price-free New inquiry and Inquiry confirmation emails.
* **Inquiry checkout fields**: Added configurable Company, Phone, Address, and Message settings for inquiry checkout.
* **Core tab settings**: Added shop-wide Core tab title, priority, and visibility controls.
* **Price hiding**: Added quote-mode product-price suppression and Inquiry price-free customer views with the Store API limitation.

## 2026-11-06
* **Variable length**: Added Store API request capture, validation and cart-line preservation.
* **Germanized integration**: Recorded ADR 0005; suppress Germanized shopmarks and non-kept legal checkboxes only during Quote mode.
* **Germanized privacy checkbox**: The built-in privacy checkbox is registration-only by default and requires account creation when rendered at checkout.
* **Access block**: Added audience-specific tab content, registration notices, and logged-in cache bypass guidance.
* **Price per length unit**: Added opt-in product pricing based on cart-item length, preserving Quote mode price hiding.
* **Protected files**: Added ADR 0007 for denied uploads storage, access-controlled delivery, and host configuration requirements.
* **Download log privacy**: Added opt-in file-download records with retention, personal-data tools, and account-deletion cleanup.
* **Security hardening**: Recorded ADR 0020 for module-independent protected-file and privacy behavior, MIME controls, non-overwriting moves, REST redaction, and corrected access/length APIs.
* **Release documentation**: Added the cross-module test matrix, hosting requirements, complete decision index, and release checklist.
