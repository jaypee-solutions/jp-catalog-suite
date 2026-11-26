import { expect, type Page } from '@playwright/test';

const ME = '/?rest_route=/wp/v2/users/me';

type Me = { locale: string };

async function restNonce( page: Page ): Promise<string> {
	const response = await page.request.get( '/wp-admin/admin-ajax.php?action=rest-nonce' );
	expect( response.ok() ).toBe( true );
	return ( await response.text() ).trim();
}

async function readLocale( page: Page, nonce: string ): Promise<string> {
	const response = await page.request.get( `${ ME }&context=edit`, { headers: { 'X-WP-Nonce': nonce } } );
	expect( response.ok() ).toBe( true );
	return ( ( await response.json() ) as Me ).locale;
}

async function writeLocale( page: Page, nonce: string, locale: string ): Promise<void> {
	const response = await page.request.post( ME, { headers: { 'X-WP-Nonce': nonce }, data: { locale } } );
	// WordPress rejects a locale whose language pack is not installed, so a missing pack fails here.
	expect( response.ok(), `setting the user locale to "${ locale }": ${ await response.text() }` ).toBe( true );
}

/**
 * Run `run` with the logged-in user switched to `locale`, then put the previous
 * value back (an empty string means "site default"), also when `run` throws.
 * The locale's core language pack has to be installed; see `test_locales` in
 * .copier-answers.yml. Call `login( page )` first.
 */
export async function withUserLocale<T>( page: Page, locale: string, run: () => Promise<T> ): Promise<T> {
	const nonce = await restNonce( page );
	const original = await readLocale( page, nonce );
	await writeLocale( page, nonce, locale );
	try {
		return await run();
	} finally {
		await writeLocale( page, nonce, original );
	}
}
