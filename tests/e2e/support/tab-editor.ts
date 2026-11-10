import { expect, type Page } from './fixtures';

export async function logIn( page: Page ): Promise<void> {
	await page.request.get( '/wp-login.php' );
	const login = await page.request.post( '/wp-login.php', {
		form: { log: process.env.WP_ADMIN_USER ?? 'admin', pwd: process.env.WP_ADMIN_PASS ?? 'password', 'wp-submit': 'Log In', redirect_to: '/wp-admin/', testcookie: '1' },
		maxRedirects: 0,
	} );
	expect( login.status() ).toBe( 302 );
}

export async function publishInDialog( page: Page, title: string, body: string ): Promise<void> {
	const editor = page.frameLocator( '.jps-cs-tab-dialog[open] iframe' );
	const nestedCanvas = editor.locator( 'iframe[name="editor-canvas"]' );
	await expect( editor.locator( '.edit-post-layout, .editor-header' ).first() ).toBeVisible( { timeout: 30_000 } );
	const canvas = ( await nestedCanvas.count() ) > 0 ? editor.frameLocator( 'iframe[name="editor-canvas"]' ) : editor;
	const titleField = canvas.getByRole( 'textbox', { name: 'Add title' } );
	await titleField.fill( title );
	await titleField.press( 'Enter' );
	await page.keyboard.type( body );
	await editor.getByRole( 'button', { name: 'Publish', exact: true } ).first().click();
	const confirm = editor.locator( '.editor-post-publish-panel__header-publish-button button, .editor-post-publish-panel__header-publish-button' ).first();
	if ( await confirm.isVisible( { timeout: 3_000 } ).catch( () => false ) ) {
		await confirm.click();
	}
}
