( function ( $ ) {
	'use strict';

	const ACCORDION_ITEM = '.wp-block-woocommerce-accordion-item, .wp-block-accordion-item';
	const TABS_WRAPPER = '.wc-tabs-wrapper, .woocommerce-tabs';

	// The inline display is needed because WooCommerce and theme CSS set `display` on tab items, overriding `hidden`.
	function set_shown( element, shown ) {
		element.hidden = ! shown;
		element.style.display = shown ? '' : 'none';
	}

	function tab_title( marker ) {
		return document.getElementById( 'tab-title-' + marker.dataset.jpsCsTab );
	}

	function toggle( marker, shown ) {
		const item = marker.closest( ACCORDION_ITEM );
		if ( item ) {
			set_shown( item, shown );
			return;
		}
		const panel = marker.closest( '.wc-tab' );
		if ( ! panel ) {
			return;
		}
		const title = tab_title( marker );
		if ( title ) {
			set_shown( title, shown );
		}
		if ( ! shown ) {
			panel.hidden = true;
			panel.style.display = 'none';
			return;
		}
		// WooCommerce shows the active panel; an inactive one stays hidden until its tab is clicked.
		panel.hidden = false;
		if ( title && title.classList.contains( 'active' ) ) {
			panel.style.display = '';
		}
	}

	function settle_classic_tabs( markers ) {
		const wrappers = new Set();
		markers.forEach( function ( marker ) {
			const wrapper = marker.closest( TABS_WRAPPER );
			if ( wrapper ) {
				wrappers.add( wrapper );
			}
		} );
		wrappers.forEach( function ( wrapper ) {
			const active = wrapper.querySelector( '.wc-tabs li.active' );
			if ( active && ! active.hidden ) {
				return;
			}
			const active_key = active ? active.id.replace( 'tab-title-', '' ) : '';
			let next = null;
			markers.forEach( function ( marker ) {
				const title = tab_title( marker );
				if ( ! next && title && ! title.hidden && active_key && marker.dataset.jpsCsReplaces === active_key ) {
					next = title;
				}
			} );
			if ( ! next ) {
				next = wrapper.querySelector( '.wc-tabs li:not([hidden])' );
			}
			if ( next ) {
				$( next ).find( 'a' ).trigger( 'click' );
			}
		} );
	}

	function apply( product_id, variation_id ) {
		const selected = String( variation_id );
		const markers = Array.prototype.slice.call(
			document.querySelectorAll( '[data-jps-cs-variation][data-jps-cs-product="' + String( product_id ) + '"]' )
		);
		const replaced_keys = {};
		const shown_keys = {};
		markers.forEach( function ( marker ) {
			const shown = marker.dataset.jpsCsVariation === selected;
			toggle( marker, shown );
			const key = marker.dataset.jpsCsReplaces;
			if ( key ) {
				replaced_keys[ key ] = true;
				if ( shown ) {
					shown_keys[ key ] = true;
				}
			}
		} );
		Object.keys( replaced_keys ).forEach( function ( key ) {
			document.querySelectorAll( '[data-jps-cs-tab="' + key + '"]:not([data-jps-cs-variation])' ).forEach( function ( marker ) {
				toggle( marker, ! shown_keys[ key ] );
			} );
		} );
		settle_classic_tabs( markers );
	}

	$( function () {
		const product_ids = {};
		document.querySelectorAll( '[data-jps-cs-variation][data-jps-cs-product]' ).forEach( function ( marker ) {
			product_ids[ marker.dataset.jpsCsProduct ] = true;
		} );
		Object.keys( product_ids ).forEach( function ( product_id ) {
			apply( product_id, 0 );
		} );
	} );

	$( document.body ).on( 'found_variation', '.variations_form', function ( event, variation ) {
		apply( $( this ).data( 'product_id' ), variation.variation_id );
	} );
	$( document.body ).on( 'reset_data hide_variation', '.variations_form', function () {
		apply( $( this ).data( 'product_id' ), 0 );
	} );
}( jQuery ) );
