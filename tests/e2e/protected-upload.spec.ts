import { test, expect } from './support/fixtures';
import { wpCli } from './support/wp-cli';

const MIMES = 'stp|step=application/step\ndxf=application/dxf\nstl=model/stl';
const EXTENSIONS = 'stp, step, dxf, stl';

const FILES = [
	{ name: 'E2E-Box.STL', mimeType: 'application/octet-stream', buffer: Buffer.concat( [ Buffer.alloc( 80 ), Buffer.from( [ 1, 0, 0, 0 ] ), Buffer.alloc( 50 ) ] ) },
	{ name: 'E2E-Part.STEP', mimeType: 'text/plain', buffer: Buffer.from( 'ISO-10303-21;\nHEADER;\nENDSEC;\nDATA;\nENDSEC;\nEND-ISO-10303-21;\n' ) },
];

test( 'CAD files uploaded in the media uploader are stored protected', async ( { page, options } ) => {
	options.set( 'jps_cs_files_mimes', MIMES );
	options.set( 'jps_cs_files_protected_exts', EXTENSIONS );
	await page.goto( '/wp-login.php' );
	await page.fill( '#user_login', process.env.WP_ADMIN_USER ?? 'admin' );
	await page.fill( '#user_pass', process.env.WP_ADMIN_PASS ?? 'password' );
	await page.click( '#wp-submit' );

	try {
		for ( const file of FILES ) {
			await page.goto( '/wp-admin/media-new.php' );
			const upload = page.waitForResponse( ( response ) => response.url().includes( 'async-upload.php' ) );
			await page.locator( 'input[type=file]' ).first().setInputFiles( file );
			expect( ( await upload ).status(), file.name ).toBe( 200 );
			await expect( page.locator( '.media-item .error-div' ), file.name ).toHaveCount( 0 );

			const stored = wpCli( 'eval', `$ids = get_posts( [ 'post_type' => 'attachment', 'post_status' => 'any', 'title' => '${ file.name.replace( /\.[^.]+$/, '' ) }', 'numberposts' => 1, 'fields' => 'ids' ] ); echo $ids ? get_post_meta( $ids[0], '_jps_cs_protected', true ) . '|' . get_attached_file( $ids[0] ) : 'missing';` );
			expect( stored, file.name ).toMatch( /^1\|.*\/jps-cs-protected\/[A-Za-z0-9]{20}\// );
		}
	} finally {
		wpCli( 'eval', "foreach ( get_posts( [ 'post_type' => 'attachment', 'post_status' => 'any', 'numberposts' => -1, 's' => 'E2E-', 'fields' => 'ids' ] ) as $id ) { wp_delete_attachment( $id, true ); }" );
	}
} );

test( 'a site with file type settings saved before the 3D types can upload STL after updating', async ( { page, options } ) => {
	options.set( 'jps_cs_files_mimes', 'stp|step=application/step\ndxf=application/dxf' );
	options.set( 'jps_cs_files_protected_exts', 'stp, step, dxf' );
	options.set( 'jps_cs_files_3d_types_added', 'yes' );
	wpCli( 'option', 'delete', 'jps_cs_files_3d_types_added' );
	await page.goto( '/wp-login.php' );
	await page.fill( '#user_login', process.env.WP_ADMIN_USER ?? 'admin' );
	await page.fill( '#user_pass', process.env.WP_ADMIN_PASS ?? 'password' );
	await page.click( '#wp-submit' );

	try {
		await page.goto( '/wp-admin/media-new.php' );
		const upload = page.waitForResponse( ( response ) => response.url().includes( 'async-upload.php' ) );
		await page.locator( 'input[type=file]' ).first().setInputFiles( FILES[ 0 ] );
		expect( ( await upload ).status() ).toBe( 200 );
		await expect( page.locator( '.media-item .error-div' ) ).toHaveCount( 0 );
		const stored = wpCli( 'eval', "$ids = get_posts( [ 'post_type' => 'attachment', 'post_status' => 'any', 'title' => 'E2E-Box', 'numberposts' => 1, 'fields' => 'ids' ] ); echo $ids ? get_post_meta( $ids[0], '_jps_cs_protected', true ) : 'missing';" );
		expect( stored ).toBe( '1' );
	} finally {
		wpCli( 'eval', "foreach ( get_posts( [ 'post_type' => 'attachment', 'post_status' => 'any', 'numberposts' => -1, 's' => 'E2E-', 'fields' => 'ids' ] ) as $id ) { wp_delete_attachment( $id, true ); }" );
	}
} );

test( 'a CAD file uploaded in the block editor File block completes and links to the protected endpoint', async ( { page, options } ) => {
	options.set( 'jps_cs_files_mimes', MIMES );
	options.set( 'jps_cs_files_protected_exts', EXTENSIONS );
	await page.goto( '/wp-login.php' );
	await page.fill( '#user_login', process.env.WP_ADMIN_USER ?? 'admin' );
	await page.fill( '#user_pass', process.env.WP_ADMIN_PASS ?? 'password' );
	await page.click( '#wp-submit' );

	try {
		await page.goto( '/wp-admin/post-new.php' );
		// Blocks inserted before the editor finished loading its content are replaced by it.
		await page.waitForFunction( () => Reflect.get( window, 'wp' )?.data?.select( 'core/editor' )?.__unstableIsEditorReady?.() === true );
		await page.evaluate( () => {
			const wp = Reflect.get( window, 'wp' );
			wp.data.dispatch( 'core/block-editor' ).insertBlock( wp.blocks.createBlock( 'core/file' ) );
		} );
		await page.frameLocator( 'iframe[name="editor-canvas"]' ).locator( 'input[type=file]' ).first().setInputFiles( FILES[ 0 ] );
		await expect.poll( () => page.evaluate( () => Reflect.get( window, 'wp' ).data.select( 'core/block-editor' ).getBlocks()[ 0 ]?.attributes.href ?? '' ), { timeout: 20000 } ).toContain( 'jps_cs_file=' );
		await expect( page.locator( '.components-snackbar', { hasText: /Uploading|Wird hochgeladen/ } ) ).toHaveCount( 0 );
	} finally {
		wpCli( 'eval', "foreach ( get_posts( [ 'post_type' => 'attachment', 'post_status' => 'any', 'numberposts' => -1, 's' => 'E2E-', 'fields' => 'ids' ] ) as $id ) { wp_delete_attachment( $id, true ); }" );
	}
} );
