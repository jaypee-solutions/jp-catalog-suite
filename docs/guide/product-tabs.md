# Product tabs

Add reusable product information, tailor it for products, and control WooCommerce Core tabs. For shop admins and editors.

## Before you start

- WooCommerce and Catalog Suite are active.
- An administrator enables **Product tabs** at **WooCommerce → Settings → Catalog Suite → General** and saves.
- Product tabs use WooCommerce's product capabilities: Administrators and Shop managers can create and edit them; the WordPress Editor role cannot unless granted product capabilities such as `edit_products`. Product-specific controls require permission to edit the product.

## Tutorial: publish a Global tab for all products

1. Open **Products → Product tabs → Add product tab**.
2. Enter `Shipping details` as the title.
3. In the **Tab settings** panel, set **Scope** to **All products**. Leave **Priority** empty; an empty priority sorts as 50.
4. Add a paragraph in the block editor: `Dispatch usually takes two business days.`
5. Select **Publish**.
6. Open a published product page and select the `Shipping details` tab.

**What you should see:** the tab appears on every product page, ordered by priority. A tab whose content is empty or whitespace-only is omitted.

**Try it:** use the title `Packaging` and content `Packed in recyclable materials.`; confirm it appears on another product page too.

## Tutorial: target categories and set priority

1. Under **Products → Product tabs**, add a Global tab titled `Trade specifications`.
2. In **Tab settings**, set **Scope** to **Categories**.
3. Choose `Bolts` in **Categories**. A product assigned to a child category also matches a selected parent category.
4. Set **Priority** to `25` and add `Dimensions and material grades are available on request.` in the block editor.
5. Publish, then open a product assigned to `Bolts` or one of its descendants.

**What you should see:** `Trade specifications` appears on matching products but not unrelated products, before tabs with higher numeric priorities.

**Try it:** set priority to `70`; it follows tabs with priorities below 70.

## Tutorial: create a Product tab for selected products

1. Open **Products → Product tabs → Add product tab**.
2. Enter `Datasheet summary` as the title.
3. Under **Tab settings**, set **Scope** to **Products** and choose `Tab Demo` in **Products**.
4. Leave **Replace global tab** set to **None** and **Priority** empty. Add `Specifications for this product.` in the editor, then publish.
5. Open `Tab Demo` on the storefront and then open a different product.

**What you should see:** the tab appears only on `Tab Demo`. Products-scope tabs are not listed as global tabs in a product's **Tabs** panel.

**Try it:** add `Customer-specific notes` to the Products scope and select `Tab Demo` and one other product.

## Tutorial: Replace or Hide one Global tab

To replace a Global tab on chosen products:

1. Create and publish a Product tab with **Scope** set to **Products** and select the intended product in **Products**.
2. In **Replace global tab**, choose the applicable Global tab, for example `Shipping details`.
3. Leave **Priority** empty to inherit the replaced tab's priority, or enter an explicit number; publish.
4. Visit that product page. The Product tab takes the Global tab's place there; it remains unchanged on other products.

To Hide a Global tab on just one product:

1. Edit that product and open **Product data → Tabs**.
2. Tick **Hide on this product** beside the applicable Global tab.
3. Update the product and refresh its storefront page.

**What you should see:** the replacement appears in place of the selected Global tab only on assigned products. A hidden Global tab is absent only on the product where you hid it.

**Try it:** edit `Datasheet summary` (the Products-scope tab on `Tab Demo`), set **Replace global tab** to `Shipping details`, and update it; `Tab Demo` now shows `Datasheet summary` where `Shipping details` was. Then Hide `Shipping details` on a different product.

## Tutorial: configure Core tabs shop-wide

1. Open **WooCommerce → Settings → Catalog Suite → Product tabs**.
2. Find the **Description**, **Additional information**, or **Reviews** settings.
3. Enter `Overview` in **Title** for Description, set **Priority** to `5`, and leave **Hide** unticked.
4. Save changes and open a product page.
5. To hide a Core tab instead, return to this section, tick its **Hide** checkbox, and save.

**What you should see:** the Description Core tab is titled `Overview` and appears according to priority. Hidden Core tabs are absent across the shop. Empty titles and priorities preserve WooCommerce's translated title and ordering; Core tabs cannot be replaced by Product tabs or configured per product.

**Try it:** set Reviews priority to `80` and verify its position relative to other tabs.

## Settings reference

| Setting | Where | Default | Effect |
|---|---|---|---|
| Product tabs | WooCommerce → Settings → Catalog Suite → General | Off | Enables Global tabs, Product tabs, the product **Tabs** panel and the Core tab settings. The **Product tabs** settings section is always visible, but its tab settings take effect only while the module is on. |
| Scope | Product tab → Tab settings | All products | Applies to all products, chosen categories, or selected products. |
| Categories | Product tab → Tab settings | None | Chooses categories; matching includes descendant categories. |
| Products | Product tab → Tab settings | None | Chooses products for a Products-scope tab. |
| Replace global tab | Product tab → Tab settings | None | Replaces an applicable Global tab on the tab's selected products. Takes effect only when **Scope** is **Products**; ignored for All products and Categories scopes. |
| Priority | Product tab → Tab settings | Empty; effective default 50 | Lower numbers sort first; replacement inherits the replaced tab's priority when left empty. |
| Hide on this product | Product data → Tabs | Off | Hides an applicable Global tab for this product. |
| Title / Priority / Hide | WooCommerce → Settings → Catalog Suite → Product tabs, under each Core tab | Empty / empty / off | Changes Core tab title/order or hides it site-wide. |

## Good to know

- A Product tab with no visible text or media does not render. Product tabs and Core tabs are sorted together by numeric priority.
- A Product tab can replace only an applicable Global tab (all-products or category scope), not another Product tab or a Core tab.
- Global tabs are WordPress content. WPML configuration includes Core tab title options; on multilingual sites, translate and assign Global tabs using your multilingual plugin's normal content workflow. Polylang can translate content and standard strings through its usual workflow. See [Multilingual sites](../../README.md#multilingual-sites).
- The Product tabs REST route is restricted to logged-in users with `edit_posts` capability.
- For access-controlled tab content, see [Access block](access-block.md). For file delivery, see [Protected files](protected-files.md).

## For developers

- `jps_cs_tab_content` adjusts the rendered content of each Global tab and Product tab.
- `jps_cs_product_tabs` adjusts the product tab list.
- See the [README hook reference](../../README.md#hook-reference).
