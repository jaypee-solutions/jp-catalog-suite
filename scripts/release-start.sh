#!/usr/bin/env bash
#
# Opens the release PR: branch release/X.Y.Z from an up-to-date main, version bump,
# lint, commit, push, pull request.
#
# Usage: scripts/release-start.sh X.Y.Z [readme-line...]
#   The readme lines are passed to scripts/bump-version.sh.

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

die() {
	echo "release-start: $1" >&2
	exit 1
}

new="${1:-}"
[[ "$new" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || die "usage: release-start.sh X.Y.Z [readme-line...]"

[[ -z "$(git status --porcelain)" ]] || die "the working tree is not clean"
git fetch --quiet origin main
git checkout --quiet main
git merge --quiet --ff-only origin/main || die "main cannot fast-forward to origin/main"

git checkout --quiet -b "release/$new"
scripts/bump-version.sh "$@"
composer lint

git add -A
git commit --quiet -m "chore(release): $new"
git push --quiet -u origin "release/$new"

section="$(awk -v v="$new" '$0 ~ "^## \\[" v "\\]" {on=1; next} /^## \[/ {on=0} on' CHANGELOG.md | sed '/./,$!d')"
url="$(gh pr create --base main --head "release/$new" --title "chore(release): $new" --body-file - <<EOF
## Summary

Version bump to $new in \`jp-catalog-suite.php\`, \`package.json\`, \`readme.txt\` and \`CHANGELOG.md\`.

$section

## Evidence

- \`scripts/check-version.sh\` passes and \`composer lint\` is clean on this branch; the changelog has the \`## [$new]\` section the release workflow extracts.
- Tag \`v$new\` is created by \`just release-publish $new\` after this PR is merged and CI on the merge commit is green.

## Merge Danger

**Door:** one-way (tagging \`v$new\` after the merge publishes a release)

**Blast Radius:** plugin update for all sites tracking GitHub releases
EOF
)"
echo "release-start: $url"
