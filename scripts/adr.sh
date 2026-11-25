#!/usr/bin/env bash
#
# ADR scaffolding. Every ADR in docs/adr has a same-number OKF mirror with frontmatter,
# an index link and a log entry (docs/agents/conventions.md).
#
# Usage:
#   scripts/adr.sh new "Title" "One-line description" "tag-one,tag-two" [slug]
#       Creates docs/adr/NNNN-slug.md (sections to fill in), its mirror, the index link
#       and today's log entry. NNNN is the next unused number.
#   scripts/adr.sh sync NNNN
#       Rewrites the mirror body from docs/adr/NNNN-*.md and keeps the mirror's frontmatter.
# ADR_ROOT points the script at another directory (the guardrail fixtures use it).

set -euo pipefail

ROOT_DIR="${ADR_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
ADR_DIR="$ROOT_DIR/docs/adr"
DECISIONS_DIR="$ROOT_DIR/.okf/decisions"
INDEX="$ROOT_DIR/.okf/index.md"
LOG="$ROOT_DIR/.okf/log.md"

die() {
	echo "adr: $1" >&2
	exit 1
}

sync_mirror() {
	local adr="$1" mirror="$DECISIONS_DIR/${1##*/}" frontmatter
	[[ -f "$mirror" ]] || die "no mirror $mirror to sync (use 'new' for a new ADR)"
	# Frontmatter: from the first line to the closing '---'.
	frontmatter="$(awk 'NR==1 && /^---$/ {print; inside=1; next} inside {print} inside && /^---$/ {exit}' "$mirror")"
	{
		printf '%s\n\n' "$frontmatter"
		cat "$adr"
	} >"$mirror.tmp"
	mv "$mirror.tmp" "$mirror"
	echo "adr: synced ${mirror#"$ROOT_DIR"/}"
}

case "${1:-}" in
	new)
		title="${2:-}"
		description="${3:-}"
		tags="${4:-}"
		[[ -n "$title" && -n "$description" && -n "$tags" ]] || die 'usage: adr.sh new "Title" "One-line description" "tag-one,tag-two" [slug]'
		slug="${5:-$(printf '%s' "$title" | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-z0-9]+/-/g; s/^-+//; s/-+$//' | cut -c1-60 | sed -E 's/-+$//')}"
		last="$(find "$ADR_DIR" -maxdepth 1 -name '[0-9][0-9][0-9][0-9]-*.md' | sed -E 's#.*/([0-9]{4})-.*#\1#' | sort | tail -n1)"
		number="$(printf '%04d' $((10#${last:-0} + 1)))"
		name="$number-$slug.md"
		adr="$ADR_DIR/$name"
		[[ ! -e "$adr" ]] || die "$adr already exists"

		mkdir -p "$ADR_DIR" "$DECISIONS_DIR"
		cat >"$adr" <<EOF
# $title

## Status

Accepted.

## Context

TODO

## Decision

TODO

## Consequences

TODO
EOF
		today="$(date +%F)"
		{
			printf -- '---\ntype: decision\ntitle: %s\ndescription: %s\ntags: [%s]\ntimestamp: %sT00:00:00Z\n---\n\n' "$title" "$description" "${tags//,/, }" "$today"
			cat "$adr"
		} >"$DECISIONS_DIR/$name"

		entry="* [$title](decisions/$name) - $description"
		if grep -q -F '](decisions/0' "$INDEX"; then
			awk -v entry="$entry" '{ lines[NR] = $0 } /\]\(decisions\/0/ { last = NR } END { for (i = 1; i <= NR; i++) { print lines[i]; if (i == last) print entry } }' "$INDEX" >"$INDEX.tmp"
		else
			{ cat "$INDEX"; echo "$entry"; } >"$INDEX.tmp"
		fi
		mv "$INDEX.tmp" "$INDEX"

		last_heading="$(grep -E '^## ' "$LOG" | tail -n1 || true)"
		{
			[[ "$last_heading" == "## $today" ]] || printf '\n## %s\n' "$today"
			printf '* **%s**: Recorded ADR %s: %s\n' "$title" "$number" "$description"
		} >>"$LOG"

		echo "adr: created docs/adr/$name; fill in Context, Decision and Consequences, then run: scripts/adr.sh sync $number"
		;;
	sync)
		number="${2:-}"
		[[ "$number" =~ ^[0-9]{4}$ ]] || die "usage: adr.sh sync NNNN"
		adr="$(find "$ADR_DIR" -maxdepth 1 -name "$number-*.md" | head -n1)"
		[[ -n "$adr" ]] || die "no docs/adr/$number-*.md"
		sync_mirror "$adr"
		;;
	*)
		die 'usage: adr.sh new "Title" "One-line description" "tag-one,tag-two" [slug] | adr.sh sync NNNN'
		;;
esac
