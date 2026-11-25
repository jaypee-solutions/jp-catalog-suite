#!/usr/bin/env bash
#
# Bumps the plugin version in every place that carries it and turns the
# "Unreleased" changelog section into a release section.
#
# Usage: scripts/bump-version.sh X.Y.Z [readme-line...]
#   Without readme lines, the readme.txt changelog entry is derived from the
#   Unreleased bullets of CHANGELOG.md (first sentence of each bullet).
# BUMP_VERSION_ROOT points the script at another directory (the guardrail fixtures use it).

set -euo pipefail

ROOT_DIR="${BUMP_VERSION_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
PLUGIN_FILE="$ROOT_DIR/jp-catalog-suite.php"
CHANGELOG="$ROOT_DIR/CHANGELOG.md"
README="$ROOT_DIR/readme.txt"
PACKAGE="$ROOT_DIR/package.json"

die() {
	echo "bump-version: $1" >&2
	exit 1
}

new="${1:-}"
[[ "$new" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || die "usage: bump-version.sh X.Y.Z [readme-line...]"
shift

current="$(sed -n -E 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*([0-9][^[:space:]]*).*/\1/p' "$PLUGIN_FILE" | head -n1)"
[[ -n "$current" ]] || die "could not read the current version from $PLUGIN_FILE"
[[ "$current" != "$new" && "$(printf '%s\n%s\n' "$current" "$new" | sort -V | tail -n1)" == "$new" ]] || die "$new is not newer than $current"
grep -q -E '^## \[Unreleased\]' "$CHANGELOG" || die "CHANGELOG.md has no '## [Unreleased]' section"

# Unreleased bullets: from the heading to the next release heading.
unreleased="$(awk '/^## \[Unreleased\]/ {on=1; next} /^## \[/ {on=0} on' "$CHANGELOG")"
grep -q -E '^- ' <<<"$unreleased" || die "the Unreleased section of CHANGELOG.md has no bullets"

readme_lines=()
if [[ "$#" -gt 0 ]]; then
	for line in "$@"; do
		readme_lines+=("* $line")
	done
else
	prefix=""
	while IFS= read -r line; do
		case "$line" in
			"### Fixed"*) prefix="Fix: " ;;
			"### "*) prefix="" ;;
			"- "*)
				text="${line#- }"
				text="${text//\`/}"
				first="${text%%. *}"
				[[ "$first" == *. ]] || first="$first."
				readme_lines+=("* ${prefix}${first}")
				;;
		esac
	done <<<"$unreleased"
fi

today="$(date +%F)"
tmp="$(mktemp)"
trap 'rm -f "$tmp"' EXIT

awk -v new="$new" -v today="$today" '
	{ print }
	/^## \[Unreleased\]/ { print ""; print "## [" new "] - " today }
' "$CHANGELOG" >"$tmp" && cat "$tmp" >"$CHANGELOG"

entry="= $new ="
for line in "${readme_lines[@]}"; do
	entry+=$'\n'"$line"
done
ENTRY="$entry" awk -v new="$new" '
	/^Stable tag:/ { print "Stable tag: " new; next }
	{ print }
	/^== Changelog ==/ { print ""; print ENVIRON["ENTRY"] }
' "$README" >"$tmp" && cat "$tmp" >"$README"

sed -E -e "s/^([[:space:]]*\*[[:space:]]*Version:[[:space:]]*)[0-9][^[:space:]]*/\1$new/" \
	-e "s/^(const VERSION[[:space:]]*=[[:space:]]*')[^']+(')/\1$new\2/" "$PLUGIN_FILE" >"$tmp" && cat "$tmp" >"$PLUGIN_FILE"

awk -v new="$new" '
	!done && /^[[:space:]]*"version":/ { sub(/"version":[[:space:]]*"[^"]*"/, "\"version\": \"" new "\""); done=1 }
	{ print }
' "$PACKAGE" >"$tmp" && cat "$tmp" >"$PACKAGE"

CHECK_VERSION_ROOT="$ROOT_DIR" "$(dirname "${BASH_SOURCE[0]}")/check-version.sh"
echo "bump-version: $current -> $new"
