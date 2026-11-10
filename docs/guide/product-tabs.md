# Product tabs

Add shop-wide, category, product or variation tabs, replace or hide them per product, and control WooCommerce Core tabs. For shop admins and editors.

## Before you start

- WooCommerce and Catalog Suite are active.
- An administrator enables **Product tabs** at **WooCommerce → Settings → Catalog Suite → General** and saves.
- Product tabs use WooCommerce's product capabilities: Administrators and Shop managers can create and edit them; the WordPress Editor role cannot unless granted product capabilities such as `edit_products`. Product-specific controls require permission to edit the product.

## How tabs are managed

Each tab has exactly one owner and is managed where it applies:

| Owner | Where you manage it | Shown on |
|---|---|---|
| Shop-wide | **WooCommerce → Settings → Catalog Suite → Product tabs → Shop-wide tabs** | Every product |
| Category | The **Tabs** field on the product category edit screen | Products in that category and its subcategories |
| Product | **Product data → Tabs** on the product edit screen | That product only |
| Variation | The **Tabs** field in the variation's panel under **Product data → Variations** | That variation, while it is selected |

**Add tab**, **Edit** and **Replace** open the block editor in a dialog; **Close** returns to the list, which refreshes after saving. **Delete** moves the tab to the trash. Tabs are also deleted with their product or category. The old **Products → Product tabs** menu no longer exists.

## Tutorial: publish a shop-wide tab for all products

1. Open **WooCommerce → Settings → Catalog Suite → Product tabs** and find **Shop-wide tabs**.
2. Select **Add tab**.
3. Enter `Shipping details` as the title and add a paragraph in the block editor: `Dispatch usually takes two business days.`
4. In the **Tab settings** panel, leave **Priority** empty; an empty priority sorts as 50. Publish the tab and select **Close**.
5. Open a product page and select the `Shipping details` tab.

**What you should see:** the tab appears on every product page, ordered by priority. A tab whose content is empty or whitespace-only is omitted.

**Try it:** add a second shop-wide tab `Packaging` with the content `Packed in recyclable materials.`

## Tutorial: add a category tab and set priority

1. Open **Products → Categories** and edit `Bolts`.
2. In the **Tabs** field, select **Add tab**, enter `Trade specifications` and add `Dimensions and material grades are available on request.`
3. Set **Priority** to `25`, publish and close the dialog.
4. Open a product assigned to `Bolts` or one of its subcategories.

**What you should see:** `Trade specifications` appears on products in `Bolts` and its subcategories but not on unrelated products, before tabs with higher numeric priorities.

**Try it:** set the priority to `70`; it follows tabs with priorities below 70. To share a tab across several categories, add it to each category; every category owns its own copy.

## Tutorial: add a tab to one product

1. Edit the product `Tab Demo` and open **Product data → Tabs**.
2. Select **Add tab**, enter `Datasheet summary`, add `Specifications for this product.`, leave **Replace global tab** at **None** and **Priority** empty, then publish and close the dialog.
3. Update the product if WordPress asks, open `Tab Demo` on the storefront, then open a different product.

**What you should see:** the tab appears only on `Tab Demo`. The panel lists it with its **Shown on** scope (**This product**).

## Tutorial: Replace or Hide one Global tab

To replace a shop-wide or category tab on one product:

1. Edit the product and open **Product data → Tabs**.
2. Beside the tab you want to replace, for example `Shipping details`, select **Replace**. The dialog opens with a copy of that tab; change the content.
3. Leave **Priority** empty to inherit the replaced tab's priority, or enter a number; publish and close the dialog.
4. Visit that product page. The replacement takes the original's place there; the original stays unchanged on other products.

The list then shows **Replaced by …** beside the original and **Replaces …** beside the replacement.

To Hide a Global tab on just one product:

1. In the same **Tabs** panel, tick **Hide on this product** beside the applicable tab.
2. Update the product and refresh its storefront page.

**What you should see:** the replacement appears in place of the original only on that product. A hidden tab is absent only on the product where you hid it.

**Try it:** replace `Shipping details` on `Tab Demo`, then hide `Shipping details` on a different product.

## Tutorial: add a tab to one variation

1. Edit a variable product, open **Product data → Variations** and expand the variation `Blue`.
2. In its **Tabs** field, select **Add tab**, enter `Blue finish`, add `Powder-coated, RAL 5015.`, leave **Replace tab** at **None** and **Priority** empty, then publish and close the dialog.
3. Open the product page, then select `Blue` and `Red` in turn.

**What you should see:** `Blue finish` appears only while `Blue` is selected and is hidden again after **Clear**. The panel lists the parent product's tabs too, each with a **Replace** link.

**Try it:** select **Replace** beside a parent tab such as `Datasheet summary`. While `Blue` is selected, the variation tab takes its place; with any other selection the original shows.

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
| Product tabs | WooCommerce → Settings → Catalog Suite → General | Off | Enables Global tabs, category and product tabs, the product **Tabs** panel and the Core tab settings. The **Product tabs** settings section is always visible, but its tab settings take effect only while the module is on. |
| Replace global tab | Product tab → Tab settings (product tabs only) | None | Replaces an applicable Global tab on that product. |
| Priority | Tab → Tab settings | Empty; effective default 50 | Lower numbers sort first; replacement inherits the replaced tab's priority when left empty. |
| Hide on this product | Product data → Tabs | Off | Hides an applicable Global tab for this product. |
| Title / Priority / Hide | WooCommerce → Settings → Catalog Suite → Product tabs, under each Core tab | Empty / empty / off | Changes Core tab title/order or hides it site-wide. |

## Good to know

- A Product tab with no visible text or media does not render. Product tabs and Core tabs are sorted together by numeric priority.
- A Product tab can replace only an applicable Global tab (shop-wide or category), not another Product tab or a Core tab. A variation tab can also replace a Product tab of its parent product, but never a Core tab or another variation tab.
- Variation tabs are switched when the shopper selects a variation in the classic variation form. With the "Add to Cart + Options" block they stay hidden; without JavaScript every tab shows. See [ADR 0024](../adr/0024-variation-tabs.md).
- After an upgrade from a version where one tab listed several categories or products, each of them received its own copy once ([ADR 0023](../adr/0023-tabs-managed-on-owners.md)).
- Global tabs are WordPress content. WPML configuration includes Core tab title options; on multilingual sites, translate and assign Global tabs using your multilingual plugin's normal content workflow. Polylang can translate content and standard strings through its usual workflow. See [Multilingual sites](../../README.md#multilingual-sites).
- The Product tabs REST route is restricted to logged-in users with `edit_posts` capability.
- For access-controlled tab content, see [Access block](access-block.md). For file delivery, see [Protected files](protected-files.md).

## For developers

- `jps_cs_tab_content` adjusts the rendered content of each Global tab and Product tab.
- `jps_cs_product_tabs` adjusts the product tab list.
- See the [README hook reference](../../README.md#hook-reference).
