( function () {
	'use strict';

	var HIDDEN_FIELD = 'input[name="_jps_cs_hidden_tabs[]"]';

	function manager_of( element ) {
		return element.closest( '.jps-cs-tab-manager' );
	}

	function refresh( manager ) {
		var checked = Array.prototype.map.call(
			manager.querySelectorAll( HIDDEN_FIELD + ':checked' ),
			function ( input ) {
				return input.value;
			}
		);
		var query = new URLSearchParams( {
			action: 'jps_cs_tab_list',
			scope: manager.dataset.scope,
			target: manager.dataset.target,
			nonce: manager.dataset.nonce,
		} );
		return fetch( window.ajaxurl + '?' + query.toString(), { credentials: 'same-origin' } )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( payload ) {
				if ( ! payload.success ) {
					return;
				}
				manager.querySelector( '.jps-cs-tab-manager__list' ).innerHTML = payload.data.html;
				manager.querySelectorAll( HIDDEN_FIELD ).forEach( function ( input ) {
					input.checked = checked.indexOf( input.value ) !== -1;
				} );
			} )
			.catch( function () {} );
	}

	function trash( manager, tabId ) {
		var body = new FormData();
		body.append( 'action', 'jps_cs_trash_tab' );
		body.append( 'nonce', manager.dataset.nonce );
		body.append( 'tab_id', tabId );
		return fetch( window.ajaxurl, { method: 'POST', body: body, credentials: 'same-origin' } )
			.catch( function () {} )
			.then( function () {
				return refresh( manager );
			} );
	}

	document.addEventListener( 'click', function ( event ) {
		var open = event.target.closest( '.jps-cs-tab-open' );
		if ( open ) {
			event.preventDefault();
			var manager = manager_of( open );
			manager.querySelector( '.jps-cs-tab-dialog__frame' ).src = open.href;
			manager.querySelector( '.jps-cs-tab-dialog' ).showModal();
			return;
		}
		if ( event.target.closest( '.jps-cs-tab-dialog__close' ) ) {
			event.target.closest( '.jps-cs-tab-dialog' ).close();
			return;
		}
		var remove = event.target.closest( '.jps-cs-tab-trash' );
		if ( remove ) {
			var owner = manager_of( remove );
			if ( window.confirm( owner.dataset.confirmTrash ) ) {
				trash( owner, remove.dataset.tab );
			}
		}
	} );

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.jps-cs-tab-dialog' ).forEach( function ( dialog ) {
			dialog.addEventListener( 'close', function () {
				dialog.querySelector( '.jps-cs-tab-dialog__frame' ).src = 'about:blank';
				refresh( manager_of( dialog ) );
			} );
		} );
	} );

	window.addEventListener( 'message', function ( event ) {
		if ( event.origin !== window.location.origin || ! event.data || event.data.type !== 'jps-cs-tab-saved' ) {
			return;
		}
		document.querySelectorAll( '.jps-cs-tab-manager' ).forEach( function ( manager ) {
			var frame = manager.querySelector( '.jps-cs-tab-dialog__frame' );
			if ( frame.contentWindow === event.source ) {
				refresh( manager );
			}
		} );
	} );
}() );
