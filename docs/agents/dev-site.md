# Deployed dev site

The shop owner's dev site, `https://dev.koeberle-schmid.com`, runs this plugin behind a web server and possibly a cache or proxy. Reports from it arrive as screenshots; the agent cannot open the site. Ask for what the checks below print.

## Is a cache serving the wrong page?

A guest page served from a cache to a logged-in visitor (an Access block, a protected File block or a File list showing the login notice to a user who is logged in) is a hosting-side problem (ADR 0008): for logged-in requests that render protected content the plugin sends WordPress's no-cache headers (`Cache-Control` with `no-cache`, and `no-store` in current WordPress) and defines `DONOTCACHEPAGE`, and the cache has to honour them.

1. In the browser, log in, open the page and copy the `Cookie` header of the page request from the developer tools (Network tab).
2. Run `just headers https://dev.koeberle-schmid.com/product/<slug>/ "<cookie header>"`. It prints the status and the cache, encoding and cookie headers as a guest and again with the login cookie.
3. Read the result:
   - With the login cookie, `Cache-Control` must contain `no-store` or `no-cache`, and `Age`, `X-Cache: HIT`, `CF-Cache-Status: HIT` or `X-Litespeed-Cache: hit` must be absent. A hit there is the cache serving a stored page to a logged-in user; the fix is the cache's bypass rule for the `wordpress_logged_in_*` cookie (see `.okf/references/hosting-requirements.md`, Varnish section).
   - The guest response may be cacheable.
   - `Content-Encoding: gzip` or `br` without `Content-Length` on a protected file download is why a browser's save dialog shows "File Size: Unknown"; exclude the protected-file endpoint (`?jps_cs_file=`) from compression.

## What to ask the reporter

The URL of the affected page, whether they were logged in, and the output of `just headers`. A screenshot of the browser's Network tab for the failing request (status, response headers) works too.
