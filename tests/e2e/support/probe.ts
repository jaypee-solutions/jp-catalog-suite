import type { Page } from '@playwright/test';

/**
 * Logs what the browser does, for throwaway diagnostic specs (`tests/e2e/zz-*.spec.ts`, git-ignored):
 * non-GET responses, failed requests, console errors and uncaught page errors with their stack.
 * See "Diagnosing browser behaviour" in docs/agents/testing.md.
 */
export function probe( page: Page, label = 'PROBE' ): void {
	page.on( 'response', ( response ) => {
		const method = response.request().method();
		if ( method !== 'GET' ) {
			console.log( `${ label } ${ method } ${ response.status() } ${ response.url().slice( 0, 140 ) }` );
		}
	} );
	page.on( 'requestfailed', ( request ) => {
		console.log( `${ label } FAILED ${ request.url().slice( 0, 140 ) } ${ request.failure()?.errorText ?? '' }` );
	} );
	page.on( 'console', ( message ) => {
		if ( message.type() === 'error' ) {
			console.log( `${ label } CONSOLE ${ message.text().slice( 0, 300 ) }` );
		}
	} );
	page.on( 'pageerror', ( error ) => {
		const stack = ( error.stack ?? '' ).split( '\n' ).slice( 0, 6 ).join( '\n' );
		console.log( `${ label } PAGEERROR ${ error.message }\n${ stack }` );
	} );
}
