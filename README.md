# Hisabiya — Sales & Accounting SAAS

A multi-tenant **Sales & Accounting** platform for the Bangladeshi and international markets.
Built with **Laravel 12**, **Inertia**, **Vue 3**, and **Tailwind CSS v4**, architected as a
**Modular Monolith** (each module is independent).

## Modules

| Module | Status | Description |
|--------|--------|-------------|
| **CORE** | ✅ Complete | Admin dashboard, ACL (users/roles/permissions via Spatie), Tenants CRUD, Activity logs, reusable DataTable |
| Personal Accounting | ⏳ Planned | — |
| Business Accounting | ⏳ Planned | — |
| POS | ⏳ Planned | — |

> **Important:** only the **CORE** module has been built so far. No other modules exist yet.

## Tech Stack

- Laravel 12 (framework skeleton: `laravel/vue-starter-kit`)
- Inertia + Vue 3 (TypeScript)
- Tailwind CSS **v4** (`@tailwindcss/vite`, CSS-first theme) + shadcn-vue components
- Spatie **laravel-permission** (ACL) & **laravel-activitylog** (audit trail)

## Quick start

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
touch database/database.sqlite

php artisan migrate
php artisan db:seed --class="Modules\CORE\Database\Seeders\CORESeeder"

npm run build        # production assets
php artisan serve    # or: npm run dev (dev server)
```

### Demo login

- **Email:** `admin@hisabiya.test`
- **Password:** `password`

## Project structure

```
app/
├── Console/Commands/           # module scaffolding commands
├── Support/Navigation/         # ModuleNavigation (drives the sidebar)
├── Providers/ModuleServiceProvider.php  # auto-registers module providers
modules/
└── CORE/                       # the only module (independent)
    ├── Http/Controllers/
    ├── Models/
    ├── Requests/
    ├── Services/
    ├── Database/{Migrations,Seeders}/
    ├── Resources/js/{Pages,Components,Layouts}/
    └── Routes/web.php
```

See `modules/CORE/README.md` for the CORE module details.

## Conventions

- Module Inertia pages: `Inertia::render('CORE::Page/Name')`.
- Routes are registered by each module's service provider and are permission-gated.
- New modules can be scaffolded with `php artisan make:module <Name>`.
