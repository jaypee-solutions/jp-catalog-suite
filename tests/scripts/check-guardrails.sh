#!/bin/sh
set -eu
root=$(CDPATH= cd -- "$(dirname "$0")/../.." && pwd)
tmp=$(mktemp -d); trap 'rm -rf "$tmp"' EXIT HUP INT TERM
mkdir -p "$tmp/adr" "$tmp/decisions" "$tmp/includes"
adr="$tmp/adr"; decisions="$tmp/decisions"; index="$tmp/index.md"
cat > "$adr/0001-first.md" <<'EOT'
## Status
Accepted
## Context
Context
## Decision
Decision
## Consequences
Consequences
EOT
cp "$adr/0001-first.md" "$decisions/0001-first.md"
printf '[decision](decisions/0001-first.md)\n' > "$index"
"$root/scripts/check-adr.sh" "$adr" "$decisions" "$index"
cp "$adr/0001-first.md" "$adr/0001-second.md"; cp "$adr/0001-first.md" "$decisions/0001-second.md"; printf '[decision](decisions/0001-second.md)\n' >> "$index"
if "$root/scripts/check-adr.sh" "$adr" "$decisions" "$index" 2>/dev/null; then echo 'duplicate ADR number was accepted' >&2; exit 1; fi
rm "$adr/0001-second.md" "$decisions/0001-second.md"; sed 's/0001/0002/g' "$adr/0001-first.md" > "$adr/0002-second.md"
if "$root/scripts/check-adr.sh" "$adr" "$decisions" "$index" 2>/dev/null; then echo 'missing ADR mirror was accepted' >&2; exit 1; fi
cp "$adr/0002-second.md" "$decisions/0002-second.md"
if "$root/scripts/check-adr.sh" "$adr" "$decisions" "$index" 2>/dev/null; then echo 'missing ADR index link was accepted' >&2; exit 1; fi
mkdir -p "$tmp/pot/includes" "$tmp/pot/templates" "$tmp/pot/blocks" "$tmp/pot/languages"
printf "<?php __( 'Fixture missing', 'jp-catalog-suite' ); _n( 'Single fixture', 'Plural fixture', 2, 'jp-catalog-suite' );\n" > "$tmp/pot/includes/fixture.php"
printf 'msgid ""\nmsgid "Fixture missing"\nmsgid "Single fixture"\n' > "$tmp/pot/languages/jp-catalog-suite.pot"
if php "$root/scripts/check-pot.php" "$tmp/pot" 2>/dev/null; then echo 'missing plural POT msgid was accepted' >&2; exit 1; fi
printf 'msgid ""\nmsgid "Fixture missing"\nmsgid "Single fixture"\nmsgid "Plural fixture"\n' > "$tmp/pot/languages/jp-catalog-suite.pot"
php "$root/scripts/check-pot.php" "$tmp/pot"
cat > "$tmp/includes/hooks.php" <<'EOT'
<?php apply_filters( 'jps_cs_fixture_hook', $value );
EOT
printf '| `jps_cs_fixture_hook` (filter) |\n' > "$tmp/README.md"
"$root/scripts/check-readme-hooks.sh" "$tmp/README.md" "$tmp/includes"
printf '| `jps_cs_stale_hook` (action) |\n' >> "$tmp/README.md"
if "$root/scripts/check-readme-hooks.sh" "$tmp/README.md" "$tmp/includes" 2>/dev/null; then echo 'stale hook was accepted' >&2; exit 1; fi
printf '| `jps_cs_fixture_hook` (filter) |\n| `jps_cs_missing_hook` (action) |\n' > "$tmp/README.md"
if "$root/scripts/check-readme-hooks.sh" "$tmp/README.md" "$tmp/includes" 2>/dev/null; then echo 'undocumented hook was accepted' >&2; exit 1; fi
echo 'Guardrail checker fixtures passed.'
