import { expect, type Page } from '@playwright/test';

/**
 * Logs the page's browser context in through wp-login.php. The request shares the context's cookie jar,
 * so later `page.goto()` calls are authenticated. Defaults to the administrator
 * (`WP_ADMIN_USER` / `WP_ADMIN_PASS`); pass the seeded `customer1` / `password` for a customer.
 */
export async function loginAs(
	page: Page,
	user: string = process.env.WP_ADMIN_USER ?? 'admin',
	password: string = process.env.WP_ADMIN_PASS ?? 'password',
): Promise<void> {
	await page.context().clearCookies();
	await page.request.get( '/wp-login.php' );
	const response = await page.request.post( '/wp-login.php', {
		form: { log: user, pwd: password, 'wp-submit': 'Log In', redirect_to: '/wp-admin/', testcookie: '1' },
		maxRedirects: 0,
	} );
	expect( response.status(), `login as ${ user }` ).toBe( 302 );
}
