# Implementer brief

The brief for an agent that implements one slice of work in this repository. Hand the agent this file together with the slice's requirements (target files, behaviour, acceptance). Everything below holds for every slice.

## Before you edit

- Read `AGENTS.md`, `CODING_STANDARDS.md`, `docs/agents/testing.md` and, before touching WordPress core APIs, `docs/agents/wordpress-facts.md`.
- Reuse the patterns already in `includes/`. Namespace `JayPee\CatalogSuite`, hook, option and transient prefix `jp_catalog_suite_`, text domain `jp-catalog-suite`. Do not add a second convention, and add no Composer or npm package unless the slice says so.
- Files the template owns (`scripts/`, `.github/`, `phpcs.xml.dist`, `.husky/`, the stamped docs) change upstream in `wp-plugin-template`, not here.

## Worktrees

- Work only in the worktree you were given. Use absolute paths for every read and edit, and that worktree as `cwd` for every shell command. After your first edit, run `git status --short` there and check it lists only your files.
- Never edit another checkout of this plugin or another plugin. Do not push, tag or open a pull request unless the slice says so.
- wp-env ports derive from the checkout (see `docs/agents/testing.md`); never hard-code 8888 or 8889.

## Conventions

- Conventional Commits. Commit after each green step, not at the end. Name the step in the subject.
- Code follows PHPCS (WordPress standard, 4 spaces, short arrays). Comments only for non-obvious behaviour. Delete code your change makes obsolete; no shims, no TODOs.
- Never fake core behaviour. Integration tests drive WordPress through hooks, REST dispatch, `do_blocks()` and admin-post, and never short-circuit mail or hooks; only HTTP leaving the machine may be stubbed.
- A decision a future reader would question gets an ADR in `docs/adr/`; a user-visible change gets a `CHANGELOG.md` entry under `## [Unreleased]`.

## Verification order

Run every command from the worktree root; stop at the first failure and fix its cause, never weaken a check.

1. `composer lint`
2. `composer test`
3. `npm run env:start`, then `npm run test:integration`, then `npm run test:e2e:run`, and `npm run env:stop` when you are done
4. For a change that affects what ships: `bash scripts/build-release.sh` and `bash scripts/verify-release-zip.sh build/jp-catalog-suite-v*.zip`

Skip a step only when nothing in your change can affect it, and say which one you skipped.

## Done criterion

A step is green only when the command exits 0 and its summary line is there:

- PHPUnit (unit and integration): `OK (N tests, M assertions)`.
- Playwright: `N passed` and no `failed`/`flaky` line.
- `composer lint`, the build and the ZIP check: exit 0 with no error output.

Report each command with its exit code and the summary line verbatim. A claim without that output is unverified; say so instead of rounding it up.

## Report

End with: the commits (`git log --oneline` of your range), the verification table above, deviations from the slice with the reason, and anything the maintainer has to do by hand.
