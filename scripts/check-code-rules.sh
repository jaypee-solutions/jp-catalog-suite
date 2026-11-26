#!/usr/bin/env bash
# Mechanical rules from CODING_STANDARDS.md: patterns that are wrong wherever they
# appear, so a check catches them before a reviewer has to. Judgement calls stay
# in CODING_STANDARDS.md. Comment-only lines are ignored.
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."

failed=0

# rule PATTERN MESSAGE DIR...: PATTERN is an extended regex matched against PHP code.
rule() {
	local pattern="$1" message="$2" dir hits
	shift 2
	for dir in "$@"; do
		[[ -d "$dir" ]] || continue
		hits="$(grep -rnE --include='*.php' -e "$pattern" "$dir" | grep -vE '^[^:]+:[0-9]+:[[:space:]]*(//|/\*|\*|#)' || true)"
		if [[ -n "$hits" ]]; then
			printf '%s\n' "$hits" >&2
			printf 'check-code-rules: %s\n' "$message" >&2
			failed=1
		fi
	done
}

rule '(^|[^A-Za-z0-9_>:$])menu_page_url[[:space:]]*\(' \
	'menu_page_url() is empty in admin-post, REST and cron requests; build admin URLs with admin_url().' \
	includes

exit "$failed"
