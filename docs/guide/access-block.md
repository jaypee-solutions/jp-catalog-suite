# Access block

Show different tab content to Authorized users and visitors without access. For shop admins and editors.

## Before you start

- WooCommerce and Catalog Suite are active. An administrator enables **Product tabs** at **WooCommerce → Settings → Catalog Suite → General** to create and manage tab content.
- You can edit a Product tab under **Products → Product tabs**.
- By default, an Authorized user is any logged-in user. You can customize the rule with the `jps_cs_can_access_protected` filter.

## Tutorial: add Protected content and Fallback content

1. Open **Products → Product tabs** and edit a published tab, such as `Downloads`.
2. In the block editor, add a paragraph above the Access block: `Download technical information for this product.` This is Public content and is visible to every visitor.
3. Insert the **Access** block inside the tab. In its **Access settings**, leave **Show content** set to **Only with access**.
4. Add a heading and paragraph inside the Access block: `Technical datasheet` and `Datasheet: revision 4, issued 1 October.` This is Protected content.
5. Insert a second **Access** block below it. Set **Show content** to **Only without access**, then add: `Sign in to view the technical datasheet.` This is Fallback content.
6. Keep **Show login notice when hidden** enabled on the first Access block, then update the tab.
7. Visit the product page in a private browser window where you are logged out. Select the tab; then sign in and revisit it.

**What you should see:** both visitors see the Public content. A logged-out visitor sees the Fallback content and, for the first block, the Protected notice instead of Protected content. A logged-in Authorized user sees the Protected content, not the Fallback content.

**Try it:** use `Public intro: Download our product overview.` outside the block, and `Authorized users: Datasheet DS-42, revision 4.` inside an **Only with access** block.

## Tutorial: configure the Protected notice and registration return

1. Open **WooCommerce → Settings → Catalog Suite → Product tabs** and find **Protected notice**.
2. Enter `Please {login} or {register} to view this datasheet.` and save changes.
3. For a registration link, enable **WooCommerce → Settings → Accounts & Privacy → Account creation → On "My account" page**, or WordPress **Settings → General → Membership → Anyone can register**. Sign out and visit a product with Protected content.
4. Select the `log in` link in the notice. After signing in, WordPress returns you to that product. If registration is enabled, the `register` link also carries the product destination: WooCommerce My account registration signs the new customer in and returns them to the product; WordPress registration returns them to the product still signed out until they set a password and log in.
5. If you do not offer registration, Catalog Suite removes the `{register}` placeholder together with the word before it (written for `… or {register} …`), so `Please {login} or {register} to view this datasheet.` becomes “Please log in to view this datasheet.” Place `{register}` directly after a single connecting word such as `or`. The default notice text is used when the setting is empty.

**What you should see:** the notice substitutes `{login}` and, when registration is available, `{register}` with links. The page destination is retained through login or registration.

**Try it:** with registration enabled, set the message to `Access required: {login} or {register}.` and confirm both links point back to the product page; then disable registration and confirm the notice reads “Access required: log in.”

## Settings reference

| Setting | Where | Default | Effect |
|---|---|---|---|
| Product tabs | WooCommerce → Settings → Catalog Suite → General | Off | Enables **Products → Product tabs**, where you add Access blocks to tab content. The Access block and **Protected notice** setting themselves work whether or not the module is on. |
| Show content | Access block → Access settings | Only with access | Selects Protected content or Fallback content for the nested blocks. |
| Show login notice when hidden | Access block → Access settings | On | Displays a Protected notice when an unauthorized visitor's Protected content is hidden. |
| Protected notice | WooCommerce → Settings → Catalog Suite → Product tabs | Empty | Sets the message; empty uses `Please {login} or {register} to view this content.` Tokens create links when available. |

## Good to know

- **Authorized user** means a user granted access by the policy. The default grants access to any logged-in WordPress user; it does not check a particular role. The filter can change this decision.
- The Access block's notice option applies when Protected content is hidden. It does not add a notice for a hidden Fallback content block. When a logged-in user is denied by a customized policy, the notice says they do not have permission.
- Registration links are available when WooCommerce account registration or WordPress registration is enabled. Registration redirects are validated and return to the product when its URL was carried in the link.
- Rendering an Access block on a product page for a logged-in user marks that page uncacheable and sends no-cache headers. Exclude access-content pages from full-page caches; do not let a shared cache serve logged-in output to guests or vice versa.
- For Varnish cookie pass rules and hosting guidance, see [README hosting requirements](../../README.md#hosting-and-privacy).
- Protected files use the same access policy. See [Product tabs](product-tabs.md) and [Protected files](protected-files.md).

## For developers

- `jps_cs_can_access_protected` decides whether a user may access Protected content or a Protected file; the context identifies `product_id` for block rendering or `attachment_id` for file delivery.
- `jps_cs_protected_notice_html` customizes the rendered notice markup.
- See the [README hook reference](../../README.md#hook-reference).
