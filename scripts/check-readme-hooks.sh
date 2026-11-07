#!/bin/sh
set -eu
readme=${1:-README.md}; shift || true
roots="${*:-includes templates uninstall.php jp-catalog-suite.php}"
code=$(mktemp); docs=$(mktemp); trap 'rm -f "$code" "$docs"' EXIT HUP INT TERM
for root in $roots; do [ -e "$root" ] && find "$root" -type f -name '*.php' -exec perl -ne 'while(/\b(?:apply_filters|do_action|add_action|add_filter)\s*\(\s*[\x27\x22](jps_cs_[A-Za-z0-9_]+)/g){print "$1\n"}' {} +; done | sort -u > "$code"
perl -ne 'if (/^\|.*`(jps_cs_[A-Za-z0-9_]+)`/){print "$1\n"}' "$readme" | sort -u > "$docs"
fail=0
while IFS= read -r hook; do [ -z "$hook" ] || grep -Fxq "$hook" "$docs" || { echo "$readme: undocumented hook $hook" >&2; fail=1; }; done < "$code"
while IFS= read -r hook; do [ -z "$hook" ] || grep -Fxq "$hook" "$code" || { echo "$readme: stale documented hook $hook" >&2; fail=1; }; done < "$docs"
exit "$fail"
