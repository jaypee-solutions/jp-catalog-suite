# Protected files

Protect engineering files in the Media Library and deliver them only to an Authorized user. Shop admins configure storage and delivery; editors upload files and place them in a File list block.

## Before you start

- Protected-file and Download log settings are in **WooCommerce → Settings → Catalog Suite → Product tabs**, which is always available. Protected-file storage, delivery and the File list block work whether or not the **Product tabs** module is on; enable it at **WooCommerce → Settings → Catalog Suite → General** to place File lists in Global tabs and Product tabs and to record downloads in the [Download log](download-log.md).
- Changing Catalog Suite settings requires the `manage_woocommerce` capability (Administrators and Shop managers), but only users with `manage_options` (Administrators) can change **Allowed file MIME types**; other users' edits to that field are discarded on save. Uploading media requires `upload_files`, and changing an attachment's **Protected** status requires permission to edit that attachment.
- Configure your web server before accepting protected uploads. The plugin creates an Apache `.htaccess` deny rule, but nginx requires its own deny rule. Review the [hosting requirements](../../.okf/references/hosting-requirements.md), the [README hosting and privacy notes](../../README.md#hosting-and-privacy) and the [README hook reference](../../README.md#hook-reference).
- The default MIME list allows STEP and DXF, and the default automatic-protection list includes `stp`, `step`, and `dxf`.

## Tutorial: Upload a protected STEP or DXF file

1. Open **WooCommerce → Settings → Catalog Suite → Product tabs**.
2. In **Protected files** (German: **Geschützte Dateien**), leave **Allowed file MIME types** (German: **Zulässige MIME-Typen für Dateien**) as `stp|step=application/step` and `dxf=application/dxf`. Each line maps extension(s) to a MIME type; separate aliases with `|`.
3. Leave **Automatically protect extensions** (German: **Dateiendungen automatisch schützen**) as `stp, step, dxf`, or enter the comma-separated extensions you want automatically protected. The extension must also be an upload type WordPress accepts: one WordPress allows already (for example `pdf`) or one added in **Allowed file MIME types**.
4. Set **File delivery method** (German: **Methode zur Dateibereitstellung**) to **Automatic** (German: **Automatisch**). Automatic uses X-Sendfile only when PHP runs as an Apache module and `apache_get_modules()` reports `mod_xsendfile`; otherwise, including under PHP-FPM, it uses PHP delivery.
5. Save the settings. In **Media → Add Media File**, upload `part.stp` (or `bracket.dxf`).
6. WordPress stores the upload under `wp-content/uploads/jps-cs-protected/` in a generated subdirectory. The plugin marks the attachment protected and rewrites its attachment URL to the plugin endpoint, `/?jps_cs_file=<attachment-id>`; the URL does not reveal the storage path.
7. Do not expect a preview: protected files do not generate previews or thumbnails.

**Try it:** Upload `part.stp` with the defaults. Its file path should include `jps-cs-protected/`, and its attachment URL should include `jps_cs_file=` rather than the protected directory path.

**What you should see:** The attachment appears in the Media Library, but its direct storage URL is denied by the server. The plugin endpoint requires login and authorization before delivery.

## Tutorial: Protect or unprotect a file in the Media Library

1. Open **Media → Library** and select a non-image attachment, such as a PDF.
2. In the attachment details, find **Protected** (German: **Geschützt**) and select **Served only to authorized users**.
3. Save/update the attachment. Catalog Suite moves the existing file into the protected directory, removes generated image sizes, and marks the file protected.
4. To make it public again, clear **Protected** and save. The file is moved back to public uploads and the protected marker is removed.
5. If the move fails — the source is outside the uploads directory, the relative path is invalid, or the filesystem move failed — the file and its protected marker stay unchanged. WordPress does not display the plugin's error (**The file could not be moved.**, German: **Die Datei konnte nicht verschoben werden.**) in the Media Library or on the attachment edit screen, so reopen the attachment and check that **Protected** and the file URL reflect the state you expect; if not, check the attachment's file path and filesystem permissions.

**Try it:** Protect an existing PDF, save, then clear the checkbox and save again. The same attachment should move between public uploads and the protected directory.

**What you should see:** The attachment remains in the Media Library. A protected attachment's public-facing URL is the endpoint URL; a public attachment resolves to its ordinary attachment URL.

## Tutorial: Add a File list to a tab

1. Under **Products → Product tabs**, edit a Global tab or Product tab in the block editor.
2. Insert the **File list** block (German: **Dateiliste**).
3. In the block inspector's **Files** (German: **Dateien**) panel, choose **Choose files** (German: **Dateien auswählen**). In the media picker, select one or more Media Library files and choose **Use selected files** (German: **Ausgewählte Dateien verwenden**).
4. Update the tab. The editor preview renders the same server-generated list. Existing files display their title, uppercase extension, and size; missing files are omitted.
5. View the product page as a guest. A protected file has no download link and displays **(login required)** (German: **Anmeldung erforderlich**). Public files link to their ordinary media URL.
6. Sign in as an Authorized user and revisit the product. A protected file links through `?jps_cs_file=<attachment-id>`; on a product page the link also carries `product=<id>` for optional Download log context.

**Try it:** Add `part.stp` to a tab's File list. As a guest, confirm the file name and **(login required)** appear without a link; then sign in as an Authorized user and use its link.

**What you should see:** Guests see no protected-file link. By default logged-in users are Authorized users; an access rule can deny a logged-in user, in which case the label remains and no link is rendered.

## Tutorial: Check delivery and site protection

1. Open a protected file's endpoint URL while signed out. The endpoint redirects to the WordPress login page and returns to the endpoint after login.
2. Sign in as an Authorized user and open the same endpoint. The file downloads as an attachment. A logged-in user denied by the access rule receives HTTP 403.
3. Test the direct storage URL as an unauthorized visitor. It must be denied; never rely only on the plugin endpoint while the storage path remains public.
4. In WordPress, open **Tools → Site Health → Status**. Catalog Suite's check appears as **Protected files are denied by the web server.** (German: **Der Webserver verweigert den Zugriff auf geschützte Dateien.**) under **Passed tests** when the protected directory returns HTTP 403, or as the critical issue **Protected files may be publicly accessible.** (German: **Geschützte Dateien könnten öffentlich zugänglich sein.**) when it does not; correct the web-server rule and check again.
5. For nginx, add the deny rule in the server configuration: `location ^~ /wp-content/uploads/jps-cs-protected/ { deny all; }`. Apache's `.htaccess` rule does not protect nginx.

**Try it:** Compare the endpoint and direct storage URL for `part.stp`: authorized endpoint delivery should work while direct access returns 403.

**What you should see:** Unauthorized direct access is denied; authorized delivery is private and no-store. Catalog Suite delivers protected files up to 256 MB with either delivery method; larger files return HTTP 404 (not found), so do not protect files over 256 MB. PHP delivery does not support HTTP Range requests. Set `upload_max_filesize` and `post_max_size` to at least 256 MB and ensure the proxy request-body limit is sufficient. X-Sendfile hands delivery to Apache `mod_xsendfile` for efficiency but does not raise the 256 MB limit; see the [hosting runbook](../../.okf/references/hosting-requirements.md) for Apache, nginx, Traefik and Varnish requirements.

## Settings reference

| Setting | Where | Default | Effect |
|---|---|---|---|
| Allowed file MIME types | WooCommerce → Settings → Catalog Suite → Product tabs → Protected files | `stp\|step=application/step` and `dxf=application/dxf` | Adds permitted extension-to-MIME mappings for users allowed to upload files. Dangerous code/script extensions are rejected. |
| Automatically protect extensions | Same section | `stp, step, dxf` | Matching new uploads are stored protected and marked protected. |
| File delivery method | Same section | **Automatic** (`auto`) | Selects PHP or X-Sendfile. X-Sendfile, whether chosen here, by Automatic or by the `jps_cs_file_delivery_method` filter, is used only when PHP runs as an Apache module that reports `mod_xsendfile`; otherwise the file is streamed by PHP. |

## Good to know

- Protected files remain in uploads for Media Library compatibility. Apache denial is created at activation; nginx and proxy/cache behavior must be configured by the site operator.
- Files larger than 256 MB are never delivered, whichever delivery method is selected; the endpoint returns HTTP 404. PHP delivery does not serve byte ranges. X-Sendfile needs Apache `mod_xsendfile` configured with the absolute protected directory path.
- Images are not offered the manual **Protected** attachment field; protected files do not generate previews. Existing PDFs remain public unless you protect them individually.
- Guests cannot discover protected attachments through the Media REST API; protected paths are stripped from REST responses.
- The File list adds `product=<id>` only as logging context. Authorization is checked against the attachment and current user, not that client-provided product value.
- The uninstall deletion option is separate: protected files are removed when plugin deletion is opted into. See [Download log](download-log.md).

## For developers

- `jps_cs_can_access_protected` — decide protected-file access using the `attachment_id` context.
- `jps_cs_file_delivery_method` — select `php` or `xsendfile` for a file delivery; `xsendfile` falls back to PHP streaming unless `apache_get_modules()` reports `mod_xsendfile`.
- `jps_cs_before_file_delivery` — observe an authorized delivery immediately before it begins; Download log uses this action.

See the [README hook reference](../../README.md#hook-reference) for signatures.
