# Build conventions

- Register modules in `Plugin::MODULES` order and add matching `require_once` lines in `jp-catalog-suite.php`.
- Add each module's `pre_option_jps_cs_module_<id>` filter in `tests/integration/bootstrap.php`.
- Append idempotent fixture blocks to `tests/seed.php`, headed `// Ticket #<N>: <title> fixtures.`, keyed by title or SKU.
- README has one `##` section per module and a shared Hook reference.
- Give every ADR a same-number OKF mirror. Check `scripts/check-adr.sh` for the next free number at commit time and re-check after merging a tip.
- Update translations with `npm run i18n:pot`; add formal German (Sie) strings in `de_DE.po`, copy it byte-identically to `de_DE_formal.po`, then run `npm run i18n:json`. Resolve `.pot` conflicts using the integration side and regenerate; deduplicate `.po` with `msguniq --use-first --no-wrap`.
- Clear the Germanized first-run wizard transient in seed setup.
- E2E specs isolate their fixtures, restore changed state, and assert only objects they created; queue E2E runs when worktrees share constrained environments.
- Isolate worktree environments with `WP_ENV_PORT`, `WP_ENV_TESTS_PORT`, `WP_ENV_URL`, and `WP_ENV_TESTS_URL`.
- Run `composer install` after merges.
