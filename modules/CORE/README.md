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
| Subscriptions | `Models/SubscriptionPlan.php`, `Models/TenantSubscription.php`, `Services/SubscriptionService.php`, `Controllers/SubscriptionController.php`, `Pages/Subscriptions/*` |
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

### Module permissions
The CORE module owns and seeds permissions for every sellable module. For **Personal
Accounting** it seeds:

```
personal-accounting.view
personal-accounting.transactions.{view,create,update,delete}
personal-accounting.accounts.{view,manage}
personal-accounting.budgets.{view,manage}
personal-accounting.goals.{view,manage}
personal-accounting.reports.view
```

These appear in **Access Control → Permissions** and can be assigned to any role like any other
permission. The Personal Accounting routes are gated by `can:personal-accounting.view`.

## Subscriptions (managed by CORE)

CORE also manages **module subscriptions**. A `SubscriptionPlan` defines a sellable plan for a
module and the set of permissions it grants. A `TenantSubscription` links a tenant to the plan.

Two Personal Accounting plans are seeded:
- **Personal Accounting Lite** (৳399/mo) — core tracking, accounts, budgets, recurring.
- **Personal Accounting Pro** (৳799/mo) — adds savings goals, loans, advanced reports.

`SubscriptionService` provides:
- `resolvePermissions(tenantId, module)` — the effective permission names for a tenant's active plan.
- `subscribe(tenant, plan, module, ?overrides)` — assign a plan and sync its permissions onto the
  tenant's users (so CORE can adjust what a subscription grants).
- `tenantHasPermission(tenantId, module, permission)` — check access.
- `cancel` (via `SubscriptionController`) — revokes the module permissions from the tenant's users.

Manage everything at **Workspace → Subscriptions** (assign a plan to a tenant, view active
subscriptions, cancel).

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
