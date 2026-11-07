# Release test checklist

Run each module's applicable cases as a guest and as a logged-in customer, in German (DE) and English (EN) with TranslatePress active, using both Classic and Blocks cart/checkout. Confirm translated strings and the same customer-visible behavior in each combination.

| Module | Guest DE Classic | Guest DE Blocks | Guest EN Classic | Guest EN Blocks | Logged-in DE Classic | Logged-in DE Blocks | Logged-in EN Classic | Logged-in EN Blocks | Germanized | Protected-file direct URL |
|---|---|---|---|---|---|---|---|---|---|---|
| Quote mode and Inquiries | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | N/A |
| Inquiry emails and checkout fields | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | N/A |
| Product tabs and access | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | N/A | ☐ |
| Protected files and download log | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | N/A | ☐ |
| Variable length and pricing | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | N/A | N/A |

## Per-module checks

- **Quote mode and Inquiries:** submit an inquiry; verify price-free cart, checkout and confirmation, inquiry status and email notifications; verify Quote mode off restores standard purchases without hiding existing Inquiries.
- **Inquiry emails and checkout fields:** verify recipient configuration and both email templates; exercise optional, required and hidden fields in Classic and Blocks.
- **Product tabs and access:** verify global/category/product scope, replacement and priority, core tabs, protected/fallback block behavior, guest notice and logged-in access.
- **Protected files and download log:** authorized delivery succeeds; unauthorized guest direct URL is denied without leaking the protected file; authorized delivery appears in the log and privacy export/erasure.
- **Variable length and pricing:** verify input boundaries, step validation, separate cart lines, order metadata, variation overrides, Store API, and unit-price calculation where enabled.

For Germanized, verify Quote mode removes incompatible legal/price notices and checkboxes while retaining privacy consent and correct inquiry button wording. In direct-URL checks, use the actual attachment URL as an unauthorized guest and confirm no file bytes or protected storage path are exposed.
