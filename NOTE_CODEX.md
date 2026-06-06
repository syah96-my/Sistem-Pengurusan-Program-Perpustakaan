# Codex Handoff Note

This project was cleaned up and refactored so it can be archived on GitHub and reused later as a more universal PHP/XAMPP web app.

## Local Access

- URL: `http://localhost/program-register/login/login.php`
- Username: `admin`
- Password: `password`

The project should stay under:

```text
C:\xampp-8.0\htdocs\program-register
```

Root-level shortcut/junction copies under `C:\xampp-8.0\htdocs` were removed.

## Main Work Completed

- Reworked the app so it runs correctly from the `/program-register` base path.
- Added `config/bootstrap.php` as shared setup for:
  - sessions
  - base path helpers
  - database loading
  - security helpers
  - library hierarchy helpers
- Added `app_path()` usage across PHP pages so assets, scripts, and redirects work under XAMPP subfolder deployment.
- Added `admin/javascript/global-context.php` to expose shared JS context:
  - API base URL
  - user/library context
  - CSRF token
  - app base path
- Refactored long inline JavaScript into separate files under `admin/javascript`.
- Refactored CSS into separate files under `admin/css`.
- Fixed broken/garbled button text caused by bad icon encoding.
- Improved button styling consistency for filters, action buttons, table buttons, and bulk actions.
- Fixed login page input styling.
- Set admin password to `password`.
- Removed the old one-session-only login behavior.

## API And Routing

- Kept the API architecture.
- Confirmed pages call the API directly through `/api/?route=...`.
- Added base-path aware JS API config:

```js
window.GM_API_BASE
window.GM_API_KEY
window.GM_BASE_PATH
```

- Fixed missing API implementation for:

```text
stats/program-detail
```

- Full registered API route sweep passed:
  - `94` routes checked
  - no fatal PHP/runtime output
  - expected `400` responses remain for create/update/delete routes called without request payload

## Library Hierarchy Change

The library relationship model was changed to support nested libraries up to 4 layers.

Rules implemented:

- A main library can register sub libraries.
- A sub library can register its own sub libraries.
- Nesting is supported up to 4 layers.
- Verification is checked by the higher-level library.
- Statistics access follows the same hierarchy:
  - a library can read its own statistics
  - a library can read statistics for its sub libraries
  - it cannot read unrelated branches

Shared hierarchy logic is handled through:

```text
helpers/LibraryHierarchy.php
```

## Database/Table Refactor

The old table/column names were cleaned up and renamed to be easier to understand.

Code and API were updated to match the readable schema names. The local XAMPP database was migrated during the refactor.

Important related areas:

- program CRUD
- verification workflow
- users
- libraries
- child libraries
- participants
- reports/statistics
- public attendance forms

## Reports Checked

The report/statistic pages were checked and fixed:

- `stat_status.php`
- `stat_program_type.php`
- `stat_scale.php`
- `stat_program_mod.php`
- `stat_program_target.php`
- `stat_participant_analytics.php`
- `stat_program.php`
- `stat_download.php`

## Security Work Completed

- Sensitive config/data paths are blocked by Apache `.htaccess`.
- All Markdown handoff/docs files are blocked from direct Apache access.
- Directory listing disabled.
- Session cookies now use:
  - `HttpOnly`
  - `SameSite=Lax`
  - `Secure` when HTTPS is used
- Mutating API requests now require a CSRF token.
- The existing JS `X-API-KEY` header now carries the CSRF token.
- `admin/change_password.php` also validates the CSRF token.
- Removed visible PHP debug output settings from admin pages.
- Strengthened `gm_encrypt()` / `gm_decrypt()`:
  - new encryption uses AES-256-CBC with HMAC verification
  - old AES-256-ECB decrypt fallback remains so older generated links do not immediately break
- Sensitive files verified as blocked with `403`:
  - `config/database.php`
  - `db/program_register.sql`
  - `.env.example`
  - `README.md`
  - `NOTE_CODEX.md`
  - `.gitignore`
  - `helpers/LibraryHierarchy.php`
- Public attendance submit now requires both `program_id` and the matching `public_token`.
- Public attendance form and submit endpoint now enforce the registration date window.
- Public autofill cookies now use `HttpOnly` and `SameSite=Lax`.
- CSV export cells are prefixed when needed to reduce spreadsheet formula injection risk.
- Public status/success pages escape dynamic values rendered into JavaScript templates.

## Archive Cleanup Completed

- Removed unused files:
  - `admin/template.php`
  - `admin/css/pages/template.css`
  - `config/app.php`
- Removed old disconnected notification module:
  - `mail/`
- Replaced hardcoded Google Sheets template URLs with local CSV templates:
  - `assets/templates/users_bulk_template.csv`
  - `assets/templates/branches_bulk_template.csv`
  - `assets/templates/programs_bulk_template.csv`
  - `assets/templates/participants_bulk_template.csv`
- Vendored Chart.js locally:
  - `assets/vendor/chartjs/chart.umd.min.js`
- Removed Google Fonts runtime links from public pages.

## Validation Completed

PHP lint:

```text
PHP_LINT_OK
```

JavaScript syntax:

```text
node --check passed for admin/javascript files
```

Browser page sweep through Chrome:

```text
25 pages checked
all returned HTTP 200
issueCount: 0
```

Pages checked included:

- login
- dashboard
- program input
- program verification
- social media activities
- users
- branches
- libraries
- parameter pages
- manage participants
- all report pages
- public attendance pages

Latest archive page check:

```text
25 pages checked over HTTP
all returned HTTP 200
no PHP fatal/warning/runtime text found
```

API sweep:

```text
94 registered routes checked
NO_FATAL_API_ERRORS
```

CSRF check:

```text
missing token: 403
valid token: reached controller and returned normal validation response
```

## Setup Notes For Future Reuse

Before deploying outside local XAMPP:

- Copy `.env.example` to `.env`.
- Replace placeholder values.
- Use a real database user instead of root/no password.
- Change:

```text
GM_SECRET_KEY
GM_ADMIN_PASSWORD
GM_DEFAULT_USER_PASSWORD
GM_APP_URL
```

- Change the admin password after first login.
- Keep `config`, `db`, and `helpers` protected from direct web access.

## Known Notes

- The default local admin login is intentionally simple for testing: `admin` / `password`.
- Some API routes return `400` if called without required POST/form data. This is expected.
- The old disconnected `mail/` notification module was removed before archiving because no active app code referenced it.
- `git` was not available on PATH in the shell used during validation, so `git status` could not be checked there.
