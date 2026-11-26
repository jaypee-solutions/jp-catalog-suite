#!/usr/bin/env bash
# Compiles every languages/*.po to the .mo WordPress loads at runtime (the .mo
# files are gitignored), so integration and E2E runs see the translations the
# checkout ships. Without msgfmt it fails loudly: a stale or missing catalog would
# make translation tests pass or fail for the wrong reason.
set -euo pipefail
ROOT_DIR="${COMPILE_TRANSLATIONS_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"

shopt -s nullglob
catalogs=( "$ROOT_DIR"/languages/*.po )
shopt -u nullglob

if [[ ${#catalogs[@]} -eq 0 ]]; then
	exit 0
fi

if ! command -v msgfmt >/dev/null 2>&1; then
	echo "compile-translations: msgfmt (gettext) is required to compile languages/*.po; install gettext." >&2
	exit 1
fi

for catalog in "${catalogs[@]}"; do
	msgfmt --check --output-file="${catalog%.po}.mo" "$catalog"
done
