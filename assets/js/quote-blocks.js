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
	};
	wp.hooks.addFilter( 'i18n.gettext_woocommerce', 'jp-catalog-suite/labels', ( translation, text ) => map[ text ] || translation );
	if ( wc.blocksCheckout ) {
		wc.blocksCheckout.registerCheckoutFilters( 'jp-catalog-suite', {
			placeOrderButtonLabel: () => labels.place_order,
			proceedToCheckoutButtonLabel: () => labels.proceed,
		} );
	}
}( window.wp, window.wc ) );
