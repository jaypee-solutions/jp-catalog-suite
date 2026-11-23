# STL uploads and the core type check

## Status

Accepted. Extends 0020.

## Context

Uploading an STL file failed. Two causes:

- STL and other 3D formats were neither default allowed types nor default automatically protected extensions.
- WordPress compares a file's detected content type with the type of its extension. Binary STL is detected as `application/octet-stream`, which is accepted only for `application/*`, `video/*` and `audio/*`, so `model/stl` is rejected; text STEP and ASCII STL are detected as `text/plain` and rejected as well. The plugin's `wp_check_filetype_and_ext` override exists for this, but returned early unless the caller passed an explicit MIME list. Core passes none for ordinary uploads, so the override never ran.

## Decision

- The override treats a missing caller list as the site's allowed types (`get_allowed_mime_types()`). A caller list still wins (0020: honor both upload capabilities and the caller MIME list).
- New installs allow and automatically protect STL and the other common 3D formats: OBJ, 3MF, PLY, glTF (`glb`, `gltf`), FBX, Collada (`dae`), IGES (`igs`, `iges`) and 3DS. Defaults live in `Protected_Files::DEFAULT_MIMES` and `DEFAULT_PROTECTED_EXTS`, used by the settings page and the code.

## Consequences

Configured CAD types upload whatever their content looks like; the extension, the allowed list and the upload capability remain the gate, as 0020 intended. Sites that already saved the settings keep their values, except that a one-time upgrade (`Protected_Files::add_default_3d_types`, guarded by the option `jps_cs_files_3d_types_added`, run on `admin_init`) appends the added 3D types that the stored allowed-types and protected-extensions lack. Types the site removed or customised are not touched, and the upgrade never runs again, so later removals stay. Without the upgrade those sites rejected the new types (0.5.0 and 0.5.1 added them to the defaults only). Native CAD formats (SolidWorks, Inventor, Parasolid) are not in the defaults; add them in the settings. Collada and glTF are XML or JSON, which is harmless because delivery always sends `Content-Disposition: attachment` and `X-Content-Type-Options: nosniff`.
