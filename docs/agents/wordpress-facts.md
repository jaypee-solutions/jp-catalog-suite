# WordPress facts

Behaviours of WordPress core (verified against 7.1.x) that are not obvious from the docs and cost time when guessed. Each entry states the fact and what it means for the code. Read it before writing code against mail, admin-post, post types, the block editor or REST. Add an entry when core surprised you.

## Mail

- `wp_mail()` resets `Body` and `AltBody` before `phpmailer_init` fires. → A plugin sets `AltBody` only for its own message. The default sender is `wordpress@` plus the `network_home_url()` host without a leading `www.`; `wp_mail_from` runs before `wp_mail_from_name`.
- A `pre_wp_mail` short-circuit skips `phpmailer_init` and never fires `wp_mail_failed`. → Do not use it in integration tests of mail code; it hides the hooks under test. Production code that short-circuits has to log its own failures.
- With an `AltBody`, PHPMailer sends `multipart/alternative`. → Assert the HTML part in the sent MIME body, not `ContentType === 'text/html'`.
- Hooks bind to object instances. → A second `new Mailer()` that nobody hooked never sees `phpmailer_init` or `wp_mail_failed`; send through the instance the plugin's `init()` registered.

## Admin and admin-post

- `menu_page_url()` returns an empty string during `admin-post.php`, REST and cron requests, because the admin menu is not built there. → Redirect and link with `admin_url( 'options-general.php?page=…' )` (`scripts/check-code-rules.sh` rejects `menu_page_url(` in `includes/`).

## Post types

- `register_post_type()` fills every label you do not pass with the Post wording ("Add Post", "Post published.", "No posts found."), and list-screen bulk notices come from the separate `bulk_post_updated_messages` filter. → Register the full label set plus `post_updated_messages` and `bulk_post_updated_messages`.
- `WP_REST_Posts_Controller::check_read_permission()` makes every `publish` post of a `show_in_rest` post type readable anonymously. → A non-public post type that holds private configuration needs a custom REST controller, not just `public => false`.

## REST

- `rest_cookie_check_errors()` with no nonce calls `wp_set_current_user( 0 )` and treats the request as unauthenticated. → A public REST endpoint needs no nonce, and it cannot go stale behind a page cache. With a nonce the request runs as the logged-in user.
- `rest_get_server()->dispatch()` in a test runs the real permission callbacks for the current user. → Set the user with `wp_set_current_user()` and test the anonymous case too.

## Blocks and the editor

- `register_block_script_handle()` calls `wp_set_script_translations( $handle, $textdomain )` without a path. → Bundled `languages/*.json` files need an explicit call with the plugin's `languages` directory.
- `parse_blocks()` omits attributes that are left at their `block.json` defaults. → A server-side reader has to merge the defaults from `block.json` itself.
- The block editor drops unknown keys from the block editor settings. → Hand plugin data to editor scripts with an inline script on `enqueue_block_editor_assets`.
- A block's `style` CSS is emitted by `@wordpress/scripts` only when the block's `index.js` imports it. → Import the stylesheet from `index.js`, or the front end gets none.
- `@wordpress/scripts` compiles JSX against the `react-jsx-runtime` script handle, which WordPress registers only from 6.6. → A plugin with built blocks requires WordPress 6.6 or later.

## Environment

- wp-env pins `WP_HOME` and `WP_SITEURL`. → Integration tests cannot change the site URL through the `home` option; use the `home_url` and `network_home_url` filters.
- Core language packs are not part of a fresh wp-env site. → Tests that need a core string in another language (`switch_to_locale()`, a user locale) need the pack installed; the template's seed installs the `test_locales`.
