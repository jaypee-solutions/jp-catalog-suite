# Download log

Record authorized deliveries of protected files for a configured retention period. Shop admins and privacy administrators enable and manage the Download log (German: **Download-Protokoll**); records identify the Authorized user, file, optional product context, and UTC download time.

## Before you start

- Enable the **Product tabs** module in **WooCommerce → Settings → Catalog Suite → General**. The Download log controls are in its **Product tabs** settings section.
- Changing these settings and opening **WooCommerce → Download log** both require the WooCommerce `manage_woocommerce` capability (Administrators and Shop managers).
- Protected-file delivery must be configured and working; only authorized delivery attempts that reach file delivery are logged. See [Protected files](protected-files.md).

## Tutorial: Enable the Download log

1. Go to **WooCommerce → Settings → Catalog Suite → Product tabs**.
2. Under **Download log**, enable **Enable download log** (German: **Download-Protokoll aktivieren**).
3. Set **Download log retention (days)** (German: **Aufbewahrungsdauer des Download-Protokolls (Tage)**) to `365`, or choose another number of days. The default is 365.
4. Save changes.
5. Sign in as an Authorized user and download a protected file using its `?jps_cs_file=<attachment-id>` endpoint. A file listed in a File list block on a product page can include the product identifier as logging context.
6. Open **WooCommerce → Download log**. The page shows up to the latest 200 entries, with User, File, Product, and Date.

**Try it:** Enable the log with retention `365`, then download `part.stp` from a product tab while signed in. Look for the user, `part.stp`, the product if supplied, and the download date.

**What you should see:** A new record appears for each authorized request that passes the endpoint's file checks; it is written immediately before the file is sent, so every repeated download adds a row. The page lists the user's display name and email, file title, optional product title, and localized date/time. The stored timestamp is UTC.

## Tutorial: Change retention and purge old entries

1. Go to **WooCommerce → Settings → Catalog Suite → Product tabs → Download log**.
2. Set **Download log retention (days)** to the number of days you want to retain records; for example, `90`.
3. Save changes. Daily scheduled cleanup removes records older than that many days. The purge action is `jps_cs_purge_download_log`.
4. To keep entries indefinitely, enter `0`. This disables age-based purge; records remain until erased through privacy tools, the user's account is deleted, or plugin uninstall deletion is opted into.
5. There is no separate manual purge button. To erase a person's records, use the WordPress privacy erasure workflow or delete the user; do not lower the retention value expecting an immediate purge.

**Try it:** Set retention to `90` and save. The next daily cleanup removes records older than 90 days; a record created today remains. Set it to `0` if you need retention without age-based deletion.

**What you should see:** The Download log page continues to show the latest 200 entries. Daily cleanup is scheduled by the plugin; setting retention to zero makes the purge action leave rows untouched.

## Tutorial: Export or erase records for a person

1. In WordPress, open **Tools → Export Personal Data**.
2. Enter the account's email in **Username or email address** and select **Send Request**. Leave **Confirmation email** ticked to have the person confirm by email, or untick it to confirm the request immediately. Once the request is confirmed, select **Download personal data** in the request list; the Catalog Suite **Download log** group lists each recorded File, Product (empty when none was recorded) and Date.
3. To erase records, open **Tools → Erase Personal Data**, send a request for the same email the same way, and once it is confirmed select **Erase personal data**. The Download log eraser removes all of that user's records.
4. Alternatively, deleting the WordPress user removes their Download log records.
5. Explain this processing in your site's privacy policy. WordPress may append Catalog Suite's suggested policy text from its privacy-policy helper; review and adapt the text to your actual retention and processing practices.

**Try it:** Create a test download for a test account, request a personal-data export for its email, then request erasure and confirm that account's entries no longer appear.

**What you should see:** The exported Download log data identifies the file, optional product, and date. Erasure removes matching entries; deleting a user also removes their rows. No IP address is recorded.

## Settings reference

| Setting | Where | Default | Effect |
|---|---|---|---|
| Enable download log | WooCommerce → Settings → Catalog Suite → Product tabs → Download log | No | When enabled and the **Product tabs** module is on, an authorized protected-file delivery records user, attachment, optional product ID, and UTC timestamp. |
| Download log retention (days) | Same section | `365` | Daily purge removes entries older than the configured number of days. `0` keeps entries until erased or the user is deleted. |

## Good to know

- Logging is off by default. Turning it off stops new records; it does not erase existing records. Turning the **Product tabs** module off also stops new records and removes the **WooCommerce → Download log** page, even when **Enable download log** stays ticked; retention cleanup, privacy export/erasure and user-deletion cleanup keep working.
- The log contains no IP address. Product context is optional and comes from the file link; the endpoint validates it as a published product for logging, and it never affects access authorization.
- The WooCommerce page shows only the latest 200 entries. Privacy export processes matching records in pages of 100.
- Daily retention cleanup is scheduled with WordPress's `jps_cs_purge_download_log` event. WordPress scheduled events run when the site receives traffic unless a system scheduler triggers them.
- At zero retention, the plugin's purge routine does not remove entries. WordPress privacy erasure and user deletion still remove the specified user's entries.
- On plugin uninstall, log data is removed only if the **Delete tabs, protected files and the download log when the plugin is deleted. Orders are never deleted.** option is enabled under **WooCommerce → Settings → Catalog Suite → General**. This opt-in also removes protected files; it is not a log-only switch.
- The plugin adds a suggested privacy-policy paragraph when available. Suggested text is not a substitute for your own privacy review. It says that logging records the user, protected file, optional product and download time, with configured retention; zero keeps data until erased or the user is deleted.

**Privacy policy suggestion:** “When enabled, our Download log records the account, protected file, optional product and download time. We retain records for the configured number of days; with retention set to zero, we keep them until erased or the account is deleted. We do not record IP addresses.” Adapt this to your actual configuration and applicable privacy requirements.

## For developers

- `jps_cs_before_file_delivery` — observe authorized protected-file delivery; the Download log records its user, attachment, and product context here.
- `jps_cs_purge_download_log` — run the scheduled retention cleanup.

See the [README hook reference](../../README.md#hook-reference) for signatures.
