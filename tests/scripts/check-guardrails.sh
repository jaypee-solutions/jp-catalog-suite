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
sed 's/## Consequences/## Missing/' "$adr/0001-first.md" | sed 's/0001/0002/g' > "$adr/0002-no-consequences.md"
cp "$adr/0002-no-consequences.md" "$decisions/0002-no-consequences.md"
printf '[decision](decisions/0002-no-consequences.md)\n' >> "$index"
if "$root/scripts/check-adr.sh" "$adr" "$decisions" "$index" 2>/dev/null; then echo 'missing ADR heading was accepted' >&2; exit 1; fi
rm "$adr/0002-no-consequences.md" "$decisions/0002-no-consequences.md"
printf '[decision](decisions/0001-first.md)\n' > "$index"
cp "$adr/0001-first.md" "$adr/0001-second.md"; cp "$adr/0001-first.md" "$decisions/0001-second.md"; printf '[decision](decisions/0001-second.md)\n' >> "$index"
if "$root/scripts/check-adr.sh" "$adr" "$decisions" "$index" 2>/dev/null; then echo 'duplicate ADR number was accepted' >&2; exit 1; fi
rm "$adr/0001-second.md" "$decisions/0001-second.md"; sed 's/0001/0002/g' "$adr/0001-first.md" > "$adr/0002-second.md"
printf '[decision](decisions/0002-second.md)\n' >> "$index"
if "$root/scripts/check-adr.sh" "$adr" "$decisions" "$index" 2>/dev/null; then echo 'missing ADR mirror was accepted' >&2; exit 1; fi
cp "$adr/0002-second.md" "$decisions/0002-second.md"
grep -v '0002-second' "$index" > "$tmp/index-without-second.md"
mkdir -p "$tmp/pot/includes" "$tmp/pot/templates" "$tmp/pot/blocks" "$tmp/pot/languages"
printf "<?php __( 'Fixture missing', 'jp-catalog-suite' ); _n( 'Single fixture', 'Plural fixture', 2, 'jp-catalog-suite' );\n" > "$tmp/pot/includes/fixture.php"
printf 'msgid ""\nmsgid "Fixture missing"\nmsgid "Single fixture"\n' > "$tmp/pot/languages/jp-catalog-suite.pot"
if php "$root/scripts/check-pot.php" "$tmp/pot" 2>/dev/null; then echo 'missing plural POT msgid was accepted' >&2; exit 1; fi
printf 'msgid ""\nmsgid "Fixture missing"\nmsgid "Single fixture"\nmsgid_plural "Plural fixture"\n' > "$tmp/pot/languages/jp-catalog-suite.pot"
php "$root/scripts/check-pot.php" "$tmp/pot"
printf "<?php _n_noop( 'One item', 'Many items', 'jp-catalog-suite' );\n" > "$tmp/pot/includes/noop.php"
if php "$root/scripts/check-pot.php" "$tmp/pot" 2>/dev/null; then echo 'missing _n_noop msgid was accepted' >&2; exit 1; fi
printf 'msgid "One item"\nmsgid_plural "Many items"\n' >> "$tmp/pot/languages/jp-catalog-suite.pot"
php "$root/scripts/check-pot.php" "$tmp/pot"
mkdir -p "$tmp/pot/blocks/demo"
printf "const label = __( 'JavaScript fixture', 'jp-catalog-suite' );\n" > "$tmp/pot/blocks/demo/edit.js"
if php "$root/scripts/check-pot.php" "$tmp/pot" 2>/dev/null; then echo 'missing JavaScript POT msgid was accepted' >&2; exit 1; fi
printf 'msgid "JavaScript fixture"\n' >> "$tmp/pot/languages/jp-catalog-suite.pot"
php "$root/scripts/check-pot.php" "$tmp/pot"
cat > "$tmp/includes/hooks.php" <<'EOT'
<?php apply_filters( 'jps_cs_fixture_hook', $value );
EOT
printf '| `jps_cs_fixture_hook` (filter) |\n' > "$tmp/README.md"
"$root/scripts/check-readme-hooks.sh" "$tmp/README.md" "$tmp/includes"
printf '| `jps_cs_stale_hook` (action) |\n' >> "$tmp/README.md"
if "$root/scripts/check-readme-hooks.sh" "$tmp/README.md" "$tmp/includes" 2>/dev/null; then echo 'stale hook was accepted' >&2; exit 1; fi
printf "<?php do_action( 'jps_cs_missing_hook' );\n" >> "$tmp/includes/hooks.php"
printf '| `jps_cs_fixture_hook` (filter) |\n' > "$tmp/README.md"
if "$root/scripts/check-readme-hooks.sh" "$tmp/README.md" "$tmp/includes" 2>/dev/null; then echo 'undocumented hook was accepted' >&2; exit 1; fi
# Version places and the bump script
v="$tmp/plugin"; mkdir -p "$v"
printf '<?php\n/**\n * Version:           1.0.0\n */\nconst VERSION     = '"'"'1.0.0'"'"';\n' > "$v/jp-catalog-suite.php"
printf '{\n  "version": "1.0.0"\n}\n' > "$v/package.json"
printf 'Stable tag: 1.0.0\n\n== Changelog ==\n\n= 1.0.0 =\n* Initial.\n' > "$v/readme.txt"
printf '# Changelog\n\n## [Unreleased]\n\n## [1.0.0] - 2026-01-01\n\n### Added\n- Initial.\n' > "$v/CHANGELOG.md"
CHECK_VERSION_ROOT="$v" "$root/scripts/check-version.sh" >/dev/null
cp "$v/package.json" "$tmp/package.json.good"; sed 's/1\.0\.0/0.9.0/' "$tmp/package.json.good" > "$v/package.json"
if CHECK_VERSION_ROOT="$v" "$root/scripts/check-version.sh" 2>/dev/null; then echo 'package.json version mismatch was accepted' >&2; exit 1; fi
cp "$tmp/package.json.good" "$v/package.json"
cp "$v/readme.txt" "$tmp/readme.txt.good"; sed 's/Stable tag: 1\.0\.0/Stable tag: 0.9.0/' "$tmp/readme.txt.good" > "$v/readme.txt"
if CHECK_VERSION_ROOT="$v" "$root/scripts/check-version.sh" 2>/dev/null; then echo 'readme.txt stable tag mismatch was accepted' >&2; exit 1; fi
cp "$tmp/readme.txt.good" "$v/readme.txt"
if BUMP_VERSION_ROOT="$v" "$root/scripts/bump-version.sh" 1.0.1 2>/dev/null; then echo 'bump with an empty Unreleased section was accepted' >&2; exit 1; fi
printf '# Changelog\n\n## [Unreleased]\n\n### Fixed\n- Fixed a thing. More detail.\n\n## [1.0.0] - 2026-01-01\n\n### Added\n- Initial.\n' > "$v/CHANGELOG.md"
if BUMP_VERSION_ROOT="$v" "$root/scripts/bump-version.sh" 1.0.0 2>/dev/null; then echo 'bump to the current version was accepted' >&2; exit 1; fi
BUMP_VERSION_ROOT="$v" "$root/scripts/bump-version.sh" 1.0.1 >/dev/null
CHECK_VERSION_ROOT="$v" "$root/scripts/check-version.sh" >/dev/null
grep -q '^\* Fix: Fixed a thing\.$' "$v/readme.txt" || { echo 'bump did not derive the readme line' >&2; exit 1; }
grep -q '^## \[1\.0\.1\] - ' "$v/CHANGELOG.md" || { echo 'bump did not add the release section' >&2; exit 1; }

# ADR scaffolding: the new ADR is unfinished until its TODO sections are written, and its mirror must match
a="$tmp/adrroot"; mkdir -p "$a/docs/adr" "$a/.okf/decisions"
cp "$adr/0001-first.md" "$a/docs/adr/0022-existing.md"; cp "$adr/0001-first.md" "$a/.okf/decisions/0022-existing.md"
printf '# Index\n* [Existing](decisions/0022-existing.md) - Existing.\n' > "$a/.okf/index.md"; printf '# Log\n' > "$a/.okf/log.md"
ADR_ROOT="$a" "$root/scripts/adr.sh" new 'Fixture decision' 'A fixture.' 'one,two' >/dev/null
test -f "$a/docs/adr/0023-fixture-decision.md" && test -f "$a/.okf/decisions/0023-fixture-decision.md" || { echo 'adr new did not create the ADR and its mirror' >&2; exit 1; }
grep -q 'decisions/0023-fixture-decision.md' "$a/.okf/index.md" || { echo 'adr new did not link the ADR in the index' >&2; exit 1; }
if "$root/scripts/check-adr.sh" "$a/docs/adr" "$a/.okf/decisions" "$a/.okf/index.md" 2>/dev/null; then echo 'ADR with TODO sections was accepted' >&2; exit 1; fi
sed 's/^TODO$/Written./' "$a/docs/adr/0023-fixture-decision.md" > "$a/adr.tmp"; mv "$a/adr.tmp" "$a/docs/adr/0023-fixture-decision.md"
if "$root/scripts/check-adr.sh" "$a/docs/adr" "$a/.okf/decisions" "$a/.okf/index.md" 2>/dev/null; then echo 'ADR with a stale mirror was accepted' >&2; exit 1; fi
ADR_ROOT="$a" "$root/scripts/adr.sh" sync 0023 >/dev/null
"$root/scripts/check-adr.sh" "$a/docs/adr" "$a/.okf/decisions" "$a/.okf/index.md"
echo 'Guardrail checker fixtures passed.'
