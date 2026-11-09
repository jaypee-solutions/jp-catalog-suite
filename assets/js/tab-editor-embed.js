( function ( wp ) {
	'use strict';

	if ( window.self === window.top ) {
		return;
	}
	document.documentElement.classList.add( 'jps-cs-embedded' );

	// Priority 20 runs after the metabox save of edit-post (priority 10), so the owner list sees the final meta.
	wp.hooks.addAction(
		'editor.savePost',
		'jp-catalog-suite/tab-manager',
		function ( post, options ) {
			if ( options && options.isAutosave ) {
				return;
			}
			window.parent.postMessage( { type: 'jps-cs-tab-saved', postId: post.id }, window.location.origin );
		},
		20
	);
}( window.wp ) );
