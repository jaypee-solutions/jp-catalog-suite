#!/bin/sh
set -eu
adr_dir=${1:-docs/adr}; decisions_dir=${2:-.okf/decisions}; index=${3:-.okf/index.md}
fail=0
seen=' '
for file in "$adr_dir"/[0-9][0-9][0-9][0-9]-*.md; do
    [ -f "$file" ] || continue
    name=${file##*/}; number=${name%%-*}
    case "$seen" in *" $number "*) echo "$file: duplicate ADR number $number" >&2; fail=1;; esac
    seen="$seen$number "
    [ -f "$decisions_dir/$name" ] || { echo "$file: missing mirror $decisions_dir/$name" >&2; fail=1; }
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
