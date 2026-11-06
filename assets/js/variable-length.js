( function ( $ ) {
	'use strict';

	$( '.variations_form' ).each( function () {
		const form = $( this );
		const input = form.find( 'input[name="jps_cs_length"]' );
		if ( ! input.length ) {
			return;
		}
		const parent = {
			min: input.attr( 'min' ),
			max: input.attr( 'max' ),
			step: input.attr( 'step' ),
			default: input.val(),
		};
		form.on( 'found_variation', function ( event, variation ) {
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
			[ 'min', 'max', 'step' ].forEach( function ( key ) {
				input.attr( key, parent[ key ] );
			} );
			input.val( parent.default );
		} );
	} );
}( jQuery ) );
