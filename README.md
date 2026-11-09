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

### Germanized compatibility

When Germanized is active and Quote mode is on, its price and legal notices are removed from product, catalog, cart, checkout and order views, and its checkout legal checkboxes are removed except those returned by `jps_cs_germanized_kept_checkboxes` (defaults to `privacy`). The Send inquiry button label is preserved. Germanized-specific controls run only when both plugins are active. German Market is unsupported; integrations should use `jps_cs_inquiry_created` and `jps_cs_quote_mode_active` instead.

## Inquiry emails

Two price-free WooCommerce emails are registered for each new Inquiry: **New inquiry** to the configured recipients (defaults to the site administrator email) and **Inquiry confirmation** to the billing email. Edit or disable them under **WooCommerce → Settings → Emails**. The New inquiry recipient accepts comma-separated addresses. Both email formats list products, SKU, quantity, item details, the customer's message and customer details without prices. The admin message replies to the customer's billing email. The `jps_cs_inquiry_created` action fires when an Inquiry is created; email delivery follows WooCommerce's pending-to-Inquiry notification. The two email settings are removed on uninstall; no email content is retained by this module.

## Quotes

Shop managers set prices on an Inquiry with the regular order item editor (**Recalculate** included; editable while the status is **Inquiry** or **Quote sent**), optionally fill **Valid until** and **Message to the customer** in the **Quote** box, and choose the order action **Send quote to customer**. The Inquiry moves to **Quote sent** (`jps-quote`) and the customer receives the **Quote** email with prices, totals, date and message; repeat the action to send a revised quote. Prices are visible to the customer from then on. A logged-in customer opens the order in My Account, reviews the Quote and clicks **Accept quote** (until the valid-until day ends): the order becomes `on-hold` (stock is reduced, no online payment) and the **Quote accepted** email goes to the configured recipients. Guests reply to the email instead. No PDF is generated. `jps_cs_quote_accepted` fires after an acceptance. Quote order meta: `_jps_cs_quote_sent`, `_jps_cs_quote_valid_until`, `_jps_cs_quote_message`.

## Inquiry checkout fields

Configure Company, Phone, and Address as optional, required, or hidden, and enable or disable the message under the Quote mode settings in **WooCommerce → Settings → Catalog Suite**. Name and email remain required. Address visibility and required state follow WooCommerce country locale behavior; classic checkout removes hidden street, city, state, and postcode fields while keeping Country available. Blocks cannot hide Country, which remains preselected to the store country, and Blocks order notes cannot be required; the Checkout block's own order-notes setting controls whether its message field appears. Options use `jps_cs_quote_field_company`, `jps_cs_quote_field_phone`, `jps_cs_quote_field_address`, and `jps_cs_quote_field_message`.
## Variable length

Enable **Variable length** under WooCommerce → Settings → Catalog Suite. Its settings define the default unit, minimum, maximum, and step; minimum must be positive and maximum at least the minimum. Simple and variable products can enable Variable length and override these values. When **Price per length unit** is enabled in the Variable length settings, each eligible product can opt in to per-length pricing; the unit price is multiplied by the entered length for each piece, then WooCommerce applies quantity. The `jps_cs_length_item_price` filter receives the calculated price, entered length, and cart item. Product prices show the length unit, while Quote mode continues to hide prices. On variable products, each variation may override minimum, maximum, step, and default length; blank overrides inherit the parent configuration. The input limits follow the selected variation, and server-side validation uses that variation's configuration. Each submitted length is validated and displayed on its separate cart line and order item, while WooCommerce quantity counts pieces. Catalog buttons link to the product page as **Choose length**. The `jps_cs_length_config` filter customizes resolved length configuration. For compatibility with current callers, `config()` returns an array including `enabled` and `price_per_unit`; its default falls back to the resolved minimum. Store API add-to-cart captures `jps_cs_length` from the request (or `extensions.jp-catalog-suite.jps_cs_length`), validates it, and keeps different lengths on separate cart lines. AJAX add-to-cart is disabled for Variable length products so the field cannot be dropped; use the Add to Cart form block for length products if the add-to-cart-with-options block does not submit the field. Price-per-length calculation follows the product's setting; Quote mode hides customer-facing prices.
## Product tabs

Enable **Product tabs** under WooCommerce → Settings → Catalog Suite. Tabs are managed where they apply; each tab has exactly one owner. Shop-wide tabs are listed under WooCommerce → Settings → Catalog Suite → Product tabs, category tabs on the product category edit screen (they also show on products in subcategories), and product tabs on the product's **Tabs** data panel. Add, Edit and Replace open the block editor in a dialog. A product tab can replace one applicable global tab (it starts from a copy of it); an empty priority inherits that tab's priority, otherwise priority defaults to 50. The **Tabs** panel also hides applicable global tabs per product. Tabs are deleted with their product or category. On upgrade, tabs that listed several categories or products are split once into one copy per owner. Hooks `jps_cs_tab_content` and `jps_cs_product_tabs` let extensions adjust rendered content and the product tab list. REST access is limited to users with `edit_posts`; settings and matching use canonical IDs for WPML/Polylang translations. Product-tab replacement uses `_jps_cs_replaces`, and hidden tab IDs are saved canonically in `_jps_cs_hidden_tabs`. With uninstall deletion enabled, tabs and hidden-tab product data are deleted along with the other Catalog Suite data. Product-specific tabs cannot replace other product tabs.

The same **Product tabs** settings section configures the Description, Additional information and Reviews Core tabs shop-wide: set a replacement title, priority, or hide a tab. Empty titles and priorities preserve WooCommerce's translated defaults and order. Core tabs cannot be overridden per product or replaced by Product tabs. The title options `jps_cs_tabs_core_description_title`, `jps_cs_tabs_core_additional_information_title`, and `jps_cs_tabs_core_reviews_title` are declared as WPML admin texts.

The **Access** block divides a tab into Protected content (**Only with access**) and Fallback content (**Only without access**); the toggle controls whether a hidden protected block displays a notice. The `jps_cs_can_access_protected` filter receives the default access result, current user ID, and context (`product_id` for block rendering; `attachment_id` for file delivery). Its default grants access to logged-in users. The `jps_cs_protected_notice` setting supports `{login}` and `{register}` placeholders; the `jps_cs_protected_notice_html` filter receives rendered HTML and context. Its output is sanitized with `wp_kses_post`. Login and registration return to the current product when redirect validation succeeds. Logged-in pages are sent no-cache headers and `DONOTCACHEPAGE`, because they can contain a different tab variant. Protected-file handling, Access/File list block registration, and Download-log privacy/retention hooks remain active when Product tabs is disabled; recording and the log admin page remain module-specific. Configure Varnish to pass requests with WordPress authentication cookies; otherwise a reverse proxy may cache one audience's tab variant:

```vcl
if (req.http.Cookie ~ "wordpress_logged_in_" || req.http.Cookie ~ "woocommerce_items_in_cart" || req.http.Cookie ~ "wp_woocommerce_session_") {
    return (pass);
}
```

Registration links use WooCommerce My Account registration when enabled, otherwise WordPress registration when open; no registration link is rendered when neither is available.

The **File list** block lets editors select multiple Media Library files and previews the rendered list in the editor. Existing files are listed with their title, uppercase extension and size. Protected files link through `?jps_cs_file=<attachment-id>` only for visitors with access; other visitors see an unlinked “(login required)” label. On a product page, the link also includes `product=<id>` for the download log. Missing files are omitted.

## Protected files

Configure allowed MIME types, automatically protected extensions, and delivery method under **WooCommerce → Settings → Catalog Suite → Product tabs**. STEP and DXF uploads matching the configured extensions are stored in `wp-content/uploads/jps-cs-protected/`, denied by Apache via `.htaccess`, and served only through `?jps_cs_file=<attachment-id>`. A protected attachment URL never exposes its storage path. Non-image protected attachments do not generate previews. Existing PDFs can be protected individually in the media attachment editor; protected attachments can also be returned to public storage there. The `jps_cs_can_access_protected` filter receives the user ID and context containing `attachment_id` for file checks; access is based only on the user and attachment. The untrusted `product` parameter is passed only to `jps_cs_before_file_delivery` for observers. `jps_cs_file_delivery_method` can choose `auto`, `xsendfile`, or `php`.
The optional **Download log** is configured under **WooCommerce → Settings → Catalog Suite → Product tabs**. Logging is off by default; when enabled, each authorized protected-file delivery records the user, file, optional untrusted product identifier (zero when absent), and UTC timestamp, without an IP address. The **WooCommerce → Download log** page lists the latest 200 entries for shop managers. Retention defaults to 365 days; set it to 0 to keep entries until the account or entries are erased. WordPress personal-data export and erasure tools are supported, and deleting a user removes their entries. Daily retention cleanup runs via `jps_cs_purge_download_log`; deactivation clears its schedule.

PHP delivery supports files up to 256 MB and does not support HTTP Range requests. Configure PHP upload limits to at least 256 MB and ensure Traefik's request-body limit is no lower. For X-Sendfile, enable Apache `mod_xsendfile` and put these directives in the virtual-host/server configuration, replacing the path with the absolute protected uploads directory:

```apache
XSendFile On
XSendFilePath /absolute/path/to/wordpress/wp-content/uploads/jps-cs-protected
```

For nginx, deny direct access to the protected uploads path:

```nginx
location ^~ /wp-content/uploads/jps-cs-protected/ {
    deny all;
}
```

The `.htaccess` deny rule does not protect nginx. Activation creates the directory and deny files. When uninstall deletion is enabled, protected attachments and the directory are removed.
## Uninstall

Plugin options, the `jp_catalog_suite_github_token` update credential, and the `jps_cs_purge_download_log` scheduled hook are always removed. When the uninstall deletion option is enabled, Catalog Suite tabs, protected files, the download log, variable-length product metadata, `_jps_cs_hidden_tabs`, and `_jps_cs_replaces` are also removed. Orders are never deleted. Uninstall is single-site only; network-wide uninstall is not handled. Inquiries retain their status and become unavailable in order lists after plugin removal. Bulk-change inquiry statuses before deleting the plugin to preserve their visibility.

## Development

```bash
composer install
npm ci
composer lint
composer test
composer hooks:check
npm run env:start
npm run test:integration
npm run test:e2e:run -- --retries 0
npm run env:stop
```


Linked git worktrees get their own deterministic wp-env ports (8900–8999); the main
checkout keeps 8888/8889. `WP_ENV_PORT`, `WP_ENV_TESTS_PORT`, `WP_ENV_URL`,
`WP_ENV_TESTS_URL` and `WP_ENV_HOME` override them. wp-env, integration and E2E commands
queue per plugin (`E2E_LOCK_DIR` moves the queue, `E2E_LOCK_DISABLE=1` turns it off);
`npm run test:e2e:ui` leaves the queue once the site is ready. If a site that does not
serve this checkout answers on the port, the E2E runner stops instead of testing it.
Seed shared site data in `tests/seed.php` idempotently: stable slugs or SKUs, create only
when absent. Specs import `test`/`expect` from `./support/fixtures`, `wpCli` from
`./support/wp-cli` and `withOptions` from `./support/options`, which restores every
changed option even when a test fails.

`composer lint` also validates `languages/*.po` with `msgfmt -c` and checks that every
`add_action`/`add_filter` name is fired by WordPress or WooCommerce, starts with
`jp_catalog_suite_` or `jps_cs_` (the plugin's hook prefix, allowlisted), or matches a pattern in `scripts/hook-allowlist.txt`, where
third-party hooks go. The hook check reads the source from the wp-env cache and skips
until `npm run env:start` has downloaded it; CI fetches it.

`npm install` runs Husky, which points Git at `.husky/`; `composer hooks:check` verifies
that. `.husky/pre-commit` runs `composer lint`; `.husky/pre-push` runs `composer test`.

## Release

Build the installable archive with `npm run build:release`. Update the version in `jp-catalog-suite.php` and `readme.txt`, add a changelog entry, then tag and push the release.

## Installation and configuration

Install the release ZIP through **Plugins → Add New → Upload Plugin**, then activate it. WooCommerce is required. For private GitHub release updates, provide a read-only personal access token for the private repository as `jp_catalog_suite_github_token`; store it in a secret manager or environment-backed configuration, never publish it or commit it.

Open **WooCommerce → Settings → Catalog Suite**. Enable the modules needed in General, then configure each module's settings section. Product-specific controls appear in the product editor. Save settings before testing the storefront.

## Hosting and privacy

Protected-file hosting requirements, including upload limits, Traefik body limits, Apache X-Sendfile, nginx denial, and Varnish pass rules, are documented in the [hosting runbook](.okf/references/hosting-requirements.md). PHP delivery supports files up to 256 MB and does not support HTTP Range requests. Configure production proxy and web-server rules before enabling protected files.

Self-registration is governed by WordPress/WooCommerce account settings. Sites that expose registration should consider an ALTCHA anti-spam integration appropriate to their deployment. The plugin does not include a CAPTCHA service.

The Download log is opt-in and stores user, file, optional product identifier and UTC timestamp; it does not record IP addresses. Personal-data export/erasure and retention cleanup are supported. See **Uninstall** for data-retention behavior.

## Multilingual sites

TranslatePress needs no plugin-specific configuration; translate the frontend strings normally. WPML admin-text configuration is supplied in `wpml-config.xml`. Polylang can translate content and standard strings through its usual workflow; global tabs are WordPress content and should be translated/assigned as appropriate for the site's language setup. Always test both Classic and Blocks checkout in each published language.

## Hook reference

All hook names use the `jps_cs_` prefix. Callback parameters below follow the order passed by the plugin.

| Hook | Signature | Purpose |
|---|---|---|
| `jps_cs_quote_mode_active` (filter) | `(bool $active): bool` | Enable or disable quote-mode behavior. |
| `jps_cs_quote_mode_applies_to_product` (filter) | `(bool $applies, WC_Product $product): bool` | Control quote mode for a product. |
| `jps_cs_quote_labels` (filter) | `(array $labels): array` | Customize the complete customer-facing label map. |
| `jps_cs_inquiry_created` (action) | `(WC_Order $order): void` | Runs when a new Inquiry is created. |
| `jps_cs_quote_accepted` (action) | `(WC_Order $order): void` | Runs after a customer accepts a Quote and the order moved to `on-hold`. |
| `jps_cs_germanized_kept_checkboxes` (filter) | `(array $ids): array` | Select Germanized legal checkbox IDs retained in Quote mode. |
| `jps_cs_tab_content` (filter) | `(string $content, WP_Post $tab, WC_Product $product): string` | Adjust rendered global tab content. |
| `jps_cs_product_tabs` (filter) | `(array $tabs, WC_Product $product): array` | Adjust the product tab list. |
| `jps_cs_can_access_protected` (filter) | `(bool $allowed, int $user_id, array $context): bool` | Decide access; context contains `product_id` for blocks or `attachment_id` for files. |
| `jps_cs_protected_notice_html` (filter) | `(string $html, array $context): string` | Customize the Access block notice markup. |
| `jps_cs_before_file_delivery` (action) | `(int $attachment_id, int $user_id, int $product_id): void` | Runs immediately before authorized file delivery; download logging uses this action. |
| `jps_cs_file_delivery_method` (filter) | `(string $method, int $attachment_id): string` | Select `php` or `xsendfile` delivery. |
| `jps_cs_purge_download_log` (action) | `(): void` | Runs the scheduled retention cleanup for the Download log. |
| `jps_cs_length_config` (filter) | `(array $config, WC_Product $product): array` | Adjust resolved unit and length bounds/defaults. |
| `jps_cs_length_item_price` (filter) | `(float $price, float $length, array $item): float` | Adjust calculated length-based cart-item price. |

### Known limitations

- Protected-file PHP delivery has a 256 MB supported upload size and no HTTP Range support; nginx requires an explicit deny rule.
- Guest access is the default for protected content only when access rules are customized; the default Access policy grants logged-in users.
- Germanized integration is supported; German Market is not.
- Blocks cannot hide the Country checkout field, and Blocks order notes cannot be made required.
- Inquiry status is registered by the plugin; after uninstall, existing inquiries retain their status but are unavailable in order lists until the status is registered again.
- Length-specific behavior is documented in the module sections above; Store API and variation support depend on the enabled product configuration.
