( function ( $ ) {
	'use strict';

	// Input attribute => key in the variation's jps_cs_length data.
	const VARIATION_ATTRIBUTES = {
		min: 'min',
		max: 'max',
		step: 'step',
		'data-range-message': 'range_message',
		'data-step-message': 'step_message',
	};

	function moveIntoVariationsTable( form, field ) {
		const tbody = form.find( 'table.variations > tbody' );
		if ( ! tbody.length || ! field.length ) {
			return;
		}
		// WooCommerce's variable.php has no hook inside the attribute table.
		const row = $( '<tr class="jps-cs-length"><th class="label"></th><td class="value"></td></tr>' );
		row.find( 'th' ).append( field.children( 'label' ) );
		row.find( 'td' ).append( field.children() );
		tbody.append( row );
		field.remove();
	}

	function validationMessage( input ) {
		const validity = input[ 0 ].validity;
		if ( validity.valueMissing || validity.badInput || validity.rangeUnderflow || validity.rangeOverflow ) {
			return input.attr( 'data-range-message' );
		}
		if ( validity.stepMismatch ) {
			return input.attr( 'data-step-message' );
		}
		return '';
	}

	function showValidity( input, message ) {
		// Clear first: a stale custom error would otherwise mask the native state.
		input[ 0 ].setCustomValidity( '' );
		const text = validationMessage( input );
		input[ 0 ].setCustomValidity( text );
		input.attr( 'aria-invalid', text ? 'true' : 'false' );
		message.text( text );
	}

	function followVariations( form, input, message ) {
		let currentVariationId = null;
		const parent = { default: input.val() };
		$.each( VARIATION_ATTRIBUTES, function ( attribute ) {
			parent[ attribute ] = input.attr( attribute );
		} );
		form.on( 'found_variation', function ( event, variation ) {
			if ( currentVariationId === variation.variation_id ) {
				return;
			}
			currentVariationId = variation.variation_id;
			const config = variation.jps_cs_length;
			if ( ! config || ! config.enabled ) {
				return;
			}
			input.val( config.default );
			$.each( VARIATION_ATTRIBUTES, function ( attribute, key ) {
				input.attr( attribute, config[ key ] );
			} );
			showValidity( input, message );
		} );
		form.on( 'reset_data', function () {
			currentVariationId = null;
			$.each( VARIATION_ATTRIBUTES, function ( attribute ) {
				input.attr( attribute, parent[ attribute ] );
			} );
			input.val( parent.default );
			showValidity( input, message );
		} );
	}

	$( 'form.cart' ).each( function () {
		const form = $( this );
		const input = form.find( 'input[name="jps_cs_length"]' );
		if ( ! input.length ) {
			return;
		}
		const message = form.find( '.jps-cs-length-message' );
		moveIntoVariationsTable( form, input.closest( 'p.jps-cs-length' ) );
		input.on( 'input', function () {
			showValidity( input, message );
		} );
		showValidity( input, message );
		if ( form.hasClass( 'variations_form' ) ) {
			followVariations( form, input, message );
		}
	} );
}( jQuery ) );
