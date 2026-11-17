( function ( wp, wc ) {
	const settings = wc.wcSettings.getSetting( 'jp-catalog-suite_data', {} );
	const labels = settings.labels || window.jpsCsQuoteLabels || {};
	const map = {
		Cart: labels.cart,
		'View cart': labels.cart,
		'Your cart': labels.cart,
		'Proceed to checkout': labels.proceed,
		'Proceed to Checkout': labels.proceed,
		'Go to checkout': labels.proceed,
		'Place order': labels.place_order,
		'Place Order': labels.place_order,
		'Add to cart': labels.add_to_cart,
	};
	// Mirrors Quote_Mode::rename_cart(): no cart wording is left in any other WooCommerce block string.
	const renameCart = ( text ) => {
		if ( ! labels.cart || typeof text !== 'string' ) {
			return text;
		}
		const lowerLabel = labels.cart.charAt( 0 ).toLowerCase() + labels.cart.slice( 1 );
		return text.replace( /Warenkorb/g, labels.cart ).replace( /\bcarts?\b/gi, ( match ) => ( match === match.toLowerCase() ? lowerLabel : labels.cart ) );
	};
	wp.hooks.addFilter( 'i18n.gettext_woocommerce', 'jp-catalog-suite/labels', ( translation, text ) => map[ text ] || renameCart( translation ) );
	wp.hooks.addFilter( 'i18n.ngettext_woocommerce', 'jp-catalog-suite/labels', ( translation ) => renameCart( translation ) );
	if ( wc.blocksCheckout ) {
		wc.blocksCheckout.registerCheckoutFilters( 'jp-catalog-suite', {
			placeOrderButtonLabel: () => labels.place_order,
			proceedToCheckoutButtonLabel: () => labels.proceed,
		} );
	}
}( window.wp, window.wc ) );
