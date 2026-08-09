# PersonalAccounting Module

The **PersonalAccounting** module is a fully independent module (Modular Monolith, no DDD) that powers
personal money management inside Hisabiya: income, expenses, categories, budgets, savings goals,
recurring transactions and analytics.

> **Scope of this initialisation:** the complete **data layer**. UI routes/controllers are reserved
> and will be added when the interface is built.

## Layers (SOLID, constructor injection everywhere)

```
Providers/PersonalAccountingServiceProvider.php   # bindings, migrations, schedule
Database/Migrations/                               # 7 tables, all prefixed personal_
Models/                                            # Eloquent models (casts, relations)
Traits/                                            # HasTenant, HasUser, TenantScope
Interfaces/                                        # RepositoryInterface, ServiceInterface + contracts
Repositories/                                      # query layer
Services/                                          # domain services
Actions/                                           # single-responsibility actions
Jobs/                                              # RecurringTransactionJob (queued)
Routes/web.php                                     # reserved for the UI
```

## Data model

| Table | Purpose |
|-------|---------|
| `personal_accounts` | wallets / bank / mobile-banking accounts (balance, type, default flag) |
| `personal_categories` | income & expense categories, nestable via `parent_id` |
| `personal_recurring_transactions` | scheduled transaction templates (daily/weekly/monthly/yearly) |
| `personal_transactions` | income / expense / transfer ledger entries |
| `personal_budgets` | per-category limits for a period |
| `personal_savings_goals` | savings targets with progress |
| `personal_loans` | money borrowed (liability) or lent (asset), with interest & amortisation |

Every table carries `tenant_id` + `user_id`. **Tenant isolation is enforced by a global
`TenantScope`** applied through the `HasTenant` trait — no query can leak across tenants.

## Key wiring

- **Repositories** implement `RepositoryInterface` (+ domain contracts):
  - `PersonalTransactionRepository` → `findByDateRange`, `sumByCategory`, `sumByType`, `monthlyTrend`
  - `PersonalAccountRepository` → `balanceSummary`, `recentTransactions`
  - `PersonalBudgetRepository` → `budgetVsActual(userId, month, year)`
- **Services** implement `ServiceInterface` (+ domain contracts):
  - `PersonalTransactionService` → create/update/delete; keeps the account balance in sync via
    `UpdateAccountBalanceAction` inside DB transactions
  - `PersonalBudgetService` → `createBudget`, `getBudgetProgress`, `alertOverBudget`
  - `PersonalSavingsGoalService` → `contribute`, `withdraw`, `calculateProjection`
- **Actions** (single responsibility):
  - `CreateTransactionAction`, `UpdateAccountBalanceAction`, `ProcessRecurringTransactionAction`,
    `GeneratePersonalReportAction`
- **Job:** `RecurringTransactionJob` is queued and runs every day (registered in the provider via
  `$schedule->job(new RecurringTransactionJob)->daily()`); it dispatches due recurring templates
  through `ProcessRecurringTransactionAction`.

## UI (Vue 3 + Inertia + Tailwind CSS 4 + Chart.js)

All frontend files live under `Resources/js/`.

**Layout** — `Layouts/ModuleLayout.vue` renders the global app chrome plus a
Personal Accounting **in-module sidebar** (Dashboard / Transactions / Accounts /
Budgets / Goals / Reports) and the current user chip.

**Composables** — `Composables/useTransactions.ts` (shared slide-over state for
create/edit + list navigation), `Composables/useBudgets.ts` (create + progress
colouring), `Composables/useMoney.ts` (BDT formatting).

**Components** — `SlideOver`, `TransactionForm` (type toggle, category picker,
recurring toggle, attachment), `AddAccountModal`, `AddBudgetModal`, `ConfirmDialog`,
`ProgressBar`, `ProgressCircle`, `CategoryIcon`, `MoneyText`, `TypeBadge`,
`BaseChart` (Chart.js wrapper with lifecycle management).

**Pages**
- `Dashboard` — hero balance card, income-vs-expense bar chart, recent transactions, top-3 budgets, floating quick-add.
- `Transactions` — filterable table (date/category/type/account), slide-out inline edit, bulk delete, CSV/PDF export.
- `Accounts` + `Accounts/Show` — account card grid + per-account history.
- `Budgets` — progress bars (green/amber/red), add/delete.
- `Goals` — progress circles, contribute/withdraw, add modal.
- `Reports` — date-range picker, monthly trend line chart, income/expense doughnuts, net-worth chart, CSV export.

Routes are registered in `Routes/web.php` under the `/personal` prefix; controllers in
`Http/Controllers/` feed each Inertia page and validate input.

## Install / verify

```bash
php artisan migrate            # creates the 7 personal_* tables
php artisan test --filter=PersonalAccountingModuleTest
```

Interface bindings are registered in `PersonalAccountingServiceProvider::register()`, so repositories
and services can be injected by contract throughout the app.
