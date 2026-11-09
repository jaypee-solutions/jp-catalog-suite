---
okf_version: "0.1"
---

# Catalog Suite knowledge

* [Module architecture](decisions/0001-module-architecture.md) - Registry, settings, namespace, and asset conventions.
* [WooCommerce 11.2.2 source facts](references/woocommerce-source-facts.md) - Verified source locations used during design.
* [Variable length decision](decisions/0009-variable-length-as-product-option.md) - Customer-entered length as simple product cart data.
* [Inquiry, not quote](decisions/0002-inquiry-not-quote.md) - Customer inquiries are distinct from the seller's Quote.
* [Inquiries without gateway](decisions/0003-inquiries-without-gateway.md) - Inquiry intake does not depend on payment processing.
* [Global product tabs](decisions/0006-tabs-as-block-editor-cpt.md) - Block-editor posts with global/category scope, priority, and REST access restrictions.
* [Variable-length variation overrides](decisions/0013-variable-length-variation-overrides.md) - Variation-specific limits inherit from the variable product and are enforced at cart insertion.
* [E2E environment isolation](references/e2e-ci-isolation.md) - CI-specific fixtures and independent E2E assertions.
* [Product tab overrides](decisions/0012-product-tab-overrides.md) - Product-specific tabs replace global tabs and hide them per product.
* [Quote-mode customer labels](decisions/0011-quote-mode-labels.md) - Configurable translated labels for customer-facing inquiry flows.
* [Core tab settings](decisions/0015-core-tab-settings.md) - Shop-wide Core tab titles, priorities, and visibility.
* [Price-free Inquiry emails](decisions/0010-price-free-inquiry-emails.md) - Configurable price-free administrator and customer notifications for new Inquiries.
* [Inquiry checkout fields](decisions/0014-inquiry-checkout-fields.md) - Configurable inquiry checkout details in classic and Blocks checkout.
* [Price hiding and Blocks compatibility](decisions/0004-price-hiding-and-blocks-compatibility.md) - Price-free customer views with Blocks presentation and the Store API limitation.
* [Variable length through the Store API](decisions/0016-variable-length-store-api.md) - Store API additions retain validated length as cart item data.
* [Germanized integration](decisions/0005-germanized-integration.md) - Keep Germanized legal notices and checkboxes out of active Quote mode.
* [Access content caching](decisions/0008-caching.md) - Audience-specific Access block content and logged-in cache bypass.
* [Price per length unit](decisions/0018-price-per-length-unit.md) - Opt-in cart pricing based on customer-entered length.
* [Protected files](decisions/0007-protected-files.md) - Denied uploads subdirectory, URL rewriting, and user/file-only endpoint authorization.
* [Download log privacy](decisions/0019-download-log-privacy.md) - Minimal user/file/product records, configurable retention and privacy tools.
* [Protected-file hosting requirements](references/hosting-requirements.md) - Upload limits, Traefik, X-Sendfile, nginx and Varnish deployment guidance.
* [Security hardening](decisions/0020-security-hardening.md) - Protected-file routing, access policy, Download-log lifecycle and length validation boundaries.
* [Persisted names and Quote mode ownership](decisions/0021-persisted-names-and-quote-mode-gating.md) - Canonical persisted names, canonical hidden-tab metadata, and Quote module lifecycle ownership.
* [Tabs managed on their owners](decisions/0022-tabs-managed-on-owners.md) - Single-owner tabs managed on product, category and settings screens through a block-editor dialog; one-time split migration.
* [Tab editor dialog integration facts](references/tab-dialog-integration-facts.md) - Verified WordPress/WooCommerce behaviors behind the tab management dialog.
