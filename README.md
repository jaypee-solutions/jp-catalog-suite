# JayPee Solutions Catalog Suite for WooCommerce

Quote mode, extended product tabs with access levels and protected files, and customer-entered lengths for WooCommerce.

## Requirements

- PHP 8.0+
- WordPress 6.4+
- WooCommerce 8.0+
- Composer, Node 22.13+ (wp-env 12), and Docker or Podman

## Configuration

Open **WooCommerce → Settings → Catalog Suite**. General contains a toggle for each installed module and the optional deletion setting. WooCommerce stores the `jps_cs_module_*` and `jps_cs_uninstall_delete_data` options.


## Variable length

Enable the module under **WooCommerce → Settings → Catalog Suite**. Its settings define the default unit, minimum, maximum, and step; simple products can enable Variable length and override those values. Each submitted length is validated and displayed on its separate cart line and order item, while WooCommerce quantity counts pieces. Catalog buttons link to the product page as **Choose length**. The `jps_cs_length_config` filter customizes resolved length configuration. Variable products, Store API add-to-cart, and price-per-length-unit calculation are not supported in this phase; the price-per-unit setting is reserved and defaults off.
## Uninstall

Plugin options, the `jp_catalog_suite_github_token` update credential, and the `jps_cs_purge_download_log` scheduled hook are always removed. When the uninstall deletion option is enabled, Catalog Suite tabs, protected files and the download log are also removed. Orders are never deleted. Uninstall is single-site only; network-wide uninstall is not handled. Inquiries retain their status and become unavailable in order lists after plugin removal. Bulk-change inquiry statuses before deleting the plugin to preserve their visibility.

## Development

```bash
composer install
npm ci
composer lint
composer test
npm run env:start
npm run test:integration
npm run test:e2e:run -- --retries 0
npm run env:stop
```

## Release

Build the installable archive with `npm run build:release`. Update the version in `jp-catalog-suite.php` and `readme.txt`, add a changelog entry, then tag and push the release.
