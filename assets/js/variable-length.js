( function ( $ ) {
	'use strict';

	$( '.variations_form' ).each( function () {
		const form = $( this );
		const input = form.find( 'input[name="jps_cs_length"]' );
		if ( ! input.length ) {
			return;
		}
		let currentVariationId = null;
		const parent = {
			min: input.attr( 'min' ),
			max: input.attr( 'max' ),
			step: input.attr( 'step' ),
			default: input.val(),
		};
		form.on( 'found_variation', function ( event, variation ) {
			if ( currentVariationId === variation.variation_id ) {
				return;
			}
			currentVariationId = variation.variation_id;
			const config = variation.jps_cs_length;
			if ( ! config || ! config.enabled ) {
				return;
			}
			[ 'min', 'max', 'step' ].forEach( function ( key ) {
				input.attr( key, config[ key ] );
			} );
			input.val( config.default );
		} );
		form.on( 'reset_data', function () {
			currentVariationId = null;
			[ 'min', 'max', 'step' ].forEach( function ( key ) {
				input.attr( key, parent[ key ] );
			} );
			input.val( parent.default );
		} );
	} );
}( jQuery ) );
