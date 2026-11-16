import { test, expect } from './support/fixtures';

const STYLE_PROPERTIES = [
	'height',
	'paddingTop',
	'paddingLeft',
	'fontSize',
	'fontFamily',
	'borderTopWidth',
	'borderTopStyle',
	'borderTopColor',
	'borderTopLeftRadius',
] as const;

test( 'variable product length is a styled variations row with live validation', async ( { page } ) => {
	await page.goto( '/product/length-rail/' );
	const length = page.locator( 'input[name="jps_cs_length"]' );
	const message = page.locator( '#jps_cs_length_message' );
	const colour = page.locator( 'select[name="attribute_colour"]' );

	await expect( page.locator( 'table.variations tr.jps-cs-length th.label label[for="jps_cs_length"]' ) ).toBeVisible();
	await expect( page.locator( 'p.jps-cs-length' ) ).toHaveCount( 0 );

	await colour.selectOption( 'Blue' );
	await expect( length ).toHaveAttribute( 'max', '200' );

	await length.fill( '250' );
	await expect( length ).toHaveAttribute( 'aria-invalid', 'true' );
	await expect( message ).toHaveText( /10.*200.*cm/ );

	await length.fill( '120.5' );
	await expect( length ).toHaveAttribute( 'aria-invalid', 'true' );
	await expect( message ).toHaveText( /1 cm/ );

	await length.fill( '120' );
	await expect( length ).toHaveAttribute( 'aria-invalid', 'false' );
	await expect( message ).toHaveText( '' );
	await expect( message ).toBeHidden();

	const read = ( element: Element, properties: readonly string[] ) => {
		const style = getComputedStyle( element ) as unknown as Record<string, string>;
		const values: Record<string, string> = {};
		for ( const property of properties ) {
			values[ property ] = style[ property ];
		}
		return { values, width: element.getBoundingClientRect().width };
	};
	const inputStyle = await length.evaluate( read, STYLE_PROPERTIES );
	const selectStyle = await colour.evaluate( read, STYLE_PROPERTIES );
	expect( inputStyle.values ).toEqual( selectStyle.values );
	expect( Math.abs( inputStyle.width - selectStyle.width ) ).toBeLessThan( 1 );
} );

test( 'simple product length validates while typing', async ( { page } ) => {
	await page.goto( '/product/length-bar/' );
	const field = page.locator( 'p.jps-cs-length' );
	const length = field.locator( 'input[name="jps_cs_length"]' );
	const message = field.locator( '#jps_cs_length_message' );
	await expect( field.locator( 'label[for="jps_cs_length"]' ) ).toBeVisible();

	await length.fill( '5' );
	await expect( length ).toHaveAttribute( 'aria-invalid', 'true' );
	await expect( message ).toHaveText( /10.*300.*cm/ );

	await length.fill( '50' );
	await expect( length ).toHaveAttribute( 'aria-invalid', 'false' );
	await expect( message ).toHaveText( '' );
} );
