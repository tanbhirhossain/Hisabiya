# Hisabiya — User Manual

A practical, end-to-end guide to using the **Hisabiya** Sales & Accounting SaaS platform.
This manual covers the two currently shipped modules: the **CORE** (admin) module and the
**Personal Accounting** module.

> **Demo login** — Email: `admin@hisabiya.test` · Password: `password`

---

## Table of Contents

1. [Getting Started](#1-getting-started)
2. [The Main Dashboard](#2-the-main-dashboard)
3. [CORE Module (Admin)](#3-core-module-admin)
   - Tenants
   - Users
   - Roles & Permissions
   - Subscriptions
   - Activity Logs
4. [Personal Accounting Module](#4-personal-accounting-module)
   - Dashboard
   - Accounts
   - Transactions (income / expense / transfer)
   - Importing bank statements (CSV)
   - Budgets
   - Savings Goals
   - Loans & Contacts
   - Reports & analytics
   - Recurring transactions
   - Notifications
5. [Common Tasks & How-To's](#5-common-tasks--how-tos)
6. [FAQ & Troubleshooting](#6-faq--troubleshooting)

---

## 1. Getting Started

1. Open the app in your browser (e.g. `http://localhost:8000`).
2. Log in with your email and password. The demo account is `admin@hisabiya.test` / `password`.
3. After login you land on the **Main Dashboard**.

The left sidebar has two areas:
- **Admin / Platform** (CORE): Dashboard, Tenants, Subscriptions, Users, Roles, Permissions, Activity Logs.
- **Personal Accounting** ("My Money"): Dashboard, Transactions, Recurring, Accounts, Budgets,
  Savings Goals, Loans, Contacts, Reports.

> Some menu items appear only if your role has the matching permission.

---

## 2. The Main Dashboard

The main (CORE) dashboard gives a platform-wide overview for admins:

- **Revenue (MRR)** — monthly recurring revenue across tenants, with trend.
- **Tenants / Users / Trials** — KPI cards with sparklines.
- **Revenue overview** — a 12-month MRR area chart.
- **Tenants by status** — active / trial / suspended donut.
- **Platform growth** — new tenants over the last 14 days.
- **Top tenants** — the largest workspaces.
- **Recent activity** — a live audit feed.

Nothing is editable here; it's a read-only command center.

---

## 3. CORE Module (Admin)

### 3.1 Tenants
Tenants are the organisations/workspaces using your platform.

- **View tenants** → Workspace → Tenants. Search, filter by status, sort.
- **Add a tenant** → "New Tenant". Fill in name, contact, currency, timezone, status, plan.
  - The *slug* is auto-generated from the name if left blank.
  - *Status*: `active` / `trial` / `suspended`.
  - *Plan*: `free` / `starter` / `pro` / `enterprise` (this is the platform's own plan field).
- **Edit / delete** a tenant from its row actions.

### 3.2 Users
- **View users** → Access Control → Users. Filter by role, tenant, status.
- **Add a user** → "New User". Set name, email, phone, tenant, active flag, password, and roles.
- **Edit / delete** users from the row actions. You cannot delete your own account.

### 3.3 Roles & Permissions
- **Roles** → Access Control → Roles. Create/edit a role and use the **grouped permission
  builder** (checkboxes) to grant capabilities.
- **Permissions** → Access Control → Permissions. Every capability in the system (e.g.
  `user.view`, `tenant.delete`, and module permissions like `personal-accounting.loans.manage`).
- The `super-admin` role bypasses all permission checks automatically and cannot be deleted.

### 3.4 Subscriptions
Subscriptions let you assign a **module plan** to a tenant. When you assign a plan, its
permissions are automatically granted to that tenant's users.

- **Workspace → Subscriptions** shows all plans and current assignments.
- **Assign plan** → choose a tenant, a plan (e.g. Personal Accounting Pro), and the module.
- **Cancel** a subscription to revoke those module permissions.

> Seeded plans: **Personal Accounting Lite** (৳399/mo) and **Personal Accounting Pro** (৳799/mo).

### 3.5 Activity Logs
- **Audit → Activity Logs** records create/update/delete events across the platform.
- Use the **Type** filter (Created / Updated / Deleted / Permissions) and search to find events.
- Export to CSV or PDF with the buttons in the toolbar.

---

## 4. Personal Accounting Module

This is the personal finance module. Open it from **"My Money"** in the sidebar.

### 4.1 Dashboard
The personal dashboard gives a quick snapshot:

- **Period switcher** — Today / This Week / This Month / Custom. Changes the whole view.
- **Total balance** — across all your accounts (cash, bank, mobile banking).
- **Income / Expenses / Net** for the selected period.
- **Net worth** — accounts + active savings goals − outstanding borrowed loans.
- **Savings rate** — % of income saved (green >20%, orange 10–20%, red <10%).
- **Spending velocity** — how fast you're spending vs your monthly budget.
- **Upcoming bills** — the next 5 recurring transactions due within 7 days.
- **Budgets** — top 3 budgets with progress.
- **Recent transactions** — your latest 10 movements.
- **Quick add** — the floating **+** button opens the transaction form.

### 4.2 Accounts
Manage your wallets / bank / mobile-banking accounts.

- **Add account** → "Add account". Choose type (Cash / Bank / Mobile Banking), opening balance,
  currency, colour, and whether it's the default.
- Each card shows the balance and a small **balance history** sparkline (click to expand).
- **Archive** an account from its card (hidden from normal lists; toggle "Show archived").
  You cannot archive your only active account.
- Click an account card to see its full **transaction history**.

### 4.3 Transactions
Record income, expenses, and transfers.

- **Add** → the slide-over form:
  - **Type**: Income (green) / Expense (red) / **Transfer** (blue).
  - **Transfer** requires **two accounts**: *From* and *To*. The money is moved between them.
  - **Amount**, **Account**, **Category**, **Date**, **Note**.
  - **Status**: Cleared (default) or Pending.
  - **Recurring**: optionally make it repeat (daily/weekly/monthly/yearly) with an **end
    condition** (never / on a date / after a number of occurrences).
  - **Attachment**: attach a receipt file.
- **Filter & search** — by date range, type, category, account, **min/max amount**, status
  (All/Cleared/Pending pills), or note text.
- **Edit** — click any row to reopen the slide-over; balances are reconciled automatically.
- **Bulk actions** — tick rows, then change status/category or delete them.
- **Export** — CSV or PDF of the current filtered list.
- **Duplicate warning** — if you add a transaction that matches an existing one (same
  account, amount, date), the app asks "Add anyway?".

### 4.4 Importing bank statements (CSV)
Import transactions from a bank CSV export:

1. **Transactions → Import** (or the "Import" action).
2. **Step 1 — Upload**: choose the target account and drop your CSV.
3. **Step 2 — Map columns**: the app auto-detects date/amount/description/type. Confirm or
   re-map each column. Preview the first 5 rows.
4. **Step 3 — Confirm**: review "Will import N transactions into [Account]".
5. **Step 4 — Result**: see how many were imported / skipped / failed, then view them.

> Needs the `personal-accounting.transactions.import` permission (included in Lite & Pro plans).

### 4.5 Budgets
Set monthly (or weekly/daily/yearly) limits per category.

- **New budget** → category, amount, period, start date.
  - **Rollover**: carry unused budget into the next period.
  - **Warning threshold**: choose when to warn (default 80%).
- Each budget card shows:
  - **Progress bar** — green <70%, orange >70%, red ≥100%.
  - **Forecast chip** — "On track" / "At risk" / "Will exceed" based on your daily spend rate.
  - **Effective limit** — includes any rollover.
- When spending crosses the threshold or exceeds the limit, you'll get a **notification**.

### 4.6 Savings Goals
Save towards a target.

- **New goal** → name, target amount, deadline, colour, and optionally **link an account**.
  - If linked to an account, contributions create real transactions (money moves out of the
    account and into the goal).
- **Contribute / Withdraw** on each goal card.
- **History** (clock icon) shows the contribution/withdrawal audit trail.
- You'll get notifications at **25% / 50% / 75%** milestones and when the goal is reached.

### 4.7 Loans & Contacts
Track money you've **lent** or **borrowed**.

- **Contacts** → add the people/businesses you lend to or borrow from (person or business).
- **New loan** → choose **"I lent money"** (asset) or **"I borrowed money"** (liability), the
  contact, principal, interest rate, **late penalty rate**, start/due dates, payment frequency
  and amount, and (optionally) an account to move the principal.
- Each loan card shows remaining balance, progress, and an **overdue badge** (pulsing) with
  the days overdue.
- **Record payment** — reduces the remaining balance; if late, a penalty may be applied.
- **Statement** — download a **PDF statement** of the loan and its payments.
- Notifications remind you when a payment is **due in 3 days** and alert you when **overdue**.

### 4.8 Reports & Analytics
Understand your money with charts and breakdowns. Pick a date range then use the tabs:

- **Overview** — summary cards, monthly trend line chart, expense/income doughnuts, net worth.
- **Year vs Year** — monthly net, current year vs last year (grouped bar chart).
- **Top Spending** — your top 5 expense categories (horizontal bar + % of spend).
- **Cash Flow** — inflows vs outflows by category.
- **Export PDF** — download the report as a PDF.
- **Export CSV** — download the category breakdown.
- **Monthly report email** — toggle to receive your monthly summary by email (choose the day).

### 4.9 Recurring transactions
Any transaction you mark as recurring creates a template under **Recurring**.

- Each card shows the **next run**, **last run**, frequency, and **end condition**.
- **Pause / resume** with the power icon.
- **Run history** (clock icon) shows each time it ran (success/failed) with dates.
- If a recurring run fails, you get a **notification**.

### 4.10 Notifications
- The **bell icon** (top of the module) shows your unread count. Click it for a dropdown of the
  latest notifications (budget warnings, loan reminders, goal milestones, failed recurring runs,
  monthly reports).
- **View all** opens the full notifications page where you can **mark all read** or **delete**.
- Clicking a notification jumps to the relevant page.

---

## 5. Common Tasks & How-To's

| I want to… | Do this |
|------------|---------|
| Add a new account | Personal → Accounts → Add account |
| Record my salary | Transactions → Add → Income |
| Record an expense | Transactions → Add → Expense |
| Move money between accounts | Transactions → Add → Transfer → pick From & To |
| Set a monthly food budget | Budgets → New budget → category "Food & Dining" |
| Save for a trip | Savings Goals → New goal → Contribute |
| Lend money to a friend | Contacts → add friend → Loans → New loan → "I lent money" |
| Download my yearly report | Reports → set range → Export PDF |
| Import a bank CSV | Transactions → Import → follow the 4 steps |
| Pause an automated bill | Recurring → power icon on the bill |
| Assign a plan to a tenant | CORE → Subscriptions → Assign plan |
| Give a user access to Personal Accounting | CORE → Users → edit → assign a role with the `personal-accounting` permissions |

---

## 6. FAQ & Troubleshooting

**I don't see the Personal Accounting menu.**
The menu only appears if your role has the `personal-accounting.view` permission. Ask an admin to
assign you a role or a subscription that includes it.

**Why is my account balance wrong?**
Balances are updated automatically when you add/edit/delete a transaction. If something looks off,
check the account's transaction history (Accounts → click the account) and the "Duplicate warning"
when adding. Recurring and imported transactions also update balances.

**The "Export PDF" button opens an error or does nothing.**
Make sure you're running `npm run build` (production) or `npm run dev` (development) so the latest
frontend bundle is served, then hard-refresh your browser. The button downloads the PDF directly.

**Why am I getting a "possible duplicate" warning?**
You're adding a transaction with the same account, amount, and date as an existing one. If it's a
real duplicate, cancel; if it's intentional, choose "Add anyway".

**How do overdue loans / late payments work?**
When you record a payment after the scheduled date and the loan has a **late penalty rate**, a
penalty (percentage of the remaining balance) is applied and shown on the payment record.

**How do budget notifications work?**
When you create a new expense, the app checks your budgets. If a category reaches your **warning
threshold** you get a warning; if it exceeds the limit you get an "exceeded" notification.

**I imported a CSV but some rows were skipped.**
Rows without a usable date or amount are skipped. Re-map the columns in Step 2 and make sure the
amount column contains numbers.

---

*Hisabiya — Sales & Accounting SaaS · CORE + Personal Accounting modules*
