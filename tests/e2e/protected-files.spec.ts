import { execFileSync } from 'node:child_process';
import { expect, test } from '@playwright/test';

function wpCli( ...args: string[] ): string {
  const output = execFileSync( 'npx', [ 'wp-env', 'run', 'cli', '--', 'wp', ...args ], { encoding: 'utf8', env: { ...process.env, WP_ENV_HOME: './.wp-env' } } );
  return output.split( /\r?\n/ ).map( ( line ) => line.trim() ).filter( ( line ) => line && ! line.startsWith( 'ℹ' ) && ! line.startsWith( '✔' ) && ! line.startsWith( 'Deprecated:' ) ).join( '\n' ).trim();
}

test( 'protected file endpoint requires login and denies direct uploads access', async ( { page } ) => {
  const id = wpCli( 'eval', "echo get_posts( [ 'post_type' => 'attachment', 'title' => 'part.stp', 'posts_per_page' => 1, 'fields' => 'ids' ] )[0] ?? 0;" );
  expect( id ).not.toBe( '0' );
  const guestMedia = await page.request.get( `/wp-json/wp/v2/media/${ id }` );
  expect( guestMedia.status() ).toBe( 404 );
  const url = wpCli( 'eval', `echo str_replace( wp_upload_dir()['basedir'], wp_upload_dir()['baseurl'], get_attached_file( ${ id } ) );` );
  const guest = await page.request.get( `/?jps_cs_file=${ id }`, { maxRedirects: 0 } );
  expect( guest.status() ).toBe( 302 );
  expect( guest.headers().location ).toContain( 'wp-login.php' );

  await page.request.get( '/wp-login.php' );
  const login = await page.request.post( '/wp-login.php', {
    form: { log: 'customer1', pwd: 'password', 'wp-submit': 'Log In', redirect_to: '/', testcookie: '1' },
    maxRedirects: 0,
  } );
  expect( login.status() ).toBe( 302 );
  const download = await page.request.get( `/?jps_cs_file=${ id }` );
  expect( download.status() ).toBe( 200 );
  expect( download.headers()['content-disposition'] ).toContain( 'part.stp' );
  const direct = await page.request.get( url );
  expect( direct.status() ).toBe( 403 );
} );
