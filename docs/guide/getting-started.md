# Getting started

Install Catalog Suite, switch on the modules you need and keep the plugin updated. For shop admins.

## Before you start

- WordPress 6.4+, WooCommerce 8.0+, PHP 8.0+.
- WooCommerce must be active. Without it the plugin shows the notice “JayPee Solutions Catalog Suite for WooCommerce requires WooCommerce.” and loads no features; only the update settings page stays available.
- A user with the `manage_options` capability (administrator).

## Tutorial: install the plugin

1. Download `jp-catalog-suite-v<version>.zip` from the [GitHub releases](https://github.com/jaypee-solutions/jp-catalog-suite/releases) page. The repository is private, so sign in to GitHub first.
2. In wp-admin open **Plugins → Add Plugin → Upload Plugin** (WordPress 6.x: **Plugins → Add New Plugin**), choose the ZIP and select **Install Now**.
3. Select **Activate Plugin**.

You should see **Catalog Suite** as a tab under **WooCommerce → Settings**.

## Tutorial: enable the modules

Each module stays off until you enable it. Protected files, the Access and File list blocks, and the Inquiry status of existing Inquiries are always active.

1. Open **WooCommerce → Settings → Catalog Suite → General**.
2. Tick the modules you want:
   - **Quote mode** turns the shop into an Inquiry shop: [Quote mode](quote-mode.md), [checkout fields and emails](inquiry-checkout-and-emails.md), [Germanized](germanized.md).
   - **Product tabs** turns on Global tabs and Product tabs (**Products → Product tabs**), the product data **Tabs** panel, the Core tab settings, and the **WooCommerce → Download log** page and recording (recording also needs **Enable download log**): [Product tabs](product-tabs.md), [Protected files](protected-files.md), [Download log](download-log.md). Protected files work whether or not this module is on: the Protected files settings, automatic protection of uploads, the protected-file endpoint, the Access block and the File list block stay active, so switching the module off never exposes protected content: [Access block](access-block.md).
   - **Variable length** lets customers enter a length: [Variable length](variable-length.md).
3. Select **Save changes**.

All three module sections (Quote mode, Product tabs, Variable length) are listed next to General at all times; open the section of an enabled module to configure it.

## Tutorial: receive updates from the private repository

The plugin checks the private GitHub repository for new releases. It needs a read-only token to do so.

1. In GitHub create a token with read access to this repository: a classic token needs the `repo` scope; a fine-grained token needs this repository with **Contents: Read-only**.
2. In wp-admin open **Settings → JayPee Solutions Catalog Suite for WooCommerce Updates**.
3. Paste the token into **GitHub Token** and select **Test token**. The test checks the token against the repository without saving it.
4. Select **Save**. The token is not shown again after saving; saving an empty field keeps the stored token.

New releases then appear under **Dashboard → Updates** and on the Plugins screen like any other plugin update.

## Settings reference

| Setting | Where | Default | Effect |
|---|---|---|---|
| Quote mode / Product tabs / Variable length | WooCommerce → Settings → Catalog Suite → General | off | Enables the module. |
| Delete tabs, protected files and the download log when the plugin is deleted | General | off | On deleting the plugin, also removes content data. Orders are never deleted. |
| GitHub Token | Settings → … Updates | empty | Authenticates update checks against the private repository. |

## Good to know

- **Uninstall.** Deleting the plugin always removes plugin options, the GitHub token and the scheduled purge job. With the deletion setting on it also removes Catalog Suite tabs, protected files, the download log and the product data the plugin added. Orders are never deleted. Inquiries keep their status and disappear from order lists once the plugin is gone, so bulk-change their status first if you want to keep seeing them.
- **Multisite.** Uninstall handles single sites only.
- **Multilingual sites.** See [Multilingual sites](../../README.md#multilingual-sites).
- **Hosting.** Before enabling protected files, read the [hosting runbook](../../.okf/references/hosting-requirements.md).
