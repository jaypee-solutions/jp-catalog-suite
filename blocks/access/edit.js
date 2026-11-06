( function ( blocks, element, blockEditor, components, i18n ) {
	const el = element.createElement;
	const __ = i18n.__;
	blocks.registerBlockType( 'jp-catalog-suite/access', {
		apiVersion: 3,
		title: __( 'Access', 'jp-catalog-suite' ),
		icon: 'lock',
		category: 'widgets',
		attributes: {
			mode: { type: 'string', default: 'only-with-access' },
			showNotice: { type: 'boolean', default: true },
		},
		edit: function ( props ) {
			return el( element.Fragment, {},
				el( blockEditor.InspectorControls, {},
					el( components.PanelBody, { title: __( 'Access settings', 'jp-catalog-suite' ) },
						el( components.SelectControl, {
							label: __( 'Show content', 'jp-catalog-suite' ),
							value: props.attributes.mode,
							options: [
								{ label: __( 'Only with access', 'jp-catalog-suite' ), value: 'only-with-access' },
								{ label: __( 'Only without access', 'jp-catalog-suite' ), value: 'only-without-access' },
							],
							onChange: function ( mode ) { props.setAttributes( { mode: mode } ); },
						} ),
						el( components.ToggleControl, {
							label: __( 'Show login notice when hidden', 'jp-catalog-suite' ),
							checked: props.attributes.showNotice,
							onChange: function ( showNotice ) { props.setAttributes( { showNotice: showNotice } ); },
						} )
					)
				),
				el( 'div', { className: props.className },
					el( 'strong', {}, __( 'Access', 'jp-catalog-suite' ) ),
					el( blockEditor.InnerBlocks, {} )
				)
			);
		},
		save: function () { return el( blockEditor.InnerBlocks.Content ); },
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n );
