#!/usr/bin/env bash
#
# Verifies a release ZIP against the checkout it was built from.
#
# Usage: scripts/verify-release-zip.sh <zip>
#
# Fails when
#   a) a top-level path of the repository (tracked, or untracked and not ignored)
#      is in neither scripts/release-paths.txt nor scripts/release-dev-only.txt,
#   b) a path from release-paths.txt exists in the checkout but not in the ZIP,
#   c) a languages/*.po has no compiled .mo in the ZIP,
#   d) a src/blocks/<name>/block.json has no blocks/<name>/block.json in the ZIP,
#   e) the plugin header version in the ZIP differs from the checkout.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

SLUG="jp-catalog-suite"
ZIP="${1:-}"

if [[ -z "$ZIP" || ! -f "$ZIP" ]]; then
	echo "usage: scripts/verify-release-zip.sh <zip>" >&2
	exit 2
fi

if ! command -v unzip >/dev/null 2>&1; then
	echo "verify-release-zip: unzip is required" >&2
	exit 1
fi

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
	echo "verify-release-zip: run this inside the git checkout the ZIP was built from" >&2
	exit 1
fi

path_list() {
	sed -e 's/#.*//' -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' -e '/^$/d' "$1"
}

entries="$(unzip -Z1 "$ZIP")"

in_zip() {
	awk -v path="$SLUG/$1" '$0 == path || index($0, path "/") == 1 { found = 1 } END { exit !found }' <<< "$entries"
}

failed=0
fail() {
	echo "verify-release-zip: $1" >&2
	failed=1
}

if [[ -n "$(awk -v prefix="$SLUG/" 'index($0, prefix) != 1' <<< "$entries")" ]]; then
	fail "$ZIP must contain only the folder $SLUG/"
fi

release_paths="$(path_list scripts/release-paths.txt)"
dev_only_paths="$(path_list scripts/release-dev-only.txt)"

# a) a new top-level path has to be classified, or a runtime file ships by accident or not at all
while IFS= read -r top; do
	if ! grep -qxF -- "$top" <<< "$release_paths"$'\n'"$dev_only_paths"; then
		fail "top-level path '$top' is in neither scripts/release-paths.txt nor scripts/release-dev-only.txt"
	fi
done < <(git -c core.quotePath=false ls-files --cached --others --exclude-standard | cut -d/ -f1 | sort -u)

# b) everything that ships has to be in the ZIP
while IFS= read -r path; do
	if [[ -e "$path" ]] && ! in_zip "$path"; then
		fail "'$path' is listed in scripts/release-paths.txt and exists, but is missing from the ZIP"
	fi
done <<< "$release_paths"

# c) WordPress loads .mo catalogs, never .po
for po_file in languages/*.po; do
	[[ -e "$po_file" ]] || continue
	if ! in_zip "${po_file%.po}.mo"; then
		fail "$po_file has no compiled ${po_file%.po}.mo in the ZIP"
	fi
done

# d) the built block assets, not only their sources
for block_json in src/blocks/*/block.json; do
	[[ -e "$block_json" ]] || continue
	block_name="$(basename "$(dirname "$block_json")")"
	if ! in_zip "blocks/$block_name/block.json"; then
		fail "block '$block_name' has no blocks/$block_name/block.json in the ZIP (was npm run build skipped?)"
	fi
done

# e) a ZIP built from another checkout state would ship the wrong version
header_version() {
	sed -n -E 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*([0-9][^[:space:]]*).*/\1/p' | head -n1
}

checkout_version="$(header_version < "$SLUG.php")"
zip_version="$(unzip -p "$ZIP" "$SLUG/$SLUG.php" 2>/dev/null | header_version || true)"
if [[ -z "$zip_version" ]]; then
	fail "the ZIP has no readable plugin header in $SLUG/$SLUG.php"
elif [[ "$zip_version" != "$checkout_version" ]]; then
	fail "plugin header version in the ZIP ($zip_version) differs from the checkout ($checkout_version)"
fi

if [[ "$failed" -ne 0 ]]; then
	exit 1
fi

echo "verify-release-zip: $(basename "$ZIP") matches the checkout (version ${zip_version})"
