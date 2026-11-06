# JayPee Solutions Catalog Suite for WooCommerce

Quote mode, extended product tabs with access levels and protected files, and customer-entered lengths for WooCommerce.

## Requirements

- PHP 8.0+
- WordPress 6.4+
- WooCommerce 8.0+
- Composer, Node 22.13+ (wp-env 12), and Docker or Podman

## Configuration

Open **WooCommerce → Settings → Catalog Suite**. General contains a toggle for each installed module and the optional deletion setting. WooCommerce stores the `jps_cs_module_*` and `jps_cs_uninstall_delete_data` options.

## Quote mode

Enable **Quote mode** in the Catalog Suite settings to let customers submit inquiries without payment, shipping, taxes, or coupons. Price-less simple products and variations remain purchasable; WooCommerce stock and backorder rules are unchanged. Guest checkout follows WooCommerce's guest-checkout setting. Inquiry status, Analytics exclusion, and existing inquiry visibility remain active when Quote mode is off. Inquiries use the `jps-inquiry` status and `_jps_cs_inquiry` order metadata. The `jps_cs_quote_mode_active` and `jps_cs_quote_mode_applies_to_product` filters control activation and per-product applicability; `jps_cs_inquiry_created` fires after an Inquiry is created. Price hiding is outside this module's scope, so prices remain visible on product pages and in Store API data. Quote mode handles inquiry intake only.


## Inquiry emails

Two price-free WooCommerce emails are registered for each new Inquiry: **New inquiry** to the configured recipients (defaults to the site administrator email) and **Inquiry confirmation** to the billing email. Edit or disable them under **WooCommerce → Settings → Emails**. The New inquiry recipient accepts comma-separated addresses. Both email formats list products, SKU, quantity, item details, the customer's message and customer details without prices. The admin message replies to the customer's billing email. The `jps_cs_inquiry_created` action fires when an Inquiry is created; email delivery follows WooCommerce's pending-to-Inquiry notification. The two email settings are removed on uninstall; no email content is retained by this module.
## Variable length

Enable **Variable length** under WooCommerce → Settings → Catalog Suite. Its settings define the default unit, minimum, maximum, and step; simple and variable products can enable Variable length and override these values. On variable products, each variation may override minimum, maximum, step, and default length; blank overrides inherit the parent configuration. The input limits follow the selected variation, and server-side validation uses that variation's configuration. Each submitted length is validated and displayed on its separate cart line and order item, while WooCommerce quantity counts pieces. Catalog buttons link to the product page as **Choose length**. The `jps_cs_length_config` filter customizes resolved length configuration. Store API add-to-cart and pricing by length unit are not supported.
## Product tabs

Enable **Product tabs** under WooCommerce → Settings → Catalog Suite. Editors create Global tabs under Products → Product tabs, write content in the block editor, and choose whether each tab applies to all products or selected categories (including descendants). Priority orders matching tabs; the default is 50. Empty text-only tabs are omitted. Hooks `jps_cs_tab_content` and `jps_cs_product_tabs` let extensions adjust rendered content and the product tab list. Tab posts are available through REST only to authorized editors; their settings are copied by WPML. With uninstall deletion enabled, tabs are deleted along with the other Catalog Suite data.
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
