import { expect, test } from '@playwright/test';
import { wpCli } from './support/wp-cli';

test( 'Core tab settings change titles, order and visibility shop-wide', async ( { page } ) => {
	const optionNames = [
		'jps_cs_tabs_core_description_title', 'jps_cs_tabs_core_description_priority', 'jps_cs_tabs_core_description_hidden',
		'jps_cs_tabs_core_additional_information_title', 'jps_cs_tabs_core_additional_information_priority', 'jps_cs_tabs_core_additional_information_hidden',
		'jps_cs_tabs_core_reviews_title', 'jps_cs_tabs_core_reviews_priority', 'jps_cs_tabs_core_reviews_hidden',
	];
	const title = `Overview-${ Date.now() }`;
	const oldValues = optionNames.map( ( name ) => wpCli( 'eval', `echo get_option( '${ name }', '__missing__' );` ) );
	try {
		wpCli( 'option', 'update', 'jps_cs_tabs_core_reviews_title', title );
		wpCli( 'option', 'update', 'jps_cs_tabs_core_reviews_priority', '5' );
		wpCli( 'option', 'update', 'jps_cs_tabs_core_additional_information_hidden', 'yes' );
		await page.goto( '/product/tab-demo/' );
		const tabs = page.getByRole( 'tablist' );
		await expect( tabs.getByRole( 'tab', { name: title } ) ).toBeVisible();
		await expect( tabs.getByRole( 'tab', { name: 'Additional information' } ) ).toHaveCount( 0 );
		await expect( tabs.getByRole( 'tab', { name: 'Downloads' } ) ).toBeVisible();
		const names = await tabs.getByRole( 'tab' ).allTextContents();
		expect( names.findIndex( ( name ) => name.startsWith( title ) ) ).toBeLessThan( names.findIndex( ( name ) => name.includes( 'Downloads' ) ) );
	} finally {
		for ( let index = 0; index < optionNames.length; index++ ) {
			if ( oldValues[ index ] === '__missing__' ) {
				wpCli( 'option', 'delete', optionNames[ index ] );
			} else {
				wpCli( 'option', 'update', optionNames[ index ], oldValues[ index ] );
			}
		}
	}
} );
