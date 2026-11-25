---
name: release
description: Use for a plugin release: bump the version, open the release PR, tag after the merge, publish. Also for "new release", "bugfix release", "tag vX.Y.Z" or a version bump.
---

# Release

A release is two recipes with the user's merge between them. Never edit the version places or tag by hand; the recipes keep them in step and refuse to tag unless CI on the merge commit succeeded.

## 1. Choose the version

Read the `## [Unreleased]` section of `CHANGELOG.md`. Only `### Fixed` bullets: patch (`0.5.6` to `0.5.7`). Any `### Added` or `### Changed`: minor. A bullet only about CI or tooling ships no plugin code: leave it out of the readme lines.

Done when the changelog Unreleased section holds an entry for every user-visible change since the last tag (`git log vX.Y.Z..main --oneline`); add the missing entries on a branch first.

## 2. Open the release PR

`just release-start X.Y.Z` (optionally followed by quoted readme.txt lines; without them the script derives one per Unreleased bullet).

Done when the script prints the PR URL. Give the URL to the user and stop: the merge is theirs.

## 3. Publish after the merge

When the user says the PR is merged: `just release-publish X.Y.Z`. It waits for CI on the merge commit (about 10 minutes), tags, waits for the release workflow and prints the release URL and ZIP name.

Before the wait, post the ETA in local time (CET/CEST) and UTC, and the check you are waiting on, with `out://`. Run the recipe with `async` and no timeout so the wait does not block the session.

Done when the script prints `release-publish: <url> jp-catalog-suite-vX.Y.Z.zip`. A failed CI run or Release workflow ends the recipe without a tag or with a tag already pushed; read its message, report it, and do not retry the tag.
