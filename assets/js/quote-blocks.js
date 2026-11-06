( function () {
	if ( ! window.wc || ! window.wc.blocksCheckout ) {
		return;
	}

	window.wc.blocksCheckout.registerCheckoutFilters( 'jp-catalog-suite', {
		cartItemPrice: function () {
			return '';
		},
		subtotalPriceFormat: function () {
			return '';
		},
	} );
} )();
