#!/usr/bin/env bash
# Fails when a git-ignored file sits in a source directory.
#
# A .gitignore pattern that is not anchored (`blocks/` instead of `/blocks/`)
# also matches src/blocks/, and the sources in there are then silently left out
# of every commit. Ignored build output belongs in /blocks/, /build/ and friends,
# never in includes/, src/, templates/ or tests/.
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."

# Release archives and checkouts without a .git directory have nothing to check.
if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    exit 0
fi

ignored="$(git -c core.quotePath=false ls-files --others --ignored --exclude-standard -- includes src templates tests)"
if [[ -n "$ignored" ]]; then
    echo 'check-ignored-sources: these source files are git-ignored and would never be committed:' >&2
    while IFS= read -r file; do
        printf '  %s  (rule: %s)\n' "$file" "$(git check-ignore -v -- "$file" | cut -f1)" >&2
    done <<< "$ignored"
    echo 'Anchor the offending .gitignore pattern (for example /blocks/) or remove the file.' >&2
    exit 1
fi
