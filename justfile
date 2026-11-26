# Project automation. `just` lists the recipes; every recipe is a thin wrapper over
# composer, npm or a script in scripts/, so the same steps run in CI and by hand.

set shell := ["bash", "-euo", "pipefail", "-c"]
set positional-arguments

default:
    @just --list

# Lint and static checks: phpcs, test seams, docs, i18n, hook names, version places
lint:
    composer lint

# PHPUnit plus the guardrail fixtures
test:
    composer test

# WordPress integration tests (needs wp-env: just env-start)
integration:
    npm run test:integration

# Playwright E2E; extra arguments go to the runner: just e2e tests/e2e/length.spec.ts --retries 0
e2e *args:
    npm run test:e2e:run -- "$@"

# Start wp-env (Podman machine, hosts shim, DB checks); warns when the cached WooCommerce is older than latest-stable
env-start:
    npm run env:start

# Re-download the latest-stable plugins (for example WooCommerce), the versions CI installs
env-update:
    scripts/wp-env-ensure.sh update

env-stop:
    npm run env:stop

# Check that header, VERSION, package.json, readme.txt and CHANGELOG.md agree
version-check *tag:
    scripts/check-version.sh "$@"

# Bump every version place and turn Unreleased into a release section: just bump 0.5.7 ["readme line" ...]
bump version *readme_lines:
    scripts/bump-version.sh "$@"

# Open the release PR from main: just release-start 0.5.7 ["readme line" ...]
release-start version *readme_lines:
    scripts/release-start.sh "$@"

# After the release PR is merged: wait for CI on main, tag, wait for the release workflow
release-publish version:
    scripts/release-publish.sh "$@"

# New ADR with its OKF mirror, index link and log entry: just adr-new "Title" "One-line description" "tag-one,tag-two"
adr-new title description tags:
    scripts/adr.sh new "$@"

# Rewrite an ADR's OKF mirror body after editing docs/adr: just adr-sync 0031
adr-sync number:
    scripts/adr.sh sync "$@"
# Cache and encoding headers of a page, as a guest and optionally with a login cookie: just headers URL ["name=value; ..."]
headers url cookie="":
    scripts/headers.sh "$@"
