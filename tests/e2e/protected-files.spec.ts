import { expect, test } from '@playwright/test';

test('protected file endpoint requires login and denies direct uploads access', async ({ page }) => {
  const publicMedia = await page.request.get('/wp-json/wp/v2/media?search=part.stp');
  expect(publicMedia.ok()).toBeTruthy();
  const media = await publicMedia.json();
  const attachment = media.find((item: { title: { rendered: string }; source_url: string }) => item.title.rendered === 'part.stp');
  expect(attachment).toBeTruthy();
  const url = new URL(attachment.source_url);
  const id = url.searchParams.get('jps_cs_file');
  expect(id).toBeTruthy();
  const guest = await page.request.get(`/?jps_cs_file=${id}`, { maxRedirects: 0 });
  expect(guest.status()).toBe(302);
  expect(guest.headers().location).toContain('wp-login.php');

  await page.request.get('/wp-login.php');
  const login = await page.request.post('/wp-login.php', {
    form: { log: 'customer1', pwd: 'password', 'wp-submit': 'Log In', redirect_to: '/', testcookie: '1' },
    maxRedirects: 0,
  });
  expect(login.status()).toBe(302);
  const download = await page.request.get(`/?jps_cs_file=${id}`);
  expect(download.status()).toBe(200);
  expect(download.headers()['content-disposition']).toContain('part.stp');
  const direct = await page.request.get('/wp-content/uploads/jps-cs-protected/part.stp');
  expect(direct.status()).toBe(403);
});
