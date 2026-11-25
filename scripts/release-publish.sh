#!/usr/bin/env bash
#
# Publishes a release after its PR is merged: waits for CI on the merge commit of
# main, tags vX.Y.Z, waits for the release workflow and prints the release URL.
# Nothing is tagged unless CI on that exact commit succeeded.
#
# Usage: scripts/release-publish.sh X.Y.Z

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

die() {
	echo "release-publish: $1" >&2
	exit 1
}

version="${1:-}"
[[ "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || die "usage: release-publish.sh X.Y.Z"
tag="v$version"

git fetch --quiet origin main --tags
git checkout --quiet main
git merge --quiet --ff-only origin/main || die "main cannot fast-forward to origin/main"
scripts/check-version.sh "$tag" >/dev/null || die "main does not carry version $version; merge the release PR first"
git rev-parse --quiet --verify "refs/tags/$tag" >/dev/null && die "tag $tag already exists"

sha="$(git rev-parse HEAD)"
echo "release-publish: waiting for CI on main $sha (about 10 minutes); started $(date -u +%H:%M) UTC / $(TZ=Europe/Berlin date +%H:%M) CET"

run=""
for _ in $(seq 1 60); do
	run="$(gh run list --branch main --workflow CI --json databaseId,headSha -q ".[] | select(.headSha==\"$sha\") | .databaseId" | head -n1 || true)"
	[[ -n "$run" ]] && break
	sleep 10
done
[[ -n "$run" ]] || die "no CI run found for $sha"

gh run watch "$run" >/dev/null 2>&1 || true
conclusion="$(gh run view "$run" --json conclusion -q .conclusion)"
[[ "$conclusion" == "success" ]] || die "CI run $run ended '$conclusion'; not tagging"

git fetch --quiet origin main
[[ "$(git rev-parse origin/main)" == "$sha" ]] || die "main moved past $sha while CI ran; check it and run this again"

git tag -a "$tag" "$sha" -m "$version"
git push --quiet origin "$tag"

release_run=""
for _ in $(seq 1 30); do
	release_run="$(gh run list --workflow Release --json databaseId,headBranch -q ".[] | select(.headBranch==\"$tag\") | .databaseId" | head -n1 || true)"
	[[ -n "$release_run" ]] && break
	sleep 5
done
[[ -n "$release_run" ]] || die "no Release workflow run found for $tag"

gh run watch "$release_run" >/dev/null 2>&1 || true
conclusion="$(gh run view "$release_run" --json conclusion -q .conclusion)"
[[ "$conclusion" == "success" ]] || die "Release workflow run $release_run ended '$conclusion'"

gh release view "$tag" --json url,assets -q '"release-publish: " + .url + " " + ([.assets[].name] | join(","))'
