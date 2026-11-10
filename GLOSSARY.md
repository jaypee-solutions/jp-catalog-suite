# Catalog Suite

A WooCommerce extension for B2B catalog shops: customers send inquiries instead of orders, product tabs carry public and protected content, and products can be sold by a length the customer enters.

## Language

### Quote mode

**Quote mode**:
The shop mode in which customers send inquiries instead of buying; prices, payment, shipping and taxes are absent.
_Avoid_: catalog mode, inquiry mode

**Inquiry**:
A non-binding request for an offer that a customer submits in quote mode; legally not an order (DE: Anfrage).
_Avoid_: quote, order, request for quote, Bestellung

**Quote**:
The seller's priced offer in reply to an inquiry (DE: Angebot); the shop manager sends it from the inquiry, and a logged-in customer can accept it in My Account.
_Avoid_: using it for the customer's inquiry

**Quote acceptance**:
The customer's confirmation of a sent Quote; the inquiry becomes an on-hold order.
_Avoid_: order confirmation

**Inquiry basket**:
The cart while quote mode is active (DE: Anfragekorb).
_Avoid_: cart, Warenkorb (in customer-facing text)

### Product tabs

**Global tab**:
A tab shown on all products, or on the products of one category and its subcategories.
_Avoid_: shared tab, default tab

**Product tab**:
A tab belonging to one product.
_Avoid_: custom tab, local tab

**Core tab**:
One of WooCommerce's built-in tabs: Description, Additional information, Reviews.
_Avoid_: default tab, standard tab, Woo tab

**Variation tab**:
A tab belonging to one variation of a variable product; shown while that variation is selected.
_Avoid_: variant tab

**Replace**:
A product tab taking the place of one global tab on its product, or a variation tab taking the place of one tab shown on its parent product while the variation is selected.
_Avoid_: override, überschreiben

**Hide**:
Suppressing one global tab on a single product.
_Avoid_: disable, remove

### Access

**Authorized user**:
A visitor the access rule grants protected content and protected files; by default any logged-in user.
_Avoid_: member, customer, B2B user

**Protected content**:
Tab content shown only to authorized users.
_Avoid_: private content, members-only content

**Public content**:
Tab content outside any access rule; every visitor sees it.
_Avoid_: guest content, open content

**Fallback content**:
Tab content shown only to visitors without access, in place of protected content.
_Avoid_: public content, teaser, guest-only content

**Protected notice**:
The configurable message shown to visitors without access where protected content is hidden, with login and registration links.
_Avoid_: login notice, paywall message

**Protected file**:
A media-library file that only authorized users can download, and only through the plugin.
_Avoid_: private file, secure download

**Download log**:
The record of which authorized user downloaded which protected file, and when.
_Avoid_: access log, download history

### Variable length

**Variable length**:
A product option letting the customer enter the length of each piece.
_Avoid_: length product, custom length, length variation

**Length**:
The customer-entered size of one piece, in the product's length unit.
_Avoid_: size, dimension

**Quantity**:
The number of pieces, each of the entered length.
_Avoid_: total length, meters

**Price per length unit**:
Optional pricing in which one piece costs the unit price times its length.
_Avoid_: length surcharge, meter price
