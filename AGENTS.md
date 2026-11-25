## Testing

Before starting wp-env or running integration/E2E tests, read `docs/agents/testing.md`.

## Automation

`just` lists the recipes for lint, tests, E2E, wp-env, releases (`release` skill) and ADR scaffolding; use them instead of retyping the underlying commands.

## Deployed dev site

For a report from the dev site (a cache serving the wrong page, a download header), read `docs/agents/dev-site.md`.

## Agent skills

### Issue tracker

GitHub Issues on `jaypee-solutions/jp-catalog-suite`, managed with `gh`. See `docs/agents/issue-tracker.md`.

### Triage labels

Default five roles; each label string equals its role name (`needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`). See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: `GLOSSARY.md` and `docs/adr/` at the repo root. See `docs/agents/domain.md`.

## Reviewing

Reviewing: read `CODING_STANDARDS.md`.

## Build conventions

See `docs/agents/conventions.md`.
