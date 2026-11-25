#!/usr/bin/env bash
#
# Verifies that every place that carries the plugin version agrees:
#   - plugin header "Version:" == PHP constant JayPee\CatalogSuite\VERSION
#   - package.json "version" and readme.txt "Stable tag" == the plugin version
#   - CHANGELOG.md has a "## [X.Y.Z]" section and readme.txt a "= X.Y.Z =" entry for it
#   - (optional) git tag vX.Y.Z == plugin version
#
# Usage: scripts/check-version.sh [<tag-or-ref>]
#   e.g. scripts/check-version.sh v1.0.0
#        scripts/check-version.sh refs/tags/v1.0.0
# CHECK_VERSION_ROOT points the check at another directory (the guardrail fixtures use it).

set -euo pipefail

ROOT_DIR="${CHECK_VERSION_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
PLUGIN_FILE="$ROOT_DIR/jp-catalog-suite.php"

fail=0
problem() {
	echo "check-version: $1" >&2
	fail=1
}

header_version="$(sed -n -E 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*([0-9][^[:space:]]*).*/\1/p' "$PLUGIN_FILE" | head -n1)"
const_version="$(sed -n -E "s/^const VERSION[[:space:]]*=[[:space:]]*'([^']+)'.*/\\1/p" "$PLUGIN_FILE" | head -n1)"

if [[ -z "$header_version" || -z "$const_version" ]]; then
	echo "check-version: could not read the version from $PLUGIN_FILE" >&2
	exit 1
fi

if [[ "$header_version" != "$const_version" ]]; then
	problem "plugin header version ($header_version) != VERSION constant ($const_version)"
fi

package_version="$(sed -n -E 's/^[[:space:]]*"version":[[:space:]]*"([^"]+)".*/\1/p' "$ROOT_DIR/package.json" | head -n1)"
if [[ "$package_version" != "$header_version" ]]; then
	problem "package.json version (${package_version:-missing}) != plugin version ($header_version)"
fi

stable_tag="$(sed -n -E 's/^Stable tag:[[:space:]]*([^[:space:]]+).*/\1/p' "$ROOT_DIR/readme.txt" | head -n1)"
if [[ "$stable_tag" != "$header_version" ]]; then
	problem "readme.txt Stable tag (${stable_tag:-missing}) != plugin version ($header_version)"
fi

if ! grep -q -F "= $header_version =" "$ROOT_DIR/readme.txt"; then
	problem "readme.txt has no changelog entry '= $header_version ='"
fi

if ! grep -q -E "^## \[$header_version\]" "$ROOT_DIR/CHANGELOG.md"; then
	problem "CHANGELOG.md has no '## [$header_version]' section"
fi

if [[ "$fail" -ne 0 ]]; then
	exit 1
fi

echo "check-version: plugin version is $header_version"

ref="${1:-}"
if [[ -n "$ref" ]]; then
	tag="${ref#refs/tags/}"
	tag="${tag#v}"
	if [[ "$tag" != "$header_version" ]]; then
		echo "check-version: git tag ($ref) != plugin version ($header_version)" >&2
		exit 1
	fi
	echo "check-version: tag $ref matches the plugin version"
fi
