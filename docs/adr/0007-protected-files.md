# Protected files

## Status

Accepted

## Context

CAD files must reach authorized users and never leak through a directly addressable uploads URL. Attachments must remain under the uploads directory for WordPress media-library compatibility. PHP fallback delivery is limited to 256 MB.

## Decision

Store protected uploads in `uploads/jps-cs-protected/`, with a generated Apache deny rule and index file, and rewrite protected attachment URLs to the plugin download endpoint. The endpoint redirects attachments that are no longer protected to their public URL; checks missing and unprotected files before login and access; and decides access from the current user and attachment only. The client-provided product identifier is never part of the access context and is used only by delivery observers/logging. Deliver with X-Sendfile when configured/available, otherwise PHP streaming, with private no-store headers. PDFs remain public unless individually protected. Document PHP/Traefik upload limits and nginx deny configuration for hosting operators.

## Consequences

Protected files cannot be previewed and direct paths must remain denied by the web server. PHP delivery supports files up to 256 MB and does not implement Range requests. The upload directory and protected attachments are deleted only when uninstall data deletion is opted into. Download logs, when enabled, require privacy disclosure and retention management.
