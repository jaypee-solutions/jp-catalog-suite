# JPS Catalog Suite for WooCommerce — implementation plan

## Context

Build the generic, reusable WooCommerce extension "JayPee Solutions Catalog Suite for WooCommerce" in `/Users/alten/GitHub/jp-catalog-suite`. The repo is a fresh copier scaffold from `wp-plugin-template`: main file, `includes/class-updates.php` for Plugin Update Checker over GitHub releases, wp-env, PHPUnit unit and integration setup, Playwright, release build. Three toggleable modules go on top:
1. **Quote mode**: inquiry shop without prices, payment, shipping or taxes; own order status and emails.
2. **Product tabs**: global and product tabs, with access levels and protected CAD file delivery. The brief's module 3 lives inside this module.
3. **Variable length**: customer enters a length.

Deliverables: plugin structure, `.pot` and German translations, `wpml-config.xml`, `uninstall.php`, README covering install, config and hooks, CHANGELOG, a per-module test checklist, ADRs and OKF notes. First deployment target is dev.koeberle-schmid.com: block theme, Cart/Checkout blocks, Germanized, TranslatePress, Varnish planned. Everything specific to that customer is configuration. The sibling plugins `../jp-dynamic-categories` and `../wc-shipping` are NOT touched.

User decisions (already made):
- Guest inquiries follow WooCommerce's own guest-checkout setting. The plugin has no own switch.
- Customers see past inquiries through WC My Account → Orders, relabeled "Inquiries" and shown without prices.
- All tab content is edited in the **block editor**. Every tab is a post of a custom post type, product-specific tabs included.
- X-Sendfile is auto-detected; otherwise files are streamed by PHP.
- Protected files are ≤ 256 MB.
- No page cache runs yet; Varnish is planned.
- Variable length is an **option on existing simple and variable products**, not a new product type.
- **No "Inquiry" payment gateway.** In quote mode `needs_payment` is always false, whatever the total.
- **Design review 2026-11-06** (vocabulary in `GLOSSARY.md`):
  - The customer submits an **Inquiry**; "quote" names only the module. Code identifiers for the submitted object use `inquiry`.
  - The plugin covers inquiry intake only; Angebot and order happen outside WooCommerce.
  - Only the brief's labels are replaced; leftover WooCommerce "order" wording is accepted.
  - Prices in quote mode are hidden, not confidential.
  - Inquiry status, analytics exclusion and price-free inquiry views work regardless of the quote-mode toggle.
  - File access depends on user and file only; the `product` URL param is untrusted and feeds the log only.
  - PDFs are protected per file. The endpoint redirects files that are no longer protected to their public URL.
  - Only product tabs replace global tabs; core tabs are configured globally only.
  - Variable length: quantity counts pieces of the entered length.
  - WooCommerce stock and backorder rules apply to inquiries unchanged.
  - The download log gets user-deletion cleanup, a personal-data exporter and eraser, and privacy-policy text.
  - German Market stays unsupported.

## Fixed conventions (apply everywhere)

- **Identity.** Text domain = slug = `jp-catalog-suite`. WordPress.org requires domain == slug; the brief's "jps-catalog-suite" is treated as a typo. Prefix `jps_cs_` for options, meta, hooks and the cron hook; namespace `JayPee\CatalogSuite`.
  - Meta keys are underscore-prefixed (`_jps_cs_…`), except the visible order-item meta `jps_cs_length`.
  - Block namespace `jp-catalog-suite/…`.
- **Plugin header.** Change `Author:` to `JayPee Solutions` and `Description:` to "Quote mode, extended product tabs with access levels and protected files, and customer-entered lengths for WooCommerce."
- **Template-owned code.** `includes/class-updates.php` stays untouched and keeps its option `jp_catalog_suite_github_token`. Template-owned scripts are edited only where named below.
- **PHP style.** 8.0 compatible, `declare( strict_types = 1 );`, 4-space indent, short arrays, WPCS via `composer lint`. Files are `includes/class-<kebab>.php` (flat, sibling convention), explicitly `require_once`d from the main file — no PSR-4.
  - Classes are `final` with static `init()` methods, following `Updates`.
  - Inline comments only for non-obvious behavior.
- **JS/CSS.** Plain files with no build step.
  - Blocks use `block.json` with `"editorScript": "file:./index.js"`, plus a hand-written `index.asset.php` returning `['dependencies'=>[…],'version'=>VERSION-string]`.
  - Code uses the `wp.*` / `wc.*` globals and `wp.element.createElement`; no JSX.
- **Strings.** All strings are translatable with English source strings; German comes from `languages/jp-catalog-suite-de_DE.po`.
  - Formal "Sie" in German; `de_DE_formal.po` is an identical copy.
  - An empty text option means "use the translated default". Settings fields show the default as placeholder.
- **Security.**
  - Every admin form: nonce plus capability check (`manage_woocommerce` for settings, `edit_post` for metaboxes).
  - Sanitize on save (`absint`, `sanitize_text_field`, `wp_kses_post`, `sanitize_key`).
  - Escape on output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`).
- **Integration-test rule.** `scripts/check-test-seams.sh` (part of `composer lint`) forbids static calls into `JayPee\CatalogSuite` from integration tests. Drive behavior through `do_action`, `apply_filters`, REST, `wc_*` and core functions.
- **Records.**
  - `GLOSSARY.md` (repo root) defines the domain vocabulary; code, UI, docs and ADRs use its terms.
  - ADRs go in `docs/adr/NNNN-kebab.md` (sections Status/Context/Decision/Consequences, as in `../jp-permissions/docs/adr/0002-groups-in-single-option.md`).
  - Each ADR is mirrored as an OKF decision in `.okf/decisions/NNNN-kebab.md`. Frontmatter: `type: Decision`, `title`, `description`, `tags`, `timestamp`; then `# Overview` and `# Citations`, the latter citing the ADR path.
  - Maintain `.okf/index.md` (`okf_version: "0.1"`) and `.okf/log.md` in the format of `../wp-plugin-template/.okf/`.
  - Read `skill://okf` before the first OKF write; validate with `skill://okf-validate` at the end of each phase.
- **Commits.** One commit per phase. Done criterion for each phase:
  - `composer lint` and `composer test` exit 0.
  - `npm run test:integration` prints `OK (N tests, M assertions)` and exits 0.
  - The phase's Playwright specs pass via `npm run test:e2e:run`.

## Approach

### Phase 0 — Foundation (core, settings tab, i18n, packaging, test harness)

1. **Main file `jp-catalog-suite.php`.**
   - Update the header as above.
   - Next to the existing `require_once` of `class-updates.php` (file scope), `require_once` every plain `includes/class-*.php` of this plan. These classes only declare; hooks are added in `init()`, so the activation hook can use them.
   - **Lazy-required instead**, because they extend or implement WooCommerce types that don't exist at file scope:
     - `class-settings-page.php`: inside the `woocommerce_get_settings_pages` filter.
     - `class-email-*.php`: inside `woocommerce_email_classes`.
     - `class-quote-blocks-integration.php`: inside the blocks registration action.
   - Inside the existing `plugins_loaded` callback, after the WooCommerce check, call `Plugin::init()`.
   - Add `register_activation_hook( PLUGIN_FILE, [ Plugin::class, 'activate' ] )` and `register_deactivation_hook( PLUGIN_FILE, [ Plugin::class, 'deactivate' ] )`.
   - In the `before_woocommerce_init` callback, also declare `FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', PLUGIN_FILE, true )`.
   - Add `const PLUGIN_DIR = __DIR__ . '/';`. Asset URLs use `plugins_url( 'assets/…', PLUGIN_FILE )`; no URL constant, because `const` can't hold a function call.
2. **`includes/class-plugin.php` → `final class Plugin`.**
   - `const MODULES = [ 'quote' => Quote_Mode::class, 'tabs' => Product_Tabs::class, 'length' => Variable_Length::class ];`
     - Each phase adds its line when the class exists.
     - Phase 0 ships `MODULES = []` and the settings "General" section lists only the modules present.
   - `public static function init(): void`:
     - `add_filter( 'woocommerce_get_settings_pages', … )` requires `includes/class-settings-page.php` lazily (WC_Settings_Page only exists then) and appends `new Settings_Page()`.
     - For each module, if `self::is_module_active( $id )`, call `$class::init()`.
     - From Phase 1, `Inquiries::init()` runs first and unconditionally (Phase 1 step 6).
   - `public static function is_module_active( string $id ): bool` returns `'yes' === get_option( "jps_cs_module_{$id}", 'no' )`.
   - `public static function canonical_id( int $id, string $type ): int` returns `(int) apply_filters( 'wpml_object_id', $id, $type, true, apply_filters( 'wpml_default_language', null ) )`.
     - Without WPML or Polylang the filter is a no-op and returns `$id`. Polylang implements the WPML filter API.
     - Use it for EVERY stored-ID comparison: tab, product and term IDs.
   - `public static function activate(): void` calls `Protected_Files::ensure_directory()` and `Download_Log::install_table()`. Phase 2 adds both calls; until then the method body is empty and present.
   - `public static function deactivate(): void` calls `wp_clear_scheduled_hook( 'jps_cs_purge_download_log' )`.
3. **`includes/class-settings-page.php` → `final class Settings_Page extends \WC_Settings_Page`.**
   - `$this->id = 'jps_cs'`, `$this->label = __( 'Catalog Suite', 'jp-catalog-suite' )`.
   - Use the WC ≥ 5.5 API: `get_own_sections()` returns `[ '' => General, 'quote' => Quote mode, 'tabs' => Product tabs, 'length' => Variable length ]`, listing only modules in `Plugin::MODULES`.
   - Implement `get_settings_for_default_section()` and `get_settings_for_{id}_section()`, each delegating to `<ModuleClass>::settings(): array`.
   - General section fields (WC settings arrays, `type => checkbox`, default `no`):
     - `jps_cs_module_quote`, `jps_cs_module_tabs`, `jps_cs_module_length`
     - `jps_cs_uninstall_delete_data`, desc "Delete tabs, protected files and the download log when the plugin is deleted. Orders are never deleted."
   - WooCommerce saves the options itself; no own save code.
4. **`uninstall.php`** (plugin root):
   - Guard with `defined( 'WP_UNINSTALL_PLUGIN' ) || exit;`.
   - Read `$purge = 'yes' === get_option( 'jps_cs_uninstall_delete_data' )` FIRST.
   - Delete every option matching `jps\_cs\_%` (via `$wpdb->get_col` on `$wpdb->options` with `$wpdb->esc_like`, then `delete_option` each), plus `jp_catalog_suite_github_token`.
   - Clear the cron hook `jps_cs_purge_download_log`.
   - If `$purge`:
     - `wp_delete_post( $id, true )` for all `jps_cs_tab` posts, any status.
     - `wp_delete_attachment( $id, true )` for all attachments with meta `_jps_cs_protected = 1`.
     - Remove `wp_upload_dir()['basedir'] . '/jps-cs-protected'` recursively via `WP_Filesystem()` and `$wp_filesystem->rmdir( $dir, true )`.
     - `delete_post_meta_by_key()` for `_jps_cs_hidden_tabs`, `_jps_cs_length_enabled`, `_jps_cs_length_unit`, `_jps_cs_length_min`, `_jps_cs_length_max`, `_jps_cs_length_step`, `_jps_cs_length_default`, `_jps_cs_length_price_per_unit`.
     - `DROP TABLE IF EXISTS {$wpdb->prefix}jps_cs_download_log`.
   - Orders and order-item meta are never touched. Single-site only; network-wide uninstall is not handled — state this in the README.
   - Inquiries keep status `wc-jps-inquiry`, which is unregistered once the plugin is gone, so they vanish from order lists. Changing their status here would send WooCommerce status emails to customers; the README tells admins to bulk-change inquiry statuses before deleting the plugin.
5. **`wpml-config.xml`** (plugin root). Polylang reads the same file. It starts with `<wpml-config>` and later phases fill the `<custom-types>`, `<custom-fields>` and `<admin-texts>` sections listed per phase.
6. **Packaging.**
   - Edit `scripts/build-release.sh` line 45: the copy list becomes `README.md readme.txt LICENSE uninstall.php wpml-config.xml includes assets blocks templates languages`. Drop `admin`: unused.
   - Edit `phpcs.xml.dist`: add `<file>uninstall.php</file>` and `<file>templates</file>`.
   - Both are template-owned; note "upstream to wp-plugin-template" in the final report — don't edit the template repo.
7. **i18n tooling.** Add npm scripts:
   - `"i18n:pot": "WP_ENV_HOME=./.wp-env npx --no-install wp-env run cli --env-cwd=wp-content/plugins/$(basename \"$PWD\") wp i18n make-pot . languages/jp-catalog-suite.pot --domain=jp-catalog-suite --exclude=vendor,node_modules,tests,build,.wp-env"`
   - `"i18n:json": "WP_ENV_HOME=./.wp-env npx --no-install wp-env run cli --env-cwd=wp-content/plugins/$(basename \"$PWD\") wp i18n make-json languages --no-purge"`
   - Commit `languages/jp-catalog-suite.pot`, `-de_DE.po`, `-de_DE_formal.po` and the generated `*.json`. `.mo` files stay gitignored and are compiled by the build script (lines 51–66) and by seed.
8. **Integration harness with WooCommerce.** Edit `tests/integration/bootstrap.php`:
   - In the `muplugins_loaded` callback, `require_once WP_PLUGIN_DIR . '/woocommerce/woocommerce.php'` BEFORE the plugin file.
   - Add `tests_add_filter( 'setup_theme', static function (): void { \WC_Install::install(); $GLOBALS['wp_roles'] = null; wp_roles(); } );`.
   - If `WP_PLUGIN_DIR` has no `woocommerce` folder in tests-cli, fall back to `getenv( 'WP_TESTS_DIR' )`-relative discovery: run `npx wp-env run tests-cli ls wp-content/plugins` and use the printed path.
   - Add `tests/integration/test-settings.php`. It asserts that `apply_filters( 'woocommerce_get_settings_pages', [] )` contains an object whose `get_id()` is `jps_cs`.
9. **Seed `tests/seed.php`** (idempotent; extend in each phase):
   - Compile translations with `WP_CLI::runcommand( 'i18n make-mo ' . escapeshellarg( dirname( __DIR__ ) . '/languages' ) )`.
   - Make sure WC pages exist with `WC_Install::create_pages()`.
   - Create user `customer1` / `password` with role `customer` if missing.
   - Deactivate Germanized if active: `deactivate_plugins( 'woocommerce-germanized/woocommerce-germanized.php' )`.
10. **`.wp-env.json`**: append `"https://downloads.wordpress.org/plugin/woocommerce-germanized.latest-stable.zip"` to `plugins` (used only by the Germanized spec).
11. **ADR 0001 "Module architecture"**: covers the registry, the WC settings tab, the `jps_cs_` options, why the text domain equals the slug, the flat includes, the no-build JS, and that the template-owned Updates class keeps its own option name.
12. **OKF reference `.okf/references/woocommerce-source-facts.md`** (`type: Reference`): the facts under "Critical files & anchors" → "Verified in WC 11.2.2", each with path and line.

### Phase 1 — Quote mode (`quote` module)

Files: `includes/class-quote-mode.php`, `class-inquiries.php` (core, not a module), `class-inquiry-intake.php`, `class-inquiry-emails.php`, `class-email-new-inquiry.php`, `class-email-inquiry-confirmation.php`, `class-quote-blocks-integration.php`, `class-germanized-integration.php`; `assets/css/quote-mode.css`, `assets/js/quote-blocks.js`; `templates/emails/new-inquiry.php`, `inquiry-confirmation.php`, `plain/new-inquiry.php`, `plain/inquiry-confirmation.php`, `inquiry-items.php`.

1. **Activation API (open for later per-product or per-category scope).** In `Quote_Mode`:
   - `public static function is_active(): bool` returns `(bool) apply_filters( 'jps_cs_quote_mode_active', true )`. The module only inits when its toggle is on, so `true` is the base value.
   - `public static function applies_to( \WC_Product $product ): bool` returns `(bool) apply_filters( 'jps_cs_quote_mode_applies_to_product', self::is_active(), $product )`.
   - Product-level filters use `applies_to()`; cart, checkout and order logic uses `is_active()`.
2. **Purchasable without price.**
   - Filters `woocommerce_is_purchasable` and `woocommerce_variation_is_purchasable`, both at priority 10 with 2 args: return `true` when `applies_to( $product )`, `'publish' === $product->get_status()` (for a variation, the parent's status) and the type is not `external` or `grouped`; otherwise pass `$purchasable` through.
   - `woocommerce_variation_is_visible` (4 args): return `true` when published and `applies_to`.
   - `woocommerce_before_calculate_totals`: for each cart item with `'' === $item['data']->get_price( 'edit' )`, call `$item['data']->set_price( 0 )`. This prevents arithmetic on `''`.
   - Stock logic stays WooCommerce's: out-of-stock products can't be added to an inquiry unless the product allows backorders.
3. **Hide prices** (all at priority 100; return `''` or remove):
   - **Product prices.**
     - `woocommerce_get_price_html` → `''` when `applies_to`. This covers classic templates and the `woocommerce/product-price` block, which calls `get_price_html()` (WC 11.2 `src/Blocks/BlockTypes/ProductPrice.php:112`).
     - `woocommerce_show_variation_price` → `false`.
     - `woocommerce_available_variation` → set `price_html` to `''`.
     - `woocommerce_sale_flash` → `''`.
   - **Classic cart and mini-cart.** `woocommerce_cart_item_price`, `woocommerce_cart_item_subtotal`, `woocommerce_cart_subtotal`, `woocommerce_cart_total`, `woocommerce_cart_totals_order_total_html` → `''`.
   - **Structured data.** `woocommerce_structured_data_product` → `unset( $markup['offers'] )`.
   - **Catalog sorting.** `woocommerce_catalog_orderby` → unset `price` and `price-desc`.
   - **Body class.** `body_class` → add `jps-cs-quote-mode`.
   - **Stylesheet.** On `wp_enqueue_scripts`, enqueue `assets/css/quote-mode.css` (handle `jps-cs-quote-mode`). Every rule is scoped `body.jps-cs-quote-mode …` with `display:none !important`. Starting selectors:
     - Blocks: `.wc-block-components-product-price`, `.wc-block-cart-item__prices`, `.wc-block-cart-item__total`, `.wc-block-components-totals-wrapper`, `.wc-block-cart__totals-title`, `.wc-block-components-order-summary-item__individual-prices`, `.wc-block-components-order-summary-item__total-price`, `.wc-block-mini-cart__amount`, `.wc-block-mini-cart__footer-subtotal`, `.wc-block-components-product-sale-badge`, `.wc-block-components-sale-badge`, `.wc-block-order-confirmation-totals-wrapper`.
     - Classic: `.woocommerce-cart-form .product-price`, `.woocommerce-cart-form .product-subtotal`, `.cart_totals .shop_table`, `.woocommerce-checkout-review-order-table .product-total`, `.woocommerce-checkout-review-order-table tfoot`, `.woocommerce-mini-cart__total`, `.woocommerce-table--order-details tfoot`.
     - The final list is whatever makes the Phase 1 e2e "no currency symbol" assertions pass. Inspect the DOM in the running wp-env and add or remove selectors until they do.
   - **Known limitation, README only, no code.** Prices in quote mode are hidden, not confidential. Store API JSON (`/wc/store/v1/products`, `/cart`) still contains raw price fields when a product has a price; WC 11.2 has no filter to strip them (`ProductSchema.php:952-972`).
4. **No payment, shipping, taxes or coupons** when `is_active()`:
   - `woocommerce_cart_needs_payment` → `false`.
   - `woocommerce_order_needs_payment` → `false` for inquiries (see 6).
   - `woocommerce_cart_needs_shipping` → `false`. This also makes `../wc-shipping` irrelevant without touching it.
   - `wc_tax_enabled` → `false` when `! is_admin() || wp_doing_ajax() || WC()->is_rest_api_request()`. Use `wc()->is_store_api_request()` if available; otherwise check `REST_REQUEST` and that the route starts with `/wc/store`.
   - `woocommerce_coupons_enabled` → `false`.
5. **Labels.** Options and defaults (English source → German translation):

   | Option | English default | German |
   |---|---|---|
   | `jps_cs_quote_cart_label` | "Inquiry basket" | "Anfragekorb" |
   | `jps_cs_quote_add_to_cart_label` | "Add to inquiry" | "Zur Anfrage hinzufügen" |
   | `jps_cs_quote_proceed_label` | "Continue to inquiry" | "Weiter zur Anfrage" |
   | `jps_cs_quote_place_order_label` | "Send inquiry" | "Anfrage absenden" |

   - `Quote_Mode::labels(): array` returns `apply_filters( 'jps_cs_quote_labels', [ 'cart'=>…, 'add_to_cart'=>…, 'proceed'=>…, 'place_order'=>… ] )`.
   - **Add to cart:** `woocommerce_product_single_add_to_cart_text` and `woocommerce_product_add_to_cart_text` → `add_to_cart` label for `simple` and `variation` products where `applies_to` holds. Variable products keep "Select options".
   - **Place order:** `woocommerce_order_button_text`, priority `PHP_INT_MAX`, → `place_order`.
   - **Thank-you text:** `woocommerce_thankyou_order_received_text` → "Thank you. Your inquiry has been received." for inquiries.
   - **WooCommerce strings (PHP):** filter `gettext_woocommerce` and `ngettext_woocommerce` with a fixed map from WC source string to label. The map is `'Cart'`→cart, `'View cart'`→cart, `'Your cart'`→cart, `'Proceed to checkout'`→proceed, `'Place order'`→place_order.
     - Before committing, verify each source string exists in WC (`rg -F "'View cart'" <wc-src>`). Drop entries with no match; never add guessed variants.
     - WC source in wp-env: `.wp-env/<hash>/woocommerce` (see docs/agents/testing.md).
   - **WooCommerce strings (Blocks JS):** `assets/js/quote-blocks.js` applies the same map via `wp.hooks.addFilter( 'i18n.gettext_woocommerce', 'jp-catalog-suite/labels', ( translation, text ) => map[ text ] ?? translation )`.
     - Also `wc.blocksCheckout.registerCheckoutFilters( 'jp-catalog-suite', { placeOrderButtonLabel: () => labels.place_order, proceedToCheckoutButtonLabel: () => labels.proceed, cartItemPrice: () => '', subtotalPriceFormat: () => '' } )`.
     - Labels come from `wc.wcSettings.getSetting( 'jp-catalog-suite_data' )`.
   - **Blocks loading:** `Quote_Blocks_Integration implements \Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface`.
     - `get_name()` returns `'jp-catalog-suite'`.
     - It registers the script handle `jps-cs-quote-blocks` with deps `['wc-blocks-checkout','wc-settings','wp-hooks','wp-i18n']` and calls `wp_set_script_translations( 'jps-cs-quote-blocks', 'jp-catalog-suite', PLUGIN_DIR . 'languages' )`.
     - `get_script_data()` returns `[ 'labels' => Quote_Mode::labels() ]`.
     - Register it on actions `woocommerce_blocks_cart_block_registration` and `woocommerce_blocks_checkout_block_registration`. The action is `'woocommerce_blocks_' . $identifier . '_registration'` (WC `src/Blocks/Integrations/IntegrationRegistry.php:50`).
     - The Mini-Cart block has no `integration_registry_identifier` (WC 11.2.2 `src/Blocks/BlockTypes/MiniCart.php`). So also enqueue `jps-cs-quote-blocks` on `wp_enqueue_scripts` whenever `wp_script_is( 'wc-blocks-checkout', 'registered' )`, with the same deps.
   - Page titles ("Cart", "Checkout") are WP pages; the README tells admins to rename them. No `the_title` filtering.
   - No other WooCommerce strings are rewritten. Leftover "order" wording (order number, order details) is accepted: the legal risk is the button wording (§312j BGB), which the labels cover.
6. **Inquiries.** Split by what each behavior depends on:
   - **`Inquiries` (core, `includes/class-inquiries.php`)** handles everything keyed on an existing inquiry. `Plugin::init()` initializes it regardless of the quote-mode toggle. With an unregistered status, inquiries vanish from the admin order lists and My Account (WC `ListTable.php:575-586`, `class-wc-admin-list-table-orders.php:646-655`), and their views would show prices once quote mode is off.
   - **`Inquiry_Intake` (module, `includes/class-inquiry-intake.php`)** handles creating inquiries and the shop-facing account labels. `Quote_Mode::init()` initializes it.
   - **Status registration (`Inquiries`).**
     - `woocommerce_register_shop_order_post_statuses` adds `'wc-jps-inquiry' => [ 'label' => _x( 'Inquiry', 'Order status', 'jp-catalog-suite' ), 'public' => false, 'exclude_from_search' => false, 'show_in_admin_all_list' => true, 'show_in_admin_status_list' => true, 'label_count' => _n_noop( 'Inquiry <span class="count">(%s)</span>', 'Inquiries <span class="count">(%s)</span>', 'jp-catalog-suite' ) ]`.
     - `wc_order_statuses` adds `'wc-jps-inquiry' => label` after `wc-pending`.
   - **Inquiry flag (`Inquiries`).** `Inquiries::is_inquiry( \WC_Order $order ): bool` returns `'yes' === $order->get_meta( '_jps_cs_inquiry' ) || $order->has_status( 'jps-inquiry' )`. The meta outlives later status changes, so prices stay hidden when an admin moves an inquiry to another status.
   - **Mark orders (`Inquiry_Intake`).** `woocommerce_checkout_create_order` (classic, `$order, $data`) and `woocommerce_store_api_checkout_update_order_meta` (Blocks, `$order`): when `Quote_Mode::is_active()`, `$order->update_meta_data( '_jps_cs_inquiry', 'yes' )`.
   - **Status flow (`Inquiry_Intake`).** WC calls `$order->payment_complete()` for orders that need no payment: classic `WC_Checkout::process_order_without_payment`, Store API `CheckoutTrait::process_without_payment`. Both reach it with status `pending`; the Store API sets it at `Checkout.php:686`.
     - Hook `woocommerce_pre_payment_complete` ($order_id): if the order is an inquiry, `$order->update_status( 'jps-inquiry', __( 'Inquiry received.', 'jp-catalog-suite' ) )`, then `do_action( 'jps_cs_inquiry_created', $order )`.
     - Because `jps-inquiry` is not in `woocommerce_valid_order_statuses_for_payment_complete`, `payment_complete()` then skips `date_paid`, the "Payment complete" note and `woocommerce_payment_complete`. No stock reduction runs, since stock reduction hooks processing/completed/on-hold/payment_complete.
   - **Analytics (`Inquiries`).**
     - `woocommerce_analytics_settings_default_excluded_order_statuses` adds `'jps-inquiry'` (WC ≥ 11.2).
     - `option_woocommerce_excluded_report_order_statuses` appends `'jps-inquiry'` when the saved value is an array lacking it.
   - **My Account (`Inquiry_Intake`).** When `is_active()`:
     - `woocommerce_account_menu_items` renames `orders` to "Inquiries".
     - `woocommerce_account_orders_columns` removes `order-total`.
   - **Order displays (`Inquiries`)**: thank-you page, account view and list, any WC email. For inquiries:
     - `woocommerce_get_order_item_totals` → `[]`.
     - `woocommerce_order_formatted_line_subtotal` → `''`.
     - `woocommerce_get_formatted_order_total` → `''` outside wp-admin (My Account orders list when quote mode is off).
     - Admin order screens keep prices.
   - **Lifecycle.** The plugin covers intake only. WC's own status emails (e.g. "Completed order") still reach the customer, without prices, if an admin moves an inquiry to such a status; the README warns.
7. **Emails (`Inquiry_Emails`, module).**
   - `woocommerce_email_classes` adds `'JPS_CS_Email_New_Inquiry' => new Email_New_Inquiry()` and `'JPS_CS_Email_Inquiry_Confirmation' => new Email_Inquiry_Confirmation()`. Require the class files inside the filter callback, because `WC_Email` loads late.
   - `woocommerce_email_actions` adds `'woocommerce_order_status_pending_to_jps-inquiry'`.
   - **`Email_New_Inquiry extends \WC_Email`:**
     - `id = 'jps_cs_new_inquiry'`, `title = __( 'New inquiry', … )`, `customer_email = false`.
     - Recipient comes from the standard WC email setting `recipient`; default `get_option( 'admin_email' )`, comma-separated addresses allowed.
     - Default subject "[{site_title}]: New inquiry #{order_number}", heading "New inquiry".
     - `template_base = PLUGIN_DIR . 'templates/'`, `template_html = 'emails/new-inquiry.php'`, `template_plain = 'emails/plain/new-inquiry.php'`.
     - Trigger: `add_action( 'woocommerce_order_status_pending_to_jps-inquiry_notification', [ $this, 'trigger' ], 10, 2 )`. It sets `$this->object`, the `{order_number}`/`{order_date}` placeholders and the reply-to of the customer email.
   - **`Email_Inquiry_Confirmation`:** same shape. `id = 'jps_cs_inquiry_confirmation'`, `customer_email = true`, recipient = billing email, subject "Your inquiry at {site_title}", heading "Thank you for your inquiry".
   - **Templates.**
     - Use WC's email header/footer actions (`woocommerce_email_header`, `woocommerce_email_footer`).
     - Include `templates/emails/inquiry-items.php`: a table with Product, SKU, Quantity and item meta via `wc_display_item_meta( $item )`. No price columns; no `wc_get_email_order_items`.
     - Then the customer note (`$order->get_customer_note()`) and `do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email )`.
     - Plain variants mirror this content as text.
8. **Checkout fields.** All four are WC `select` settings with options `required|optional|hidden`, except the last:
   - `jps_cs_quote_field_company` (default `optional`), `jps_cs_quote_field_phone` (default `optional`), `jps_cs_quote_field_address` (default `hidden`), and the checkbox `jps_cs_quote_field_message` (default `yes`).
   - Name and email are always required.
   - **Company and phone** (both checkouts): filters `option_woocommerce_checkout_company_field`, `default_option_woocommerce_checkout_company_field` and the same for `woocommerce_checkout_phone_field` return the module setting. Blocks honors these core options per WC docs "Removing checkout fields".
   - **Address** (both checkouts): if `hidden`, filters `woocommerce_get_country_locale` (every country key), `woocommerce_get_country_locale_default` and `woocommerce_get_country_locale_base` set `address_1`, `address_2`, `city`, `postcode` and `state` to `hidden => true, required => false`.
     - Classic additionally unsets those `billing_*` keys in `woocommerce_checkout_fields`.
     - If `optional`, set `required => false` only.
     - **Limitation:** Blocks cannot hide Country (WC docs). It stays visible, preselected to the store base country.
   - **Message:** classic `woocommerce_checkout_fields` unsets `order.order_comments` when the setting is `no`. In Blocks the order-notes field comes from the Checkout block's own "order notes" setting and can't be required; the README says so.
9. **Germanized (`Germanized_Integration`).** Init only if `class_exists( 'WooCommerce_Germanized' )`; otherwise check `function_exists( 'WC_germanized' )` (verify which exists in the installed Germanized). Behaviour, when `Quote_Mode::is_active()`:
   - **Checkboxes.** On `woocommerce_gzd_registered_legal_checkboxes` ($manager), for each checkbox ID of `$manager->get_checkboxes()` not in `apply_filters( 'jps_cs_germanized_kept_checkboxes', [ 'privacy' ] )`, call `$manager->remove( $id )`. API verified in `includes/class-wc-gzd-legal-checkbox-manager.php:901-926,1154`; check whether `get_checkboxes()` keys are IDs, else read `->get_id()`.
   - **Price and legal notices.** On `wp` at priority `PHP_INT_MAX`, for each of `wc_gzd_get_single_product_shopmarks`, `…_single_product_grouped_shopmarks`, `…_product_loop_shopmarks`, `…_product_block_shopmarks`, `…_cart_shopmarks`, `…_mini_cart_shopmarks`, `…_checkout_shopmarks`, `…_order_shopmarks`: if `function_exists`, call `->remove()` on each item (`src/Shopmark.php::remove()`).
     - **Fallback** if the e2e still shows "inkl. MwSt"/"zzgl. Versand": add a `pre_option` filter returning `'no'` for option names matching `/^woocommerce_gzd_display_[a-z_]+$/` that don't end in `_priority` or `_filter`. These are `Shopmark::is_enabled()`'s options, `woocommerce_gzd_display_{location}_{type}`.
   - **Button text.** If Germanized overrides the Blocks or classic button text despite the priority-`PHP_INT_MAX` filter and the JS filter, grep the installed Germanized for `order_submit_btn_text` and add `pre_option_<that option>` returning `labels()['place_order']`.
   - **German Market** is proprietary and not testable: unsupported. The README tells sites to use the `jps_cs_inquiry_created` and `jps_cs_quote_mode_active` hooks.
10. **`wpml-config.xml` `<admin-texts>`.** Add `<key name="jps_cs_quote_cart_label"/>`, `<key name="jps_cs_quote_add_to_cart_label"/>`, `<key name="jps_cs_quote_proceed_label"/>` and `<key name="jps_cs_quote_place_order_label"/>`.
11. **`Quote_Mode::settings()`** returns the WC settings arrays for the label options of step 5 (text, `placeholder` = translated default), the checkout-field options of step 8, and the Germanized note, `type => info`.
12. **ADRs.**
    - 0002 "Inquiry, not quote": the customer submits an Inquiry; "quote" is reserved for the seller's Angebot (out of scope) and names only the module. Status, meta, hooks and email IDs use `inquiry` because they are stored in the DB or consumed by other code, so renaming them later is expensive.
    - 0003 "Inquiries without gateway": `needs_payment` false; status via `woocommerce_pre_payment_complete`; own `WC_Email` classes; analytics exclusion; intake-only lifecycle; inquiry-keyed behavior in core, independent of the toggle; uninstall leaves inquiries untouched; stock and backorders unchanged.
    - 0004 "Price hiding and Blocks compatibility": PHP filters plus Blocks checkout filters plus CSS; prices hidden, not confidential (Store API JSON limitation); only the brief's labels replaced; locale-based field hiding; Country not hideable; message = order notes.
    - 0005 "Germanized integration".

### Phase 2 — Product tabs with access levels and protected files (`tabs` module)

Files: `includes/class-product-tabs.php`, `class-tab-admin.php`, `class-access.php`, `class-protected-files.php`, `class-download-log.php`; `blocks/access/{block.json,index.js,index.asset.php}`, `blocks/file-list/{block.json,index.js,index.asset.php}`; `assets/css/tabs.css`.

1. **CPT `jps_cs_tab`** (`Product_Tabs::register_post_type` on `init`):
   - `public => false`, `show_ui => true`, `show_in_menu => 'edit.php?post_type=product'`.
   - `show_in_rest => true` (block editor). `supports => [ 'title', 'editor', 'revisions' ]`. `capability_type => 'product'`, `map_meta_cap => true`.
   - Labels "Product tabs" / "Product tab".
   - REST read of a published tab returns `content.rendered`, which goes through the blocks' render callbacks. Guests therefore never receive protected content; no extra REST code.
   - `wpml-config.xml`: `<custom-types><custom-type translate="1">jps_cs_tab</custom-type></custom-types>`.
2. **Tab meta** (registered with `register_post_meta`, `single => true`, `show_in_rest => false`). Edited in the metabox "Tab settings" (`Tab_Admin`, context `side`), saved on `save_post_jps_cs_tab` with nonce `jps_cs_tab_meta` and `current_user_can( 'edit_post', $post_id )`.

   | Meta | Type | Default | Notes |
   |---|---|---|---|
   | `_jps_cs_scope` | string | `all` | `all`\|`categories`\|`products` |
   | `_jps_cs_categories` | int[] | — | `product_cat` term IDs; descendants included at match time |
   | `_jps_cs_products` | int[] | — | |
   | `_jps_cs_replaces` | int | `0` | ID of a tab with scope `all` or `categories`; only meaningful for scope `products` |
   | `_jps_cs_priority` | string | `''` | Empty means "replaced tab's priority, else 50" |

   - **UI.** Radio buttons for scope.
     - Categories: `<select multiple class="wc-enhanced-select">` of all `product_cat` terms (hierarchical names).
     - Products: `<select multiple class="wc-product-search" data-action="woocommerce_json_search_products">`.
     - Replaces: a select of global tabs.
     - Priority: number input.
     - Enqueue `wc-enhanced-select` and `woocommerce_admin_styles` on `jps_cs_tab` edit screens.
   - **Prefill.** `post-new.php?post_type=jps_cs_tab&jps_cs_product={ID}` preselects scope `products` with that product (absint the GET value; check `current_user_can( 'edit_post', ID )`).
   - **wpml-config:** `<custom-field action="copy">` for the five keys and `_jps_cs_hidden_tabs`.
3. **Product data panel "Tabs".** `woocommerce_product_data_tabs` adds `jps_cs_tabs`; `woocommerce_product_data_panels` renders it. It lists:
   - (a) Global tabs that match this product, each with a checkbox "Hide on this product" (`jps_cs_hidden_tabs[]`).
   - (b) Product-specific tabs assigned here, with edit links.
   - (c) A button-link "Add product-specific tab" to the prefill URL.
   - Save on `woocommerce_process_product_meta` (WC has verified its `woocommerce_meta_nonce`) into `_jps_cs_hidden_tabs` as canonical int[].
4. **Resolution (`Product_Tabs::tabs_for( \WC_Product $product ): array`).**
   - Query once per request: `get_posts( [ 'post_type' => 'jps_cs_tab', 'post_status' => 'publish', 'numberposts' => -1, 'suppress_filters' => false ] )`. Keep `suppress_filters` false so WPML and Polylang return current-language posts.
   - Let `P = canonical_id( product_id, 'product' )` and `T = canonical_id( tab_id, 'jps_cs_tab' )`. A tab applies if:
     - `all`; or
     - `categories` and the product has a term whose canonical ID is in the expanded set (stored IDs plus `get_term_children`); or
     - `products` and `P` ∈ stored IDs.
   - Drop tabs whose `T` is in the product's `_jps_cs_hidden_tabs`.
   - For applicable tabs with `_jps_cs_replaces = R > 0`, drop the tab with canonical ID `R`.
   - Priority = meta, else the replaced tab's priority, else `50`.
5. **Rendering.**
   - Per tab, `$html = apply_filters( 'jps_cs_tab_content', wp_filter_content_tags( do_shortcode( do_blocks( $post->post_content ) ) ), $post, $product )`.
   - The tab is skipped when empty: `'' === trim( wp_strip_all_tags( preg_replace( '/<(img|video|iframe|embed|object|svg)\b/i', 'x<$1', $html ) ) )`. In words: treat media tags as content.
   - Filter `woocommerce_product_tabs` at priority 98 (WC sorts at 99): add `"jps_cs_tab_{T}" => [ 'title' => get_the_title( $post ), 'priority' => …, 'callback' => static fn() => print $html ]`. `$html` was already rendered, so render once.
   - Product from `$GLOBALS['product']` if it is a `WC_Product`, else `wc_get_product( get_the_ID() )`; return `$tabs` unchanged if none.
   - Afterwards: `$tabs = apply_filters( 'jps_cs_product_tabs', $tabs, $product )`.
   - This works for classic templates and the `woocommerce/product-details` block, which applies `woocommerce_product_tabs` (WC `src/Blocks/BlockTypes/ProductDetails.php:119`).
6. **Core tabs.** Settings per key `description`, `additional_information`, `reviews`: `jps_cs_tabs_core_{key}_title` (text, empty = WC default), `…_priority` (number, empty = WC default), `…_hidden` (checkbox).
   - Applied in the same priority-98 filter: unset if hidden, override title and priority if set.
   - wpml-config `<admin-texts>`: the three `_title` keys.
   - Core tabs are configured globally only: no per-product core-tab overrides, and product tabs can't replace core tabs.
7. **Access rule (`Access`).**
   - `public static function can_access( array $context = [] ): bool` returns `(bool) apply_filters( 'jps_cs_can_access_protected', is_user_logged_in(), get_current_user_id(), $context )`. Context keys are optional: `product_id` (block rendering only, taken server-side from the current product) and `attachment_id` (downloads). File access depends on user and file only: downloads never pass `product_id`, because the endpoint's `product` param is client-supplied.
   - **Notice.**
     - Option `jps_cs_protected_notice` (textarea, `wp_kses_post`). Default: "This content is available to registered customers. {login} or {register} to view it."
     - `{login}` → `<a href="wp_login_url( $return_url )">Log in</a>`.
     - `{register}` → a registration link, or `''` when registration is off. Registration link: if `'yes' === get_option( 'woocommerce_enable_myaccount_registration' )`, use `add_query_arg( 'jps_cs_redirect', rawurlencode( $return_url ), wc_get_page_permalink( 'myaccount' ) )` plus filter `woocommerce_registration_redirect` → `wp_validate_redirect( wp_unslash( $_GET['jps_cs_redirect'] ?? '' ), $default )`. Read the redirect from the referer's query when `$_GET` is empty: WC posts the registration form to the same URL, so the `$_GET` param persists. Otherwise, if `get_option( 'users_can_register' )`, use `add_query_arg( 'redirect_to', rawurlencode( $return_url ), wp_registration_url() )`. Otherwise `''`.
     - `$return_url = get_permalink()` of the product.
     - Logged-in users who lack access see "You do not have permission to view this content."
     - Output passes through `apply_filters( 'jps_cs_protected_notice_html', $html, $context )`, wrapped in `<div class="jps-cs-protected-notice">`.
     - wpml-config `<admin-texts>`: `jps_cs_protected_notice`.
   - **Caching.** When an access-dependent block renders for a logged-in user, `if ( ! defined( 'DONOTCACHEPAGE' ) ) define( 'DONOTCACHEPAGE', true );`. WP already sends no-cache headers for logged-in users (`WP::send_headers`). Guests always get the identical public variant, so guest pages stay cacheable. No AJAX fragments. The README carries a Varnish VCL snippet that passes on cookie `wordpress_logged_in_` and on `woocommerce_items_in_cart`/`wp_woocommerce_session_`.
8. **Block `jp-catalog-suite/access`.**
   - Attributes: `audience` (`"authorized"`|`"unauthorized"`, default `authorized`), `showNotice` (bool, default `true`).
   - `supports.html = false`. Editor: `InnerBlocks`; `InspectorControls` with a SelectControl ("Only with access" / "Only without access") and a ToggleControl ("Show login notice when hidden"); a label chip in the editor. `save` → `InnerBlocks.Content`.
   - Registered in PHP with `register_block_type( PLUGIN_DIR . 'blocks/access', [ 'render_callback' => [ Access::class, 'render_block' ] ] )`. Render, with `$ok = can_access( [ 'product_id' => current product id or 0 ] )`:
     - `authorized` → `$ok ? $content : ( showNotice ? notice : '' )`
     - `unauthorized` → `$ok ? '' : $content`
   - Vocabulary (`GLOSSARY.md`): content outside the block is **public content**, `authorized` holds **protected content**, `unauthorized` holds **fallback content** shown in place of protected content.
   - Each `authorized` block with `showNotice` renders its own notice. Editors switch `showNotice` off on extra blocks; no deduplication.
   - `index.asset.php` deps: `wp-blocks`, `wp-block-editor`, `wp-components`, `wp-element`, `wp-i18n`. block.json `textdomain: jp-catalog-suite`.
9. **Block `jp-catalog-suite/file-list`.**
   - Attribute `ids` (number[]).
   - Editor: `MediaUpload` with `multiple` and `gallery: false`, `ServerSideRender` preview.
   - Dynamic render: `<ul class="jps-cs-file-list">`, one `<li>` per existing attachment.
     - Link `wp_get_attachment_url( $id )`; this already yields the endpoint for protected files (step 11). For protected files rendered with a current product, append `product=<id>` via `add_query_arg`; it feeds the download log only.
     - Title, uppercase extension, `size_format( filesize( get_attached_file( $id ) ) )` when the file exists.
     - Protected items for viewers without access render as unlinked text plus "(login required)".
   - deps as above plus `wp-server-side-render`.
10. **Protected uploads (`Protected_Files`).**
    - **Directory** `wp_upload_dir()['basedir'] . '/jps-cs-protected'`. `ensure_directory()` runs `wp_mkdir_p` and writes, only if missing:
      - `.htaccess`: `# Generated by JayPee Solutions Catalog Suite — files are served only through the plugin.`, then `<IfModule mod_authz_core.c>` `Require all denied` `</IfModule>` and `<IfModule !mod_authz_core.c>` `Deny from all` `</IfModule>`.
      - `index.php`: `<?php // Silence is golden.`
    - **MIME types.** Option `jps_cs_files_mimes` (textarea, one `ext[|ext2]=mime` per line). Default:
      ```
      stp|step=application/step
      dxf=application/dxf
      ```
      Parse it into `upload_mimes` additions for users with `upload_files`. `wp_check_filetype_and_ext` (5 args): when the extension is configured, return `[ 'ext' => $ext, 'type' => $mime, 'proper_filename' => false ]`. finfo reports text/plain or octet-stream for STEP/DXF, which core would reject.
    - **Auto-protect.** Option `jps_cs_files_protected_exts` (text, default `stp, step, dxf`).
      - `wp_handle_upload_prefilter` and `wp_handle_sideload_prefilter`: if the extension is in the list, set static `$routing = true` and call `ensure_directory()`.
      - `upload_dir`: when `$routing`, prefix `subdir` with `/jps-cs-protected` and recompute `path` and `url`.
      - `wp_handle_upload` and `wp_handle_sideload`: reset `$routing = false`.
      - `add_attachment`: if `get_attached_file()` contains `/jps-cs-protected/`, set `_jps_cs_protected = 1`.
      - `intermediate_image_sizes_advanced` and `fallback_intermediate_image_sizes`: return `[]` for protected files, so no PDF previews are written.
      - PDFs are not in the default list, because datasheets are usually public. Individual PDFs get protected with the manual toggle below.
    - **Manual toggle.**
      - `attachment_fields_to_edit` adds the checkbox "Protected (served only to authorized users)" for non-image attachments when `current_user_can( 'edit_post', $id )`.
      - `attachment_fields_to_save` calls `protect( $id )` or `unprotect( $id )`. Each moves the file with `WP_Filesystem()->move()` into or out of `jps-cs-protected/<same subdir>`, deletes intermediate size files, clears `sizes` in the metadata, and runs `update_attached_file()` and the meta set/delete.
      - On failure, leave the state unchanged and add `$post['errors']['jps_cs_protected']['errors'][] = __( 'The file could not be moved.', … )`.
    - **URL.** `wp_get_attachment_url` filter: for protected attachments, return `add_query_arg( 'jps_cs_file', $id, home_url( '/' ) )`. Library, REST `source_url` and blocks then never leak the path.
    - `rest_attachment_query` needs no change.
11. **Download endpoint.** `query_vars` adds `jps_cs_file`. On `template_redirect` priority 1, if the var is set:
    - `nocache_headers()`.
    - Missing attachment → `status_header( 404 )` plus `wp_die( …, 404 )`.
    - Exists but not `_jps_cs_protected` → `wp_redirect( wp_get_attachment_url( $id ), 302 )` and exit. Endpoint links stored in content keep working after "unprotect".
    - Guest → `wp_safe_redirect( wp_login_url( current request URL ) )` and exit.
    - `$product_id = absint( $_GET['product'] ?? 0 )` is client-supplied and only feeds the action and the log.
    - `! Access::can_access( [ 'attachment_id' => $id ] )` → `wp_die( __( 'You do not have permission to download this file.' ), 403 )`.
    - Otherwise: `do_action( 'jps_cs_before_file_delivery', $id, get_current_user_id(), $product_id )`, log (step 12), then deliver.
    - **Delivery.**
      - Option `jps_cs_files_delivery` is `auto|xsendfile|php`, default `auto`. `auto` = `function_exists( 'apache_get_modules' ) && in_array( 'mod_xsendfile', apache_get_modules(), true )`. The value passes through `apply_filters( 'jps_cs_file_delivery_method', $method, $id )`.
      - Headers: `Content-Type` = attachment MIME, `Content-Disposition: attachment; filename="<sanitized basename>"; filename*=UTF-8''<rawurlencode>`, `X-Content-Type-Options: nosniff`, `Cache-Control: private, no-store`.
      - `xsendfile` → `header( 'X-Sendfile: ' . $path )`.
      - `php` → `Content-Length`, close all output buffers, `@set_time_limit( 0 )`, read with fopen/fread in 1 MiB chunks, echo, `flush()`. No HTTP Range support.
      - Then `exit`.
    - **Tests.** Integration tests stop delivery by throwing from the `jps_cs_before_file_delivery` action.
12. **Download log (`Download_Log`).**
    - Option `jps_cs_files_log_enabled` (checkbox, default `no`), `jps_cs_files_log_retention_days` (number, default `365`, `0` = keep).
    - **Table** `{prefix}jps_cs_download_log`, created by `dbDelta` in `install_table()` on activation and when option `jps_cs_db_version` ≠ `'1'`:
      ```sql
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      user_id BIGINT UNSIGNED NOT NULL,
      attachment_id BIGINT UNSIGNED NOT NULL,
      product_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
      downloaded_at DATETIME NOT NULL,
      PRIMARY KEY (id),
      KEY downloaded_at (downloaded_at)
      ```
      `downloaded_at` is UTC from `current_time( 'mysql', true )`. No IP stored.
    - **Admin page** "Download log" under the WooCommerce menu (`add_submenu_page( 'woocommerce', …, 'manage_woocommerce', 'jps-cs-download-log' )`). It is a plain table of the latest 200 rows: user display name with email, file title, product title, local time.
    - **Retention.** Daily cron `jps_cs_purge_download_log`, scheduled on module init if not yet scheduled, deletes rows older than the retention period.
    - **Privacy.**
      - `deleted_user` deletes the user's rows.
      - `wp_privacy_personal_data_exporters` registers an exporter: one item per row with file title, product title and local time.
      - `wp_privacy_personal_data_erasers` registers an eraser that deletes the user's rows (no anonymizing).
      - On `admin_init`, `wp_add_privacy_policy_content()` suggests a translatable paragraph on what the log stores and its retention.
13. **`Product_Tabs::settings()`** covers the core-tab fields (6), notice (7), MIME types, protected extensions, delivery method, log toggle and retention, plus an `info` field with the README pointer for PHP/Traefik limits.
14. **`Plugin::activate()`** now calls `Protected_Files::ensure_directory()` and `Download_Log::install_table()`.
15. **ADRs.**
    - 0006 "Tabs as block-editor CPT with access block": the `audience` semantics (public, protected, fallback content), one notice per block, rendering via callbacks (REST-safe), translation via wpml-config plus `canonical_id`, only product tabs replace global tabs, core tabs configured globally.
    - 0007 "Protected files": uploads subdir plus deny rule instead of a dir outside the webroot, because attachments must live under the uploads basedir. Also the URL filter → endpoint, X-Sendfile auto, the 256 MB limit and its README requirements; file access by user and file only (`product` param untrusted, log only); PDFs protected per file; redirect for files no longer protected; download-log privacy.
    - 0008 "Caching": no fragments; logged-in bypass plus DONOTCACHEPAGE plus the Varnish VCL.

### Phase 3 — Variable length (`length` module)

Files: `includes/class-variable-length.php`, `assets/js/variable-length.js`, `assets/css/variable-length.css`.

1. **Global settings** (`Variable_Length::settings()`):
   - `jps_cs_length_unit` (select `mm|cm|m`, default `cm`), `jps_cs_length_min` (default `1`), `jps_cs_length_max` (default `1000`), `jps_cs_length_step` (default `1`).
   - `jps_cs_length_pricing_enabled` (checkbox, default `no`, desc "Allows price per length unit on products; leave off in quote mode").
2. **Product fields.** In `woocommerce_product_options_general_product_data`, inside `<div class="options_group show_if_simple show_if_variable">`, using `woocommerce_wp_checkbox` and `woocommerce_wp_text_input` (`type=number`, `custom_attributes step=any`):
   - `_jps_cs_length_enabled`, `_jps_cs_length_unit` (select), `_jps_cs_length_min`, `_jps_cs_length_max`, `_jps_cs_length_step`, `_jps_cs_length_default`.
   - `_jps_cs_length_price_per_unit`, rendered only when pricing is enabled.
   - Empty value = global default. Save on `woocommerce_process_product_meta` with `wc_format_decimal`.
   - **Variations.** `woocommerce_product_after_variable_attributes` renders min/max/step/default as `jps_cs_length_{field}[{loop}]` (empty = inherit from parent). Save on `woocommerce_save_product_variation` ($variation_id, $i) into `_jps_cs_length_{field}`.
   - wpml-config `copy` for all `_jps_cs_length_*`.
3. **Config resolver.** `Variable_Length::config( \WC_Product $product ): ?array` returns `null` when the parent is not enabled. Otherwise it returns `apply_filters( 'jps_cs_length_config', [ 'unit', 'min', 'max', 'step', 'default', 'price_per_unit' ], $product )`.
   - Variation meta overrides the parent, which overrides the globals.
   - `default` falls back to `min`.
   - `price_per_unit` is true only when the global pricing setting and the product meta are both `yes`.
4. **Frontend input.** `woocommerce_before_add_to_cart_button` renders:
   ```html
   <p class="jps-cs-length"><label for="jps_cs_length">Length (unit)</label>
   <input type="number" id="jps_cs_length" name="jps_cs_length" required min max step value inputmode="decimal"></p>
   ```
   - This covers the classic form, the `woocommerce/add-to-cart-form` block and the `add-to-cart-with-options` block, which fires this hook (WC `src/Blocks/BlockTypes/AddToCartWithOptions/AddToCartWithOptions.php:391`).
   - Client validation is native HTML constraint validation.
   - `woocommerce_available_variation` adds `jps_cs_length => [ min, max, step, default ]`. `assets/js/variable-length.js` (jQuery, dep `wc-add-to-cart-variation`) updates the input attributes on `found_variation` and restores the parent values on `reset_data`.
   - **Loop and collections.** For enabled products:
     - `woocommerce_product_supports` → `false` for `ajax_add_to_cart`.
     - `woocommerce_product_add_to_cart_url` → permalink.
     - `woocommerce_product_add_to_cart_text` → "Choose length". When quote mode `applies_to` is also true, the length module's filter runs at priority 110 so it wins over the quote label at 100.
5. **Server validation.**
   - `Variable_Length::validate( float $value, array $config ): ?string` returns `null` or a translated error. The value must satisfy `min ≤ value ≤ max` and `abs( ( $value - $min ) / $step - round( ( $value - $min ) / $step ) ) < 1e-6`.
   - Errors: "Please enter a length between %1$s and %2$s %3$s." and "The length must be a multiple of %1$s %2$s."
   - Filter `woocommerce_add_to_cart_validation` ($passed, $product_id, $qty, $variation_id = 0): read the value from `$_POST['jps_cs_length']` (`wc_format_decimal( wp_unslash( … ) )`; phpcs-ignore with a comment noting WC's add-to-cart has no nonce), or from the static value captured by `woocommerce_store_api_add_to_cart_data` (`$data, $request` → `$request['jps_cs_length']`). Missing or invalid → `wc_add_notice( $error, 'error' )`, return `false`.
6. **Cart item data.**
   - `woocommerce_add_cart_item_data` sets `jps_cs_length` (float) and `jps_cs_length_unit`. When pricing is on, it also sets `jps_cs_unit_price` = `(float) $product->get_price( 'edit' )`.
   - Different values give different WC cart item keys, which keeps the lines separate.
   - **Pricing.** On `woocommerce_before_calculate_totals` priority 20, items with `jps_cs_unit_price` get `set_price( apply_filters( 'jps_cs_length_item_price', $unit_price * $length, $length, $item ) )`. Price HTML gets the suffix ` / {unit}` via `woocommerce_get_price_html` priority 20 for price-per-unit products.
   - **Quantity** counts pieces of the entered length: the line total is unit price × length × qty, and stock counts pieces.
   - **Display.** `woocommerce_get_item_data` adds `[ 'key' => __( 'Length' ), 'value' => wc_format_localized_decimal( $length ) . ' ' . $unit ]`. Store API uses the same filter (WC `src/StoreApi/Schemas/V1/CartItemSchema.php:174`).
   - **Order item.** `woocommerce_checkout_create_order_line_item` (Store API runs it too, via `create_order_line_items`) adds meta `jps_cs_length` (value as string) and `_jps_cs_length_unit`.
     - `woocommerce_order_item_display_meta_key` maps `jps_cs_length` → "Length".
     - `woocommerce_order_item_display_meta_value` appends the unit.
     - This covers admin, emails including the inquiry templates via `wc_display_item_meta`, and My Account.
7. **ADR 0009** "Variable length as product option", including quantity = pieces.

### Phase 4 — Documentation deliverables

1. **`README.md`**: keep the dev and release sections. Add:
   - Feature overview per module.
   - Installation, including the private-repo token on the Updates page.
   - Configuration walkthrough for WooCommerce → Settings → Catalog Suite.
   - Hosting requirements:
     - `upload_max_filesize`/`post_max_size` ≥ 256M and the Traefik `buffering.maxRequestBodyBytes` if a buffering middleware is used.
     - Optional X-Sendfile: `apt-get install libapache2-mod-xsendfile`, `XSendFile On`, `XSendFilePath <uploads>/jps-cs-protected`.
     - Nginx deny snippet: `location ^~ /wp-content/uploads/jps-cs-protected/ { deny all; }`.
     - Varnish VCL bypass.
   - Bot protection for self-registration: recommend ALTCHA (open source, self-hosted).
   - Multilingual notes: TranslatePress needs no config; WPML and Polylang via `wpml-config.xml`.
   - Known limitations: prices hidden, not confidential (Store API price JSON); Blocks Country field; Blocks order notes not requirable; German Market unsupported; no HTTP Range on PHP streaming; single-site uninstall; stock and backorders apply to inquiries; WC status emails (e.g. "Completed order") reach customers if an admin moves an inquiry to such a status; deleting the plugin hides inquiries from order lists, so bulk-change their status first.
   - Privacy: what the download log stores, retention, exporter, eraser.
   - A hook reference table for every `jps_cs_*` filter and action named in this plan, with signatures.
2. **`readme.txt`**: description, installation and changelog for WordPress.org.
3. **`CHANGELOG.md`**: list the three modules under `## [Unreleased]` → `### Added`.
4. **`docs/testing-checklist.md`**: per module, a checklist matrix of {guest, logged-in customer} × {DE, EN (TranslatePress)} × {Classic (shortcode pages / classic theme), Blocks}. Plus the Germanized-active rows and protected-file direct-URL rows.
5. **OKF**: `.okf/index.md` lists all decisions plus a runbook `.okf/runbooks/hosting-requirements.md` (limits, X-Sendfile, Varnish). Add a `.okf/log.md` entry. Run `skill://okf-validate`.

## Critical files & anchors

- `jp-catalog-suite.php` — `plugins_loaded` bootstrap (lines 42–66) and `before_woocommerce_init` (71–82): wiring, activation hooks, blocks compatibility.
- `tests/integration/bootstrap.php` — `muplugins_loaded` callback (17–22): must load WooCommerce before the plugin and install WC tables.
- `scripts/build-release.sh:45` — copy list; without the edit, `uninstall.php`, `wpml-config.xml` and `templates/` are missing from releases.
- WC 11.2 source (`.wp-env/<hash>/woocommerce` after `npm run env:start`):
  - `includes/class-wc-order.php:138-190` (`payment_complete` flow behind Phase 1 step 6).
  - `src/StoreApi/Utilities/CheckoutTrait.php:71`.
  - `includes/class-wc-checkout.php:1206`.
  - Verified in WC 11.2.2 during the design review:
    - `src/StoreApi/Routes/V1/Checkout.php:686` sets `pending` before `payment_complete()`, so both checkouts transition pending → `jps-inquiry`.
    - `src/Internal/Admin/Orders/ListTable.php:575-586` and `includes/admin/list-tables/class-wc-admin-list-table-orders.php:646-655` list only registered statuses; My Account's orders query does the same by default.
    - `src/StoreApi/Schemas/V1/ProductSchema.php:952-972`: Store API prices have no filter.
    - `src/StoreApi/Utilities/CartController.php:337-345,1232-1237` apply `woocommerce_add_to_cart_validation` and `woocommerce_add_cart_item_data`; `src/StoreApi/Routes/V1/CartAddItem.php:116-127` passes `( $data, $request )` to `woocommerce_store_api_add_to_cart_data`.
    - `src/Blocks/BlockTypes/MiniCart.php` has no `integration_registry_identifier`.
- Germanized source (wp-env after Phase 0):
  - `includes/class-wc-gzd-legal-checkbox-manager.php` (`remove`, `get_checkboxes`, action `woocommerce_gzd_registered_legal_checkboxes`).
  - `src/Shopmark.php` (`remove`, `is_enabled`, option naming).

## Verification

Prerequisites: run in the repo root.
```
composer install && npm install
npm run env:start
npm run i18n:pot && npm run i18n:json
```
Fill the `.po` files before `i18n:json`. The seed compiles `.mo` files.

Per phase:
- `composer lint`
- `composer test` → `OK (…)`, exit 0
- `npm run test:integration` → `OK (N tests, M assertions)`, exit 0
- `npm run test:e2e:run -- --retries 0` → all passed

Integration tests (`tests/integration/test-*.php`, `WP_UnitTestCase`) run with all modules active. In the `muplugins_loaded` callback of `tests/integration/bootstrap.php`, before requiring the plugin, add `pre_option_jps_cs_module_quote`, `pre_option_jps_cs_module_tabs` and `pre_option_jps_cs_module_length` filters returning `'yes'`. Add each filter in the phase that adds its module.

**Quote — `test-quote-mode.php`:**
- A simple product with an empty price is purchasable: `$product->is_purchasable()` is true.
- `$product->get_price_html()` returns `''`.
- `apply_filters( 'woocommerce_structured_data_product', [ 'offers' => [ 1 ] ], $product )` has no `offers`.
- A variation with an empty price is visible and purchasable.

**Quote — `test-inquiries.php`:**
- Create `wc_create_order()` with meta `_jps_cs_inquiry = yes`, a product line and status `pending`.
- `$order->payment_complete()` → status `jps-inquiry`, `get_date_paid()` is null, stock unchanged (`manage_stock` on, qty 5 stays 5).
- `tests_retrieve_phpmailer_instance()` holds 2 mails: to admin with subject containing "New inquiry", and to billing email. Neither body contains the price `12,34` or `12.34` (set the regular price to 12.34).
- `apply_filters( 'woocommerce_get_order_item_totals', [ 'x' => 1 ], $order, '' )` returns `[]`.
- `wc_get_order_statuses()` contains `wc-jps-inquiry`.
- On the frontend, `$order->get_formatted_order_total()` returns `''`.

**Tabs — `test-product-tabs.php`:**
- Tabs with scopes all, categories (parent category assigned on the tab, child category on the product) and products.
- A hidden-tab product meta, and a replacing product tab.
- Assert on `array_keys( apply_filters( 'woocommerce_product_tabs', [] ) )` with `$GLOBALS['product']` set: the exact expected keys and order.
- An empty tab (only whitespace paragraph) is absent.

**Access — `test-access.php`:**
- `do_blocks( '<!-- wp:jp-catalog-suite/access --><p>SECRET</p><!-- /wp:jp-catalog-suite/access -->' )`:
  - As a guest it contains the notice and not `SECRET`, and the login link contains `redirect_to`.
  - After `wp_set_current_user( customer )` it contains `SECRET`.
  - With `add_filter( 'jps_cs_can_access_protected', '__return_false' )` it contains the no-permission text.

**Files — `test-protected-files.php`:**
- `wp_upload_bits`-style upload through `wp_handle_upload` of `part.stp` lands under `/jps-cs-protected/`.
- `.htaccess` exists there and contains `Require all denied`.
- `wp_get_attachment_url()` contains `jps_cs_file=`.
- Endpoint as guest: set `$_GET['jps_cs_file']`, run `do_action( 'template_redirect' )`; a `wp_redirect` filter throws the redirect exception, whose URL contains `wp-login.php`.
- As a customer, `jps_cs_before_file_delivery` fires: a test action throws a sentinel exception.
- With logging on, one row is in the log table.
- A `jps_cs_can_access_protected` filter records its context: a download with `&product=<id>` passes no `product_id`.
- `wp_delete_user( customer )` leaves no log rows for that user; the registered eraser deletes a user's rows.
- An existing non-protected attachment → a redirect exception whose URL is the attachment's uploads URL. A missing ID → `WPDieException` with 404.

**Length — `test-variable-length.php`:**
- `WC()->cart->add_to_cart( $id, 1 )` with `$_POST['jps_cs_length'] = '120'`, then `'150'` → 2 cart lines, and `woocommerce_get_item_data` shows "120 cm".
- `'5'` → add returns false and an error notice is present.
- Validation boundaries through `WC()->cart->add_to_cart()` (the `woocommerce_add_to_cart_validation` hook, no unit seam) on a product with min 10, max 100, step 0.5: 10, 100 and 10.5 are added; 9.99, 100.01 and 10.3 are rejected. With step 0.2, 10.6 is added (float tolerance).
- Pricing on with price 2 and length 120 → after `calculate_totals()` the line total is 240.

**E2E** (`tests/e2e/*.spec.ts`; seed creates the fixtures):
- **Fixtures.**
  - Products: "Quote Profile" (simple, no price, SKU QP-1); "Priced Bolt" (simple, price 9.99); "Length Rail" (variable: attribute Color Red/Blue, length enabled 10–300 cm step 1, variation Blue max 200).
  - A global tab "Downloads": access block with a `part.stp` file list (protected) plus public text "Public info".
  - Classic pages "Classic Cart" `[woocommerce_cart]` and "Classic Checkout" `[woocommerce_checkout]`.
  - Options: all modules on.
  - Use a `wpCli( cmd )` helper (`execSync` of `WP_ENV_HOME=./.wp-env npx --no-install wp-env run cli wp …`) to switch the cart/checkout page IDs for the classic run and `WPLANG` for the German run.
- **`quote-blocks.spec.ts`** (guest):
  - Shop and product pages show no `€` and the button "Add to inquiry".
  - Add "Quote Profile" and "Priced Bolt" → the cart page body text has no `€` and no "zahlungspflichtig".
  - The proceed button reads "Continue to inquiry".
  - Checkout shows no payment-method section; address fields are hidden.
  - Fill name, email, company, phone; click "Send inquiry" → the confirmation page has no `€`.
  - As admin, the newest order has status "Inquiry".
- **`quote-classic.spec.ts`**: the same flow on the shortcode pages.
- **`quote-de.spec.ts`**: with `WPLANG=de_DE`, the buttons read "Zur Anfrage hinzufügen" and "Anfrage absenden".
- **`quote-germanized.spec.ts`**:
  - Activate Germanized via `POST /wp-json/wp/v2/plugins/woocommerce-germanized/woocommerce-germanized` `{status:'active'}` as admin; deactivate in `afterAll`.
  - The product page has no "inkl. MwSt" / "incl. VAT" and no "Shipping costs" notice.
  - The checkout place-order button reads "Send inquiry".
  - The privacy checkbox is present only if Germanized's privacy checkbox is enabled for checkout; enable it via its option in the test or assert the terms checkbox is absent.
- **`tabs.spec.ts`**:
  - As a guest, the product page tab "Downloads" shows "Public info" and the notice with a login link containing `redirect_to`, and no `part.stp` link.
  - Logged in as `customer1`, the link is visible; requesting it returns 200 with `content-disposition` containing `part.stp`.
  - A direct GET of `/wp-content/uploads/jps-cs-protected/…/part.stp` returns 403.
  - A guest GET of the endpoint returns 302 to `wp-login.php`.
- **`length.spec.ts`**:
  - Select Blue and enter 250 → native validation blocks submit (`input.validity.valid === false`).
  - Enter 120 and add, then 150 and add → the cart shows 2 lines with "120 cm" and "150 cm".
  - Complete the inquiry → the admin order shows "Length: 120 cm".

## Assumptions & contingencies

- **Template-owned files edited locally:** `scripts/build-release.sh`, `phpcs.xml.dist`, `tests/integration/bootstrap.php`. The same changes should be upstreamed to `../wp-plugin-template`; flag this in the final report, don't do it.
- **Germanized hook timing** is unverified. Use the fallbacks named in Phase 1 step 9 when the e2e assertions fail.
- **Blocks address-locale hiding.** If it fails for a field, assert in e2e only what works and record the gap in the README limitations. Don't add DOM hacks.
- **`add-to-cart-with-options`.** If the block submits via JS and drops `jps_cs_length`, set `woocommerce_product_supports` `ajax_add_to_cart` false; if still dropped, document "use the Add to Cart form block for length products".
- **Default protected file extensions** `stp, step, dxf` and MIME defaults are customer-overridable in settings.
