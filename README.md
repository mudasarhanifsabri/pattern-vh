# Pattern RMS

A focused Laravel ERP foundation with no business modules yet.

All future screens must follow [the ERP UI guidelines](docs/UI_GUIDELINES.md).

Planned features are tracked in [Pending Features](docs/PENDING_FEATURES.md).

## Included

- Laravel 13 with Blade, Tailwind CSS, Alpine.js, and Vite
- Laravel Breeze authentication and profile management
- Spatie Roles & Permissions with a Super Admin gate bypass
- Responsive admin dashboard with sidebar, topbar, footer, and role-aware menu area
- User management module with users, roles, permissions, role assignment, and activity log foundation
- Owner module with owner records, identity document upload, bank details, blacklist flag, and notes history
- Installable PWA shell with a privacy-safe offline fallback
- MySQL application database
- AWS S3 as the default filesystem for media and documents
- Environment-driven Super Admin seeder

## First Run

1. Create a MySQL database named `erp_base`, or change `DB_DATABASE` in `.env`.
2. Set the MySQL credentials, AWS credentials, bucket, region, and Super Admin values in `.env`.
3. Replace the example `SUPER_ADMIN_PASSWORD` before seeding.
4. Run:

```bash
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Sign in with the `SUPER_ADMIN_EMAIL` and `SUPER_ADMIN_PASSWORD` configured in `.env`.

## Access Control

Seeded roles: `Super Admin`, `Owner`, `Tenant`, `Operations Team`, `Cleaner`, and `Technician`.

Admin screens:

- `/admin/users`
- `/admin/roles`
- `/admin/permissions`
- `/admin/activity-logs`

Owner module:

- `/owners`
- `/owners/create`

Owner records are ready for future unit assignment. The Units module is not built yet, so ownership attachment will be added when unit registration is introduced.

Portal permissions are seeded as `portal.owner`, `portal.tenant`, `portal.operations`, `portal.cleaner`, and `portal.technician` so each role can receive its own portal when those modules are built.

## Storage Rule

Use Laravel's default `Storage` facade for all future uploads. The default disk is `s3`, so calls such as `Storage::put(...)` store files in AWS S3. Do not write uploaded media or documents directly under `public/`.

## Development

Tests use an in-memory SQLite database and do not require MySQL or AWS:

```bash
php artisan test
npm run build
```
