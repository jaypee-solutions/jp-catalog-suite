import { test, expect } from './support/fixtures';
import { login } from './support/auth';
import { withUserLocale } from './support/locale';

test( 'a de_DE user sees German core strings in the admin', async ( { page } ) => {
	await login( page );

	await withUserLocale( page, 'de_DE', async () => {
		await page.goto( '/wp-admin/' );

		await expect( page.locator( 'html' ) ).toHaveAttribute( 'lang', /^de/ );
		await expect( page.locator( '#adminmenu' ) ).toContainText( 'Einstellungen' );
	} );
} );
