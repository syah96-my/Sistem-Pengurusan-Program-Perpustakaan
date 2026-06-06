# Sistem Pengurusan Program Perpustakaan

Legacy PHP/XAMPP program management system for library-style organizations.

The archive version is sanitized for reuse: credentials are read from environment variables, the SQL dump contains no participant/program/user data, and the sample library tree is generic.

## Requirements

- PHP 7.2+ with PDO MySQL
- MySQL or MariaDB
- Apache/XAMPP, or any PHP-capable web server

## Setup

1. Create a database named `program_register`.
2. Import `db/program_register.sql`.
3. Set environment variables from `.env.example`.
4. Create the first admin account:

```bash
php config/create_admin.php
```

5. Open the app at `/program-register/login/login.php` when using the default XAMPP folder name.

Default setup values are intentionally placeholders. Change `GM_ADMIN_PASSWORD` and `GM_SECRET_KEY` before using the system anywhere outside local testing.

## Local XAMPP Preview

Place the project folder under `htdocs/program-register`, then open:

```text
http://localhost/program-register/login/login.php
```

The app detects the project folder name and prefixes internal links, assets, and API calls automatically.

## Hierarchy Rules

- A root or main library can create child libraries.
- Child libraries can create their own child libraries.
- Maximum hierarchy depth is 4 layers.
- Programs store their immediate parent library automatically from the selected library.
- A program from a sub-library is verified by its immediate parent library.
- Statistics and dashboards show a user's own library and its descendants. Super admins can see all data.

## Fresh Data

The included SQL file keeps only:

- Generic roles
- Generic library types
- Generic program types
- Generic scales, platforms, and target groups
- A sample 4-layer library hierarchy

It does not include users, programs, participants, notes, or API keys. Use `config/create_admin.php` to create the first user.

## Existing Database Upgrade

If you are upgrading an older copy of this system instead of doing a fresh import, back up the database first, then run:

```bash
mysql -u root program_register < db/migrations/2026_05_31_readable_schema_names.sql
```

This migration renames unclear program, participant, note, and statistics columns to readable names and adds indexes used by the dashboard/report tables.
