#!/usr/bin/env bash
#
# Prints the response headers that decide whether a cache, a proxy or compression sits between
# the site and the browser: status, cache headers, content encoding and length, cookies set.
# Run it twice (as a guest, and with a login cookie) to see whether logged-in requests bypass the cache.
#
# Usage: scripts/headers.sh URL [COOKIE]
#   COOKIE is the Cookie header value copied from the browser's developer tools,
#   for example "wordpress_logged_in_abc=...; wordpress_sec_abc=...".

set -euo pipefail

url="${1:-}"
cookie="${2:-}"
[[ -n "$url" ]] || {
	echo "usage: headers.sh URL [COOKIE]" >&2
	exit 1
}

show() {
	local label="$1"
	shift
	echo "== $label"
	curl -sS --max-time 20 -o /dev/null -D - -H 'Accept-Encoding: gzip, br' "$@" "$url" |
		tr -d '\r' |
		awk -F: '
			NR == 1 { print; next }
			tolower($1) ~ /^(cache-control|expires|pragma|age|vary|etag|last-modified|content-encoding|content-length|transfer-encoding|content-type|content-disposition|server|via|x-cache.*|x-litespeed.*|x-varnish.*|x-proxy.*|x-wp-.*|cf-cache-status|cf-ray|x-sendfile)$/ { print }
			tolower($1) == "set-cookie" { sub(/=.*/, "", $2); print "set-cookie:" $2 }
		'
}

show "guest"
if [[ -n "$cookie" ]]; then
	show "with login cookie" -H "Cookie: $cookie"
fi
