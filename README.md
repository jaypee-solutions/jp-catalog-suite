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

Enable **Quote mode** in the Catalog Suite settings to let customers submit inquiries without payment, shipping, taxes, or coupons. Price-less simple products and variations remain purchasable; WooCommerce stock and backorder rules are unchanged. Guest checkout follows WooCommerce's guest-checkout setting. Inquiry status, Analytics exclusion, and existing inquiry visibility remain active when Quote mode is off. Inquiries use the `jps-inquiry` status and `_jps_cs_inquiry` order metadata. The `jps_cs_quote_mode_active` and `jps_cs_quote_mode_applies_to_product` filters control activation and per-product applicability; `jps_cs_inquiry_created` fires after an Inquiry is created. In quote mode customer-facing catalog, product, basket, checkout and confirmation views hide prices; My Account labels Orders as Inquiries and omits its total column. Existing inquiries stay price-free in account views and WooCommerce emails even when Quote mode is off, while admin order screens retain prices. Prices are hidden, not confidential: WooCommerce Store API product and cart JSON still exposes raw prices for priced products.

Quote mode provides the translatable default labels **Inquiry basket**, **Add to inquiry**, **Continue to inquiry**, and **Send inquiry**. Set `jps_cs_quote_cart_label`, `jps_cs_quote_add_to_cart_label`, `jps_cs_quote_proceed_label`, and `jps_cs_quote_place_order_label` to override them; an empty value uses the translated default. The `jps_cs_quote_labels` filter adjusts the complete label map. WooCommerce cart and checkout page titles are WordPress page titles; rename those pages separately.


## Inquiry emails

Two price-free WooCommerce emails are registered for each new Inquiry: **New inquiry** to the configured recipients (defaults to the site administrator email) and **Inquiry confirmation** to the billing email. Edit or disable them under **WooCommerce → Settings → Emails**. The New inquiry recipient accepts comma-separated addresses. Both email formats list products, SKU, quantity, item details, the customer's message and customer details without prices. The admin message replies to the customer's billing email. The `jps_cs_inquiry_created` action fires when an Inquiry is created; email delivery follows WooCommerce's pending-to-Inquiry notification. The two email settings are removed on uninstall; no email content is retained by this module.
## Variable length

Enable **Variable length** under WooCommerce → Settings → Catalog Suite. Its settings define the default unit, minimum, maximum, and step; simple and variable products can enable Variable length and override these values. On variable products, each variation may override minimum, maximum, step, and default length; blank overrides inherit the parent configuration. The input limits follow the selected variation, and server-side validation uses that variation's configuration. Each submitted length is validated and displayed on its separate cart line and order item, while WooCommerce quantity counts pieces. Catalog buttons link to the product page as **Choose length**. The `jps_cs_length_config` filter customizes resolved length configuration. Store API add-to-cart and pricing by length unit are not supported.
## Product tabs

Enable **Product tabs** under WooCommerce → Settings → Catalog Suite. Editors create Global tabs under Products → Product tabs, write content in the block editor, and choose whether each tab applies to all products, selected categories (including descendants), or selected products. A Product tab can replace one applicable global tab; an empty priority inherits that tab's priority, otherwise priority defaults to 50. On each product's **Tabs** data panel, hide applicable global tabs or follow links to its product-specific tabs. The creation link preselects Products scope and that product. Hooks `jps_cs_tab_content` and `jps_cs_product_tabs` let extensions adjust rendered content and the product tab list. Tab posts are available through REST only to authorized editors; settings and matching use canonical IDs for WPML/Polylang translations. With uninstall deletion enabled, tabs and `_jps_cs_hidden_tabs` product data are deleted along with the other Catalog Suite data. Product-specific tabs cannot replace other product tabs.

The same **Product tabs** settings section configures the Description, Additional information and Reviews Core tabs shop-wide: set a replacement title, priority, or hide a tab. Empty titles and priorities preserve WooCommerce's translated defaults and order. Core tabs cannot be overridden per product or replaced by Product tabs. The title options `jps_cs_core_tab_description_title`, `jps_cs_core_tab_additional_information_title`, and `jps_cs_core_tab_reviews_title` are declared as WPML admin texts.

The **Access** block divides a tab into Protected content (**Only with access**) and Fallback content (**Only without access**); the toggle controls whether a hidden block displays a notice. The `jps_cs_can_access_protected` filter receives the default access result, current user object, and context containing the server-resolved product for block rendering or `attachment_id` for file delivery. Its default grants access to logged-in users. The `jps_cs_protected_notice` setting supports `{login}` and `{register}` placeholders; the `jps_cs_protected_notice_html` filter changes its output. The setting is registered as a WPML admin text. Login and registration return to the current product when redirect validation succeeds. Logged-in pages are sent no-cache headers and `DONOTCACHEPAGE`, because they can contain a different tab variant. Configure Varnish to pass requests with WordPress authentication cookies; otherwise a reverse proxy may cache one audience's tab variant:

```vcl
if (req.http.Cookie ~ "wordpress_logged_in_") {
    return (pass);
}
```

Registration links use WooCommerce My Account registration when enabled, otherwise WordPress registration when open; no registration link is rendered when neither is available.

## Protected files

Configure allowed MIME types, automatically protected extensions, and delivery method under **WooCommerce → Settings → Catalog Suite → Product tabs**. STEP and DXF uploads matching the configured extensions are stored in `wp-content/uploads/jps-cs-protected/`, denied by Apache via `.htaccess`, and served only through `?jps_cs_file=<attachment-id>`. A protected attachment URL never exposes its storage path. Non-image protected attachments do not generate previews. Existing PDFs can be protected individually in the media attachment editor; protected attachments can also be returned to public storage there. The `jps_cs_can_access_protected` filter receives the user ID and context containing `attachment_id` for file checks; access is based only on the user and attachment. The untrusted `product` parameter is passed only to `jps_cs_before_file_delivery` for observers. `jps_cs_file_delivery_method` can choose `auto`, `xsendfile`, or `php`.

PHP delivery supports files up to 256 MB and does not support HTTP Range requests. Configure PHP upload limits to at least 256 MB and ensure Traefik's request-body limit is no lower. For X-Sendfile, enable and configure Apache `mod_xsendfile` to allow files beneath the protected upload directory. For nginx, deny direct access to `/wp-content/uploads/jps-cs-protected/` with `location ^~ /wp-content/uploads/jps-cs-protected/ { deny all; }`; the `.htaccess` rule does not protect nginx. Activation creates the directory and deny files. When uninstall deletion is enabled, protected attachments and the directory are removed.
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
