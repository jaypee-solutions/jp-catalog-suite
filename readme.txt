=== JayPee Solutions Catalog Suite for WooCommerce ===
Contributors: jaypeesolutions
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 0.5.5
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 8.0

JayPee Solutions Catalog Suite for WooCommerce is a WordPress plugin.

== Description ==

The suite adds price-free Inquiries, priced Quotes, configurable checkout fields and emails, global and product-specific tabs, protected content/files with access controls, variable-length products and optional length-based pricing. Administrators configure modules at WooCommerce → Settings → Catalog Suite.

== Installation ==

1. Upload the plugin ZIP under Plugins → Add New → Upload Plugin.
2. Activate the plugin.
3. WooCommerce must be active; the plugin stays dormant without it.

== Changelog ==

= 0.5.5 =
* Fix: the core File block of a protected file shows its link and download button only to visitors with access; everyone else sees the file name and "login required".

= 0.5.4 =
* Fix: uploading a protected file (STL, STEP, ...) in the block editor completes instead of staying on "Uploading".

= 0.5.3 =
* Fix: sites that saved the Protected files settings before the 3D formats existed can upload STL and the other 3D formats; the missing types are added to the saved settings once.

= 0.5.2 =
* Fix: protected files (STL, STEP, DXF and the other 3D formats) upload in the Media Library again instead of failing with "The web server cannot process this file".

= 0.5.1 =
* Fix: Quote mode leaves no cart wording on the storefront. Variable products get the Add to inquiry button, and other WooCommerce texts that say cart or Warenkorb use the Inquiry basket label.

= 0.5.0 =
* Variable length: the length field validates while typing with an inline message, looks like the variation select, and sits in the variations table on variable products.
* Protected files: STL, OBJ, 3MF, PLY, glTF, FBX, Collada, IGES and 3DS are allowed and protected by default on new installs; existing sites add the extensions in the settings.
* Fix: STL and other configured CAD types are no longer rejected on upload.
* Fix: the Quote mode label on the mini-cart checkout button works with WooCommerce 11.3.

= 0.4.1 =
* Fix: the Variable length settings are reachable on variable products when WooCommerce hides the General tab.

= 0.4.0 =
* Variation tabs: variations own tabs, shown while the variation is selected and optionally replacing a parent tab.

= 0.3.0 =
* Quotes: send a priced Quote from an Inquiry; customers accept it in My Account.
* Product tabs are managed on the product, category and settings screens.
* Inquiry confirmation page uses Inquiry wording.
* Variable length: default minimum and maximum are validated as a pair.
* User guide with a tutorial for every feature.

= 0.2.0 =
* Quote mode with price-free Inquiries, emails and configurable checkout fields.
* Global and product-specific tabs, core tab controls, protected content and files, opt-in download log.
* Variable-length products with variation overrides, Store API support and optional length-based pricing.
* Germanized compatibility.

= 0.1.0 =
* Initial release.
