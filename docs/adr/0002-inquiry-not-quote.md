# Inquiry, not quote

## Status
Accepted; scope amended by ADR 0022

## Context
The customer submits a non-binding request for an offer; the seller's priced offer is the Quote. Reusing quote/order terminology for the submitted request would blur distinct domain concepts and make stored identifiers costly to change.

## Decision
Use Inquiry for the customer's submission. Persist status `jps-inquiry`, metadata `_jps_cs_inquiry`, and `jps_cs_inquiry_*` hooks and identifiers. Quote names only the shop module and the seller's offer remains outside the plugin's scope.

## Consequences
WooCommerce order objects carry inquiries for checkout compatibility, while customer-facing and code terminology distinguish them from orders. The inquiry status and metadata remain meaningful when Quote mode is disabled.
