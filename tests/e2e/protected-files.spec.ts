import { expect, test } from '@playwright/test';
import { loginAs } from './support/auth';
import { wpCli } from './support/wp-cli';

test( 'protected file endpoint requires login and denies direct uploads access', async ( { page } ) => {
  const id = wpCli( 'eval', "echo get_posts( [ 'post_type' => 'attachment', 'title' => 'part.stp', 'posts_per_page' => 1, 'fields' => 'ids' ] )[0] ?? 0;" );
  expect( id ).not.toBe( '0' );
  const guestMedia = await page.request.get( `/wp-json/wp/v2/media/${ id }` );
  expect( guestMedia.status() ).toBe( 404 );
  const url = wpCli( 'eval', `echo str_replace( wp_upload_dir()['basedir'], wp_upload_dir()['baseurl'], get_attached_file( ${ id } ) );` );
  const endpoint = wpCli( 'eval', `echo wp_get_attachment_url( ${ id } );` );
  const guest = await page.request.get( endpoint, { maxRedirects: 0 } );
  expect( guest.status() ).toBe( 302 );
  expect( guest.headers().location ).toContain( 'wp-login.php' );

  await loginAs( page, 'customer1', 'password' );
  const download = await page.request.get( endpoint );
  expect( download.status() ).toBe( 200 );
  expect( download.headers()['content-disposition'] ).toContain( 'part.stp' );
  const direct = await page.request.get( url );
  expect( direct.status() ).toBe( 403 );
} );
