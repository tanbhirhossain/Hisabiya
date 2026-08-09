# CORE Module

The **CORE** module is the foundation of Hisabiya — a multi-tenant Sales & Accounting SAAS platform.
It is a fully independent module inside a Modular Monolith and owns the **Admin Dashboard**, the
**Access Control (ACL)** layer and **Tenant management**.

## What's inside

| Area | Files |
|------|-------|
| Admin Dashboard | `Resources/js/Pages/Dashboard/Index.vue` + `Http/Controllers/DashboardController.php` |
| Tenants (CRUD) | `Models/Tenant.php`, `Services/TenantService.php`, `Controllers/TenantController.php`, `Pages/Tenants/*` |
| Users (CRUD + ACL) | `Services/UserService.php`, `Controllers/UserController.php`, `Pages/Users/*` |
| Roles (CRUD + permission assignment) | `Services/RoleService.php`, `Controllers/RoleController.php`, `Pages/Roles/*` |
| Permissions (CRUD) | `Services/PermissionService.php`, `Controllers/PermissionController.php`, `Pages/Permissions/*` |
| Activity Logs | `Services/ActivityLogService.php`, `Controllers/ActivityLogController.php`, `Pages/ActivityLogs/*` |
| Reusable components | `Resources/js/Components/*` (DataTable, PageHeader, StatCard, StatusBadge, ConfirmDialog, FlashMessage, form components) |

## Layered architecture (SOLID)

- **Controllers** (`Http/Controllers`) — HTTP-only, thin orchestration, no business logic.
- **Services** (`Services`) — business logic, kept out of controllers/models.
- **Form Requests** (`Requests`) — validation + authorization per use case.
- **Models** (`Models`) — Eloquent models, casts, relations, activity logging.
- **Routes** (`Routes/web.php`) — all routes are permission-gated with `can:*` middleware.
- **Database** (`Database/Migrations`, `Database/Seeders`) — self-contained schema + seed data.

Module pages are resolved with the `CORE::` namespace, e.g. `Inertia::render('CORE::Dashboard/Index')`
(handled by the resolver in `resources/js/app.ts`).

## Reusable DataTable

`Resources/js/Components/DataTable.vue` is a generic, production-grade table with:

- Debounced **search**
- **Column sorting** (click headers)
- **Custom filters** (via `#filters` slot, e.g. role / tenant / status dropdowns)
- **Server-side pagination** (per-page selector + prev/next + numbered pages)
- **Export to Excel (.xlsx)** and **PDF** (libraries lazy-loaded on demand)
- Row action / cell slots (`#actions`, `#cell.<key>`)

## Permissions

Seeded permissions follow the `resource.action` convention:

```
dashboard.view            activity-log.view
user.view   user.create   user.update   user.delete
role.view   role.create   role.update   role.delete
permission.view ...       tenant.view   tenant.create   tenant.update   tenant.delete
```

Roles seeded: `super-admin` (bypasses all gates), `admin`, `manager`, `user`.
The `super-admin` bypass is registered as a `Gate::before` in `COREServiceProvider`.

## Setup

```bash
# migrate (base + CORE module migrations)
php artisan migrate

# seed ACL, demo tenant and the super admin
php artisan db:seed --class="Modules\CORE\Database\Seeders\CORESeeder"

# login
#   email: admin@hisabiya.test
#   password: password
```

> You can re-run the seeder any time — it is idempotent (`firstOrCreate`).
