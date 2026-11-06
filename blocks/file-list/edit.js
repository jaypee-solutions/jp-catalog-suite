( function ( blocks, element, blockEditor, components, i18n, serverSideRender ) {
	const el = element.createElement;
	const __ = i18n.__;
	blocks.registerBlockType( 'jp-catalog-suite/file-list', {
		apiVersion: 3,
		title: __( 'File list', 'jp-catalog-suite' ),
		icon: 'media-document',
		category: 'widgets',
		attributes: { files: { type: 'array', default: [] } },
		edit: function ( props ) {
			return el( element.Fragment, {},
				el( blockEditor.InspectorControls, {},
					el( components.PanelBody, { title: __( 'Files', 'jp-catalog-suite' ) },
						el( components.Button, {
							variant: 'secondary',
							onClick: function () {
								const frame = window.wp.media( { title: __( 'Select files', 'jp-catalog-suite' ), button: { text: __( 'Use selected files', 'jp-catalog-suite' ) }, multiple: true } );
								frame.on( 'select', function () {
									const ids = frame.state().get( 'selection' ).toJSON().map( function ( item ) { return item.id; } );
									props.setAttributes( { files: ids } );
								} );
								frame.open();
							},
							children: __( 'Choose files', 'jp-catalog-suite' ),
						} )
					)
				),
				el( 'div', { className: props.className },
					el( 'strong', {}, __( 'File list', 'jp-catalog-suite' ) ),
					el( serverSideRender, { block: 'jp-catalog-suite/file-list', attributes: props.attributes } )
				)
			);
		},
		save: function () { return null; },
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n, window.wp.serverSideRender );
