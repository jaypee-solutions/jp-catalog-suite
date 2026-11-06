# ADR 0008: Caching

## Status

Accepted

## Context

The Access block renders different product-tab content for guests and authorized users. A shared full-page cache could expose protected content to guests or hide it from an authorized user. Guest content is public and identical for all guests.

## Decision

When a logged-in request renders protected content through the Access block, define `DONOTCACHEPAGE` and send WordPress no-cache headers. Do not vary guest output by identity. Document reverse-proxy behavior and provide Varnish configuration that passes WordPress-authenticated requests.

## Consequences

Logged-in product pages that render protected content bypass page caching, while guests can continue to use the public cached variant. Reverse proxies must honor the authenticated-cookie pass rule; plugin-level headers alone cannot guarantee that a proxy will not serve a cached response.
