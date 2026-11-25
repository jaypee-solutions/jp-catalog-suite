#!/bin/sh
set -eu
adr_dir=${1:-docs/adr}; decisions_dir=${2:-.okf/decisions}; index=${3:-.okf/index.md}
fail=0
seen=' '
# ADRs before 0022 predate the byte-identical mirror convention.
identical_from=${ADR_MIRROR_FROM:-22}
for file in "$adr_dir"/[0-9][0-9][0-9][0-9]-*.md; do
    [ -f "$file" ] || continue
    name=${file##*/}; number=${name%%-*}
    case "$seen" in *" $number "*) echo "$file: duplicate ADR number $number" >&2; fail=1;; esac
    seen="$seen$number "
    if [ ! -f "$decisions_dir/$name" ]; then
        echo "$file: missing mirror $decisions_dir/$name" >&2; fail=1
    elif [ "$(expr "$number" + 0)" -ge "$identical_from" ]; then
        awk 'NR==1 && /^---$/ {front=1; next} front && /^---$/ {front=0; skip=1; next} front {next} skip && /^$/ {skip=0; next} {skip=0; print}' "$decisions_dir/$name" | cmp -s - "$file" || { echo "$file: mirror body differs from $decisions_dir/$name (run scripts/adr.sh sync $number)" >&2; fail=1; }
    fi
    ! grep -Eq '^TODO[[:space:]]*$' "$file" || { echo "$file: unfinished section (TODO)" >&2; fail=1; }
    for heading in Status Context Decision Consequences; do
        grep -Eq "^## $heading[[:space:]]*$" "$file" || { echo "$file: missing ## $heading" >&2; fail=1; }
    done
done
for file in "$adr_dir"/[0-9][0-9][0-9][0-9]-*.md; do
    [ -f "$file" ] || continue
    name=${file##*/}
    grep -Fq "decisions/$name" "$index" || { echo "$index: missing link to decisions/$name" >&2; fail=1; }
done
exit "$fail"
