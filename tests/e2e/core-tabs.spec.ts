import { execFileSync } from 'node:child_process';
import { expect, test } from '@playwright/test';

function wpCli( ...args: string[] ): string {
	return execFileSync( 'npx', [ 'wp-env', 'run', 'cli', '--', 'wp', ...args ], { encoding: 'utf8', env: { ...process.env, WP_ENV_HOME: './.wp-env' } } ).trim();
}

test( 'Core tab settings change titles, order and visibility shop-wide', async ( { page } ) => {
	const optionNames = [
		'jps_cs_core_tab_description_title', 'jps_cs_core_tab_description_priority', 'jps_cs_core_tab_description_hidden',
		'jps_cs_core_tab_additional_information_title', 'jps_cs_core_tab_additional_information_priority', 'jps_cs_core_tab_additional_information_hidden',
		'jps_cs_core_tab_reviews_title', 'jps_cs_core_tab_reviews_priority', 'jps_cs_core_tab_reviews_hidden',
	];
	const title = `Overview-${ Date.now() }`;
	const oldValues = optionNames.map( ( name ) => wpCli( 'eval', `echo get_option( '${ name }', '__missing__' );` ) );
	try {
		wpCli( 'option', 'update', 'jps_cs_core_tab_description_title', title );
		wpCli( 'option', 'update', 'jps_cs_core_tab_description_priority', '5' );
		wpCli( 'option', 'update', 'jps_cs_core_tab_additional_information_hidden', 'yes' );
		wpCli( 'option', 'update', 'jps_cs_core_tab_reviews_priority', '30' );
		await page.goto( '/product/tab-demo/' );
		const tabs = page.getByRole( 'tablist' );
		await expect( tabs.getByRole( 'tab', { name: title } ) ).toBeVisible();
		await expect( tabs.getByRole( 'tab', { name: 'Additional information' } ) ).toHaveCount( 0 );
		await expect( tabs.getByRole( 'tab', { name: 'Reviews' } ) ).toBeVisible();
		const names = await tabs.getByRole( 'tab' ).allTextContents();
		expect( names.indexOf( title ) ).toBeLessThan( names.indexOf( 'Reviews' ) );
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
