# Hisabiya SAAS — CORE & Personal Accounting Modules
### Complete Feature, Operation & Database Reference

> **Version:** 1.0 · **Stack:** Laravel 12 · Inertia · Vue 3 · Tailwind CSS 4 · Chart.js · Spatie Permission · Spatie Activitylog
> **Architecture:** Modular Monolith (each module fully independent), SOLID, constructor injection throughout.
> **Scope:** This document covers the **CORE** module and the **Personal Accounting** module end-to-end.

---

## Table of Contents

1. [Architecture & Conventions](#1-architecture--conventions)
2. [CORE Module Overview](#2-core-module-overview)
3. [CORE — Features & Operations](#3-core--features--operations)
4. [CORE — Database Tables](#4-core--database-tables)
5. [Personal Accounting Module Overview](#5-personal-accounting-module-overview)
6. [Personal Accounting — Features & Operations](#6-personal-accounting--features--operations)
7. [Personal Accounting — Database Tables](#7-personal-accounting--database-tables)
8. [Permissions & Subscriptions Matrix](#8-permissions--subscriptions-matrix)
9. [Automated Jobs & Scheduled Tasks](#9-automated-jobs--scheduled-tasks)
10. [How the Two Modules Interact](#10-how-the-two-modules-interact)

---

## 1. Architecture & Conventions

### Modular Monolith layout
```
app/
├── Models/User.php                  # extended with ACL + Activity Log traits + tenant relation
├── Providers/ModuleServiceProvider.php  # auto-discovers + registers every modules/*/Providers
├── Support/Navigation/ModuleNavigation.php  # drives the main sidebar (permission-aware)
├── Http/Middleware/HandleInertiaRequests.php # shares auth, navigation, flash
modules/
├── CORE/                            # platform core + subscriptions
└── PersonalAccounting/              # personal finance module
```

### Module internal layering (SOLID)
| Layer | Responsibility |
|-------|----------------|
| `Routes/web.php` | Route registration, permission-gated with `can:*` middleware |
| `Http/Controllers/` | Thin HTTP orchestration, validates + delegates |
| `Services/` | Domain/business logic |
| `Actions/` | Single-responsibility use cases |
| `Repositories/` | Data-access queries (behind interfaces) |
| `Models/` | Eloquent models, casts, relations, scopes |
| `Traits/` | `HasTenant`, `HasUser`, `TenantScope` |
| `Jobs/` | Queued work |
| `Database/Migrations` | Schema (idempotent) |
| `Resources/js/` | Inertia pages, components, composables |

### Conventions
- Inertia page namespacing: `Inertia::render('CORE::Page/Name')`, `Inertia::render('PersonalAccounting::Page/Name')`.
- Every **Personal Accounting** table carries `tenant_id` + `user_id`.
- **Tenant isolation** is enforced automatically via the `TenantScope` global scope (`HasTenant` trait) — no cross-tenant leakage.
- All validation in controllers/Form Requests; business rules in services; money stored as `decimal(18,2)`, BDT default.

---

## 2. CORE Module Overview

The **CORE** module is the foundation of the SAAS platform. It owns:

- 🖥️ **Admin Dashboard** (premium, chart-driven)
- 🔐 **Access Control (ACL)** — Users, Roles, Permissions (Spatie Permission)
- 🏢 **Tenant management** (multi-tenant organisations)
- 🧾 **Activity Logs** (Spatie Activitylog audit trail)
- 💳 **Subscriptions** — module plans & tenant plan assignment (also manages Personal Accounting's plans)

---

## 3. CORE — Features & Operations

### 3.1 Admin Dashboard (`/dashboard`)
| Feature | Operation |
|---------|-----------|
| Hero banner | Time-based greeting, live "platform healthy" pulse, MRR highlight card |
| KPI cards | Revenue (MRR), Tenants, Users, Trials — each with a sparkline |
| Revenue overview | 12-month MRR **area chart** (hand-crafted SVG) |
| Tenant status donut | Active / Trial / Suspended distribution |
| Platform growth | 14-day new-tenant chart |
| Plan distribution | Tenants by subscription plan |
| Top tenants table | Largest workspaces by user count |
| Recent activity feed | Latest audit events |
| Quick-add actions | Jump to create tenant / user / role |

**Backing service:** `DashboardService` computes MRR, revenue delta %, conversion %, revenue series, status/plan breakdowns, top tenants.

### 3.2 Access Control — Users (`/admin/users`)
| Operation | Notes |
|-----------|-------|
| List users | Search, filter by role/tenant/status, sort, paginate |
| Create user | Name, email, phone, tenant, active flag, roles, password |
| Edit user | Update profile, change tenant, toggle roles/active |
| Delete user | Blocked on self-deletion |
| Assign roles | Multi-select from seeded roles |

### 3.3 Access Control — Roles (`/admin/roles`)
| Operation | Notes |
|-----------|-------|
| List roles | Search, sort, permission & user counts |
| Create/Edit role | Name + **grouped permission builder** (checkboxes per resource) |
| Sync permissions | Replace role's permission set atomically |
| Delete role | `super-admin` is protected |
| Audit | Role create/update/delete/permission changes logged |

### 3.4 Access Control — Permissions (`/admin/permissions`)
| Operation | Notes |
|-----------|-------|
| List permissions | Search, sort, roles count |
| Create/Edit | `resource.action` naming, e.g. `user.view` |
| Delete | Removes from all roles |
| Audit | Permission lifecycle logged |

### 3.5 Tenants (`/admin/tenants`)
| Operation | Notes |
|-----------|-------|
| List tenants | Search, filter by status, sort, paginate |
| Create tenant | Name, auto-slug, contact, address, currency, timezone, status, plan |
| Edit tenant | Update all tenant attributes |
| Delete tenant | Cascade nulls users' tenant |
| User count | Shown per tenant |

### 3.6 Activity Logs (`/admin/activity-logs`)
| Operation | Notes |
|-----------|-------|
| List | Search, filter by event type, sort, paginate |
| Export | CSV / PDF |
| Causer tracking | Shows who performed each action |

### 3.7 Subscriptions (`/admin/subscriptions`)
| Operation | Notes |
|-----------|-------|
| View plans | Cards with price, features, permissions granted, subscriber count |
| Assign plan | Pick tenant + plan + module → creates subscription + syncs permissions to tenant users |
| Cancel subscription | Revokes the module's permissions from tenant users |
| View active subscriptions | Table of tenant → module → plan → status |

**Backing service:** `SubscriptionService` — `resolvePermissions()`, `subscribe()`, `tenantHasPermission()`, `syncPermissionsToUsers()`, `plansForModule()`.

---

## 4. CORE — Database Tables

### `tenants` (CORE)
Represents an organisation / workspace on the platform.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | |
| slug | string UNIQUE | auto-generated from name |
| email | string null | |
| phone | string null | |
| address | text null | |
| currency | string | default `BDT` |
| timezone | string | default `Asia/Dhaka` |
| status | string | `active` \| `trial` \| `suspended` |
| plan | string | `free` \| `starter` \| `pro` \| `enterprise` (platform plan) |
| trial_ends_at | timestamp null | |
| settings | json null | |
| logo | string null | |
| created_at / updated_at | timestamps | |

**Relations:** `users()`, `subscriptions()` (via TenantSubscription).

### `users` (app-level, extended by CORE)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | FK null → tenants | added by CORE |
| name | string | |
| email | string UNIQUE | |
| password | string (hashed) | |
| is_active | boolean | default true |
| phone | string null | |
| email_verified_at / timestamps | | |

**Traits:** `HasRoles`, `HasPermissions`, `LogsActivity` (Spatie), plus `tenant()` relation.

### `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`
Spatie Permission standard tables (guard `web`). Used for the full ACL.

### `activity_log`
Spatie Activitylog standard table (`log_name`, `description`, `subject`, `causer`, `event`, `properties`).

### `subscription_plans` (CORE)
A sellable plan for a module.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| module | string | e.g. `personal_accounting` |
| name | string | e.g. `Personal Accounting Pro` |
| slug | string UNIQUE | |
| description | string null | |
| price_monthly | decimal(12,2) | |
| price_yearly | decimal(12,2) | |
| permissions | json null | permission names granted |
| features | json null | sales bullets |
| is_active | boolean | default true |
| timestamps | | |

**Relation:** `subscriptions()`.

### `tenant_subscriptions` (CORE)
Links a tenant to a plan.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | FK → tenants (cascade) | |
| plan_id | FK → subscription_plans (cascade) | |
| module | string | |
| status | string | `active` \| `canceled` \| `expired` |
| starts_at | timestamp null | |
| ends_at | timestamp null | |
| auto_renew | boolean | default true |
| timestamps | | UNIQUE(tenant_id, module) |

**Relations:** `tenant()`, `plan()`. Helper: `isActive()`.

---

## 5. Personal Accounting Module Overview

The **Personal Accounting** module is a full personal finance manager inside Hisabiya:

- 📊 **Dashboard** (balance hero, charts, budgets, recent activity)
- 💸 **Transactions** (income / expense / **transfer between two accounts**)
- 🏦 **Accounts** (cash, bank, mobile banking)
- 🎯 **Budgets** (per-category limits with progress colouring)
- 🐷 **Savings Goals** (contribute / withdraw / projections)
- 💳 **Loans** (lend & borrow with contacts) + **Contacts**
- 📈 **Reports & Analytics**
- 🔁 **Recurring transactions** (scheduled + queued job)

---

## 6. Personal Accounting — Features & Operations

### 6.1 Dashboard (`/personal/dashboard`)
| Feature | Operation |
|---------|-----------|
| Balance hero card | Total across all accounts + income/expense/net for the month |
| Quick-add | Floating FAB → opens transaction slide-over |
| Income vs Expense chart | Current-month bar chart |
| Recent transactions | Last 10 with category icon + colour |
| Top budgets | Top 3 budgets with progress bars |
| Auto setup | Seeds system categories + a default cash account on first visit |

### 6.2 Transactions (`/personal/transactions`)
| Operation | Notes |
|-----------|-------|
| List | Filter by date range, category, type, account; search notes; sort; paginate |
| Create | Type toggle (Income green / Expense red / **Transfer blue**) |
| **Transfer** | **Two accounts: From (`account_id`) + To (`to_account_id`)** — debits source, credits destination atomically |
| Inline edit | Row click → slide-out panel, balance auto-reconciled on update |
| Bulk delete | Select rows → confirm → balances reversed |
| Export | CSV and PDF |
| Recurring | Toggle + frequency (daily/weekly/monthly/yearly) → creates a recurring template |
| Attachment | File upload field (path stored) |

**Backing:** `CreateTransactionAction`, `UpdateAccountBalanceAction`, `PersonalTransactionService`, `PersonalTransactionRepository`.

### 6.3 Accounts (`/personal/accounts`)
| Feature | Operation |
|---------|-----------|
| Card grid | Each account: name, type icon, balance, default flag |
| Add account | Type (cash/bank/mobile banking), opening balance, currency, colour, default |
| Total balance | Summary strip |
| Account detail | Per-account transaction history |
| Delete | Removes account |

### 6.4 Budgets (`/personal/budgets`)
| Feature | Operation |
|---------|-----------|
| Budget cards | Spent vs limit, remaining |
| Progress colour | Green <70%, **orange >70%**, red ≥100% |
| Add budget | Category + amount + period (daily/weekly/monthly/yearly) + start date |
| Delete | Remove budget |

**Backing:** `PersonalBudgetService` (`createBudget`, `getBudgetProgress`, `alertOverBudget`), `PersonalBudgetRepository` (`budgetVsActual`).

### 6.5 Savings Goals (`/personal/goals`)
| Feature | Operation |
|---------|-----------|
| Goal cards | Progress circle + saved/target + deadline |
| Contribute | Add amount, auto-marks completed at target |
| Withdraw | Reduce saved amount (capped at 0) |
| Projection | Estimated completion date given a monthly contribution |
| Add goal | Name, target, deadline, colour |

**Backing:** `PersonalSavingsGoalService` (`contribute`, `withdraw`, `calculateProjection`).

### 6.6 Loans & Contacts (`/personal/loans`, `/personal/contacts`)
| Feature | Operation |
|---------|-----------|
| Loan direction | **"I lent money"** (asset) / **"I borrowed money"** (liability) |
| Create loan | Name, contact, principal, interest rate, start/due dates, payment frequency + amount, optional account (moves principal via a transaction) |
| Summary | To receive (lent) / To repay (borrowed) / Net receivable |
| Loan cards | Progress circle, remaining vs principal, overdue badge, payoff projection, payment count |
| Record payment | Reduces remaining balance, logs a payment, optionally moves money via an account |
| Contacts | Add people/businesses (person/business, phone, email, address), loan count per contact |

**Backing:** `PersonalLoanService` (`create`, `recordPayment`, `projection`).

### 6.7 Reports (`/personal/reports`)
| Feature | Operation |
|---------|-----------|
| Date range picker | Filter the whole report |
| Summary cards | Income / Expense / Net |
| Monthly trend | Line chart (income vs expense, 12 months) |
| Category breakdown | Doughnut charts (expense & income by category) |
| Net worth history | Line chart of cumulative net |
| Export | CSV |

**Backing:** `ReportController` + `GeneratePersonalReportAction` + repositories (`monthlyTrend`, `sumByCategory`, `sumByType`, `netWorthHistory`).

### 6.8 Recurring Transactions
- A `PersonalRecurringTransaction` template stores frequency, amount, account, category, `next_run_at`.
- `RecurringTransactionJob` (queued, scheduled **daily** by the module provider) processes all `due()` templates via `ProcessRecurringTransactionAction`.
- Each run creates a real `PersonalTransaction` (balance updated) and advances `next_run_at`.

---

## 7. Personal Accounting — Database Tables

### `personal_accounts`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | FK → tenants | |
| user_id | FK → users | |
| name | string | |
| type | string | `cash` \| `bank` \| `mobile_banking` |
| currency | string | default `BDT` |
| balance | decimal(18,2) | |
| is_default | boolean | |
| icon | string null | |
| color | string | default `#6366f1` |
| timestamps | | |

**Relations:** `transactions()`, `recurringTransactions()`.

### `personal_categories`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id / user_id | FK | |
| name | string | |
| type | string | `income` \| `expense` |
| icon / color | string | |
| parent_id | FK null → self | sub-categories |
| is_system | boolean | seeded defaults |
| timestamps | | |

**Relations:** `parent()`, `children()`, `transactions()`, `budgets()`.

### `personal_transactions`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id / user_id | FK | |
| account_id | FK → personal_accounts | source account |
| **to_account_id** | FK null → personal_accounts | destination (transfers) |
| category_id | FK null → personal_categories | |
| type | string | `income` \| `expense` \| `transfer` |
| amount | decimal(18,2) | |
| note | text null | |
| date | date | |
| is_recurring | boolean | |
| recurring_id | FK null → personal_recurring_transactions | |
| attachment_path | string null | |
| timestamps | | |

**Relations:** `account()`, `toAccount()`, `category()`, `recurring()`.

### `personal_recurring_transactions`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id / user_id | FK | |
| name | string | |
| account_id | FK → personal_accounts | |
| category_id | FK null → personal_categories | |
| type | string | |
| amount | decimal(18,2) | |
| template_data | json null | extra attrs for generated txn |
| frequency | string | `daily` \| `weekly` \| `monthly` \| `yearly` |
| next_run_at | timestamp null | |
| last_run_at | timestamp null | |
| is_active | boolean | |
| timestamps | | |

**Relations:** `account()`, `category()`, `transactions()`. Scope: `due()`.

### `personal_budgets`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id / user_id | FK | |
| category_id | FK → personal_categories | |
| amount | decimal(18,2) | |
| period | string | `daily` \| `weekly` \| `monthly` \| `yearly` |
| start_date | date | |
| end_date | date null | |
| timestamps | | |

**Relation:** `category()`.

### `personal_savings_goals`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id / user_id | FK | |
| name | string | |
| target_amount | decimal(18,2) | |
| current_amount | decimal(18,2) | default 0 |
| deadline | date null | |
| icon / color | string | |
| status | string | `active` \| `completed` \| `paused` |
| timestamps | | |

Helpers: `progressPercent()`, `isCompleted()`.

### `personal_contacts`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id / user_id | FK | |
| name | string | |
| type | string | `person` \| `business` |
| phone / email / address / notes | string null / text null | |
| timestamps | | |

**Relation:** `loans()`.

### `personal_loans`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id / user_id | FK | |
| name | string | |
| direction | string | `lent` \| `borrowed` |
| counterparty | string null | legacy free-text |
| contact_id | FK null → personal_contacts | linked contact |
| account_id | FK null → personal_accounts | money source/destination |
| principal_amount | decimal(18,2) | |
| interest_rate | decimal(8,4) | annual % |
| interest_type | string | `simple` \| `compound` \| `flat` |
| remaining_balance | decimal(18,2) | |
| total_paid | decimal(18,2) | |
| start_date / due_date / next_payment_date | date null | |
| payment_frequency | string | weekly/biweekly/monthly/quarterly/yearly |
| payment_amount | decimal(18,2) | |
| status | string | `active` \| `completed` \| `overdue` \| `closed` |
| currency | string | default `BDT` |
| notes | text null | |
| timestamps | | |

**Relations:** `contact()`, `payments()`. Helpers: `isLiability()`, `isAsset()`, `progressPercent()`, `isOverdue()`, `isSettled()`.

### `personal_loan_payments`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id / user_id | FK | |
| loan_id | FK → personal_loans (cascade) | |
| amount | decimal(18,2) | |
| principal_part | decimal(18,2) | |
| interest_part | decimal(18,2) | |
| paid_at | date | |
| note | text null | |
| timestamps | | |

**Relation:** `loan()`.

---

## 8. Permissions & Subscriptions Matrix

### CORE permissions (seeded)
```
dashboard.view
activity-log.view
user.view  user.create  user.update  user.delete
role.view  role.create  role.update  role.delete
permission.view  permission.create  permission.update  permission.delete
tenant.view  tenant.create  tenant.update  tenant.delete
```
**Seeded roles:** `super-admin` (bypasses all gates), `admin`, `manager`, `user`.

### Personal Accounting permissions (owned & seeded by CORE)
```
personal-accounting.view
personal-accounting.transactions.view | create | update | delete
personal-accounting.accounts.view | manage
personal-accounting.budgets.view | manage
personal-accounting.goals.view | manage
personal-accounting.reports.view
personal-accounting.loans.view | manage
personal-accounting.contacts.view | manage
```
**Route gating:** all `/personal/*` routes require `can:personal-accounting.view`; loans require `loans.view/manage`, contacts require `contacts.view/manage`.

### Subscription plans (managed by CORE)
| Plan | Module | Price | Grants |
|------|--------|-------|--------|
| Personal Accounting **Lite** | personal_accounting | ৳399/mo · ৳3,990/yr | view, transactions.*, accounts.*, budgets.*, goals.view, reports.view |
| Personal Accounting **Pro** | personal_accounting | ৳799/mo · ৳7,990/yr | everything in Lite **+** goals.manage, loans.*, contacts.* |

Assigning a plan (CORE → Subscriptions) **syncs the granted permissions onto every user of that tenant**, so the ACL is the enforcement mechanism for a subscription.

---

## 9. Automated Jobs & Scheduled Tasks

| Job / Schedule | Cadence | Purpose |
|----------------|---------|---------|
| `RecurringTransactionJob` (Personal Accounting) | **Daily** (`$schedule->job(...)->daily()`) | Processes due recurring templates via `ProcessRecurringTransactionAction`; creates transactions + updates balances |
| `CORESeeder` | Manual / deploy | Seeds permissions, roles, tenants, subscription plans, super-admin |

---

## 10. How the Two Modules Interact

```
        ┌─────────────────────────────────────────────┐
        │                 CORE MODULE                 │
        │  Tenants · Users · Roles · Permissions      │
        │  Activity Logs · Subscriptions · Dashboard  │
        └───────────────┬─────────────────────────────┘
                        │ owns & seeds permissions
                        │ assigns subscription plans
                        │ enforces tenant isolation
                        ▼
   ┌──────────────────────────────────────────────────┐
   │              PERSONAL ACCOUNTING MODULE          │
   │  Accounts · Transactions · Transfers · Budgets   │
   │  Goals · Loans · Contacts · Reports · Recurring  │
   └──────────────────────────────────────────────────┘
```

- **Tenant isolation:** every Personal Accounting query inherits `tenant_id` from the authenticated user (via `HasTenant` → `TenantScope`), so a user only ever sees their own tenant's data.
- **ACL:** Personal Accounting routes are gated by CORE-managed permissions; CORE admins can adjust who can access the module from **Access Control**.
- **Subscriptions:** CORE decides which module plan a tenant holds and translates it into granted permissions (Lite vs Pro).
- **Shared identity:** both modules reference the same `users` and `tenants` tables; the Personal Accounting models use the CORE `Tenant` model.
- **UI:** the Personal Accounting module renders inside the CORE `AppLayout` shell and adds its own in-module sidebar; its pages resolve via the `PersonalAccounting::` Inertia namespace.

---

*Generated as a living reference for the Hisabiya platform. Re-run the CORE seeder and migrate after upgrading to keep permissions/plans in sync.*
