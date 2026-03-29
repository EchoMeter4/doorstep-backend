# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Run dev server (Laravel + queue + logs + Vite, all concurrent)
composer run dev

# Run tests (clears config cache first)
composer run test

# Run a single test file
php artisan test tests/Feature/ExampleTest.php

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Fresh migrate + seed
php artisan migrate:fresh --seed

# Lint/format code
./vendor/bin/pint

# Build frontend assets
npm run build

# Full project setup from scratch
composer run setup
```

## Architecture

This is a **Laravel 12 access control backend** (PHP 8.2+) for managing visitor passes, credentials, and zone-based security. The project is in early development — the schema is well-defined but most controllers beyond auth are not yet implemented.

### Core Domain Model

The system manages physical access to **Zones** within **Organizations**:

- **Users** → assigned **Roles** (many-to-many via `role_user`)
- **Roles** → grant access to **Zones** (many-to-many via `role_zone`)
- **Visitors** → issued **Passes** (one-to-many)
- **Passes** → valid for specific **Zones** (many-to-many via `pass_zone`)
- **Users** → hold **Credentials** (codes) and **LicensePlates**
- **AccessLogs** → audit trail for every access attempt (user, zone, action type, authorization result)

### Authentication

Dual-mode via Laravel Sanctum (`auth:sanctum` guard handles both):

- **Token-based (API):** `POST /api/login` → returns Sanctum personal access token; protected routes use `Bearer` header
- **Session-based (Web):** `POST /login` → sets session cookie

Both modes share the same protected route group in `routes/api.php`. The `auth:sanctum` middleware accepts either.

### Current App Code

Only a minimal set of PHP files exist in `app/`:
- `Http/Controllers/AuthController.php` — register, login, tokenLogin, me, logout, closeAccount
- `Http/Resources/UserResource.php` — JSON transform for User model
- `Models/User.php` — with `HasApiTokens` trait

All other domain models (Visitor, Organization, Role, Zone, Pass, Credential, LicensePlate, AccessLog) exist only as migrations — their Eloquent Model classes have not been created yet.

### Database

- **Local dev:** MySQL (`doorstep` database, configured in `.env`)
- **Tests:** SQLite in-memory (`:memory:`, configured in `phpunit.xml`)
- **Seeder:** Creates one test user: `test@test.com` / `test`

Migrations are in `database/migrations/` and define the full schema including all pivot tables and the `access_logs` audit table.
