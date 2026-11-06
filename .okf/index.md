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
* [Variable-length variation overrides](decisions/0010-variable-length-variation-overrides.md) - Variation-specific limits inherit from the variable product and are enforced at cart insertion.
* [E2E environment isolation](references/e2e-ci-isolation.md) - CI-specific fixtures and independent E2E assertions.
