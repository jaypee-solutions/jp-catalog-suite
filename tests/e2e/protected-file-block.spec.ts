import { test, expect } from '@playwright/test';
import { wpCli } from './support/wp-cli';

test( 'a core File block of a protected file is hidden from guests and downloads under its own name for customers', async ( { page } ) => {
	const created = wpCli( 'eval', `$id = get_posts( [ 'post_type' => 'attachment', 'title' => 'part.stp', 'posts_per_page' => 1, 'fields' => 'ids' ] )[0]; $url = wp_get_attachment_url( $id ); $post = wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'E2E file block', 'post_content' => '<!-- wp:file {"id":' . $id . ',"href":"' . $url . '"} --><div class="wp-block-file"><a href="' . $url . '">part.stp</a><a href="' . $url . '" class="wp-block-file__button wp-element-button" download>Download</a></div><!-- /wp:file -->' ] ); echo $post . '|' . get_permalink( $post );` );
	const [ postId, permalink ] = created.split( '|' );
	try {
		await page.goto( permalink );
		await expect( page.locator( '.wp-block-file a' ) ).toHaveCount( 0 );
		await expect( page.locator( '.jps-cs-protected-file' ) ).toContainText( 'login required' );

		await page.request.get( '/wp-login.php' );
		const login = await page.request.post( '/wp-login.php', { form: { log: 'customer1', pwd: 'password', 'wp-submit': 'Log In', redirect_to: '/', testcookie: '1' }, maxRedirects: 0 } );
		expect( login.status() ).toBe( 302 );
		await page.goto( permalink );
		await expect( page.locator( '.wp-block-file__button' ) ).toHaveAttribute( 'download', 'part.stp' );
		const download = page.waitForEvent( 'download' );
		await page.locator( '.wp-block-file__button' ).click();
		expect( ( await download ).suggestedFilename() ).toBe( 'part.stp' );
	} finally {
		wpCli( 'eval', `wp_delete_post( ${ postId }, true );` );
	}
} );
