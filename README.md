# JayPee Solutions Catalog Suite for WooCommerce

JayPee Solutions Catalog Suite for WooCommerce is a WordPress plugin.

Scaffolded from [jaypee-solutions/wp-plugin-template](https://github.com/jaypee-solutions/wp-plugin-template).
Scaffolding changes (CI, scripts, hooks, lint/test config) arrive as pull requests
from the `template-update.yml` workflow, which runs `copier update` — edit them in
the template, not here.

## Requirements

- PHP 8.0+
- WordPress 6.4+
- WooCommerce 8.0+
- Composer, Node 22.13+ (wp-env 12), and Docker or Podman (for `wp-env`)

## Development

```bash
composer install
npm install

composer lint          # PHPCS (WordPress Coding Standards)
composer lint:fix      # PHPCBF
composer test          # PHPUnit

npm run env:start      # wp-env at http://localhost:8888 (admin / password)
npm run test:e2e:run   # seed + Playwright
npm run env:stop
```

`.husky/pre-commit` runs `composer lint`, `.husky/pre-push` runs `composer test`.

## Release

1. Bump the version in **both** places in `jp-catalog-suite.php` (the
   `Version:` header and the `VERSION` constant) — `scripts/check-version.sh`
   enforces that they agree — and in `readme.txt` (`Stable tag`).
2. Add the matching `## [X.Y.Z]` section to `CHANGELOG.md`.
3. Tag and push:

   ```bash
   git tag vX.Y.Z && git push --tags
   ```

`.github/workflows/release.yml` lints, tests, builds `build/jp-catalog-suite-vX.Y.Z.zip`
and publishes it as a GitHub release asset. `scripts/build-release.sh` installs
the runtime dependencies with `--no-dev` and prefixes them with `php-scoper`, so
this plugin cannot collide with another JayPee plugin bundling a different
version of the same library on the same WordPress install.

## Updates

Sites pull updates from GitHub through the Plugin Update Checker. A private
repository needs a read-only token, pasted under
**Settings → JayPee Solutions Catalog Suite for WooCommerce Updates**; the "Test token" button there says
exactly what GitHub answered when a check fails.
