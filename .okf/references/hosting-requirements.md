---
type: runbook
title: Protected-file hosting requirements
description: Host configuration for uploading and securely serving protected Catalog Suite files.
tags: [hosting, protected-files, release]
timestamp: 2026-11-06T00:00:00Z
---

# Protected-file hosting requirements

## Upload limits

The PHP delivery handler supports files up to 256 MB and does not support HTTP Range requests. Set PHP `upload_max_filesize` and `post_max_size` to at least 256 MB (with suitable memory/time limits), and set Traefik's request-body limit no lower than the intended upload size. Validate limits in the actual production route, not just PHP configuration.

## Apache X-Sendfile

For efficient large-file delivery, enable `mod_xsendfile` and configure the virtual host:

```apache
XSendFile On
XSendFilePath /absolute/path/to/wordpress/wp-content/uploads/jps-cs-protected
```

Choose `X-Sendfile` in Catalog Suite file-delivery settings. The protected directory path must be an absolute path readable by Apache.

## nginx direct-access denial

The plugin's `.htaccess` rule does not protect nginx. Deny direct access in the server configuration:

```nginx
location ^~ /wp-content/uploads/jps-cs-protected/ {
    deny all;
}
```

Then verify an unauthorized direct URL is denied and authorized delivery still succeeds through WordPress.

## Varnish

Access-dependent content must not be served from a shared cache to the wrong visitor. Pass logged-in and WooCommerce session requests:

```vcl
if (req.http.Cookie ~ "wordpress_logged_in_" || req.http.Cookie ~ "woocommerce_items_in_cart" || req.http.Cookie ~ "wp_woocommerce_session_") {
    return (pass);
}
```

Protected-file responses are private and no-store; verify the proxy honors these response headers.

## Citations

- `README.md` hosting guidance and `includes/class-protected-files.php` delivery behavior.
