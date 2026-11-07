#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
scripts/check-adr.sh
scripts/check-readme-hooks.sh
php scripts/check-pot.php
msgfmt -c -o /dev/null languages/jp-catalog-suite-de_DE.po
msgfmt -c -o /dev/null languages/jp-catalog-suite-de_DE_formal.po
cmp -s languages/jp-catalog-suite-de_DE.po languages/jp-catalog-suite-de_DE_formal.po || { echo 'German formal catalog must be byte-identical to de_DE.' >&2; exit 1; }
if command -v uv >/dev/null 2>&1; then
    uv run "$PWD/.agents/skills/okf-validate/scripts/okf_validate.py" .okf
else
    echo 'Skipping OKF validation: uv is not installed.'
fi
