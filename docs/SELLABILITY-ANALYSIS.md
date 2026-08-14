# Hisabiya — Sellability Analysis

**Status:** Comprehensive review of the codebase, architecture, feature set, and delivery-readiness. This is a discussion/assessment document, not a finished roadmap.

---

## 1. Executive verdict

**Hisabiya is architecturally sellable today, but not yet "launch-ready sellable."**

The foundation — multi-tenant SaaS core, subscription/checkout, a real accounting module, permissions, backups, billing — is genuinely production-grade in *structure*. What separates it from a sellable product is **not** the code quality (which is high) but a set of **operational and business layers** (pricing, payments in production, emails, security hardening, monitoring, docs, onboarding, multi-currency) that a paying customer expects before they hand over money.

Think of it as: **an excellent 10-story building with no fire escapes, no elevators, and no reception desk.** The floors are solid; the "running-a-business" parts are missing.

---

## 2. What is genuinely sellable (strengths)

| Area | Verdict | Notes |
|------|---------|-------|
| **Multi-tenant isolation** | ✅ Strong | `tenant_id` scoping throughout, global-scope restore fixed, permissions model solid. |
| **Modular monolith** | ✅ Strong | SOLID, constructor injection, custom `Modules\` autoload, each module independent. Clean to extend. |
| **Self-serve checkout + provisioning** | ✅ Good | Public pricing → checkout → payment → provisioning → login auto-routing. Registration at checkout. |
| **Module chooser + add-module** | ✅ New & working | Subscribers can buy additional modules and switch between them. |
| **Payment providers** | ⚠️ Dev-ready | SSLCommerz scaffolding + manual bKash/Bank + admin approval queue. **Not production-connected.** |
| **Accounting feature depth** | ✅ Strong | Transactions, transfers, budgets, savings, loans, recurring, reports, CSV import, PDFs. |
| **Backups & restore** | ✅ Good | Full + tenant backups, upload restore with strict isolation, ID remapping (collision-safe). |
| **Billing + invoices** | ✅ Good | Payment records, invoice PDF download. |
| **Test coverage** | ✅ Good | 129 tests, 468 assertions, feature-level (auth, checkout, billing, backup, module, accounting). |
| **Code conventions** | ✅ Strong | Clean, typed, consistent, well-documented services. |

This is a **real product skeleton**, not a toy. That's the hard part and it's done well.

---

## 3. What is NOT sellable yet (the gaps)

### A. Payment & money — the #1 blocker
- **Payment gateway: mostly done now.** ✅ SSLCommerz real API calls (sandbox + live), **IPN webhook with signature verification + automatic subscription activation** (`POST /checkout/ipn`, CSRF-exempt, idempotent, tested). Manual bKash/Bank + admin approval queue remains as the offline fallback.
- **Refunds:** ✅ built — admin-initiated refund via SSLCommerz refund API (real in prod, simulated in dev), marks payment `refunded`, revokes subscription access, logs activity. UI in the admin Subscriptions "Recent payments" panel.
- **Still to do:** obtain real SSLCommerz store credentials + sandbox-to-live certification; automatic **failed-payment / dunning** (a renewal invoice currently enters a grace window rather than auto-retrying a saved card).
- **No multi-currency / multi-company settings** beyond BDT default.
- **No tax handling** (VAT/income tax, invoice tax lines).
- **Recurring billing engine:** ✅ built — `RecurringBillingService` runs daily (`billing.renewals` + `billing.expirations` in the scheduler): term-end subscriptions get a renewal invoice, move to `past_due` with a 7-day grace window, owner is emailed, and a "Pay now" button appears in Billing; unpaid/expired subscriptions have access revoked. Idempotent + tested.
- **Still to do:** stored-card auto-charge (SSLCommerz doesn't offer easy stored-card recurring; this currently issues a payable renewal invoice instead) and mid-cycle proration on downgrade.

### B. Emails & notifications
- ✅ **Transactional email now configurable & deliverable** — a Mail Settings admin page (`/admin/settings/mail`) stores SMTP config in the DB, applied to Laravel's mail config at runtime so receipts, invoices, reset emails and alerts actually send. Includes a **"Send test email"** verification.
- ✅ All the notification classes (payment approved, renewals, expiry, budgets, loans, monthly reports) already have `toMail`, and now all **CORE notifications implement `ShouldQueue`** (database queue) so they dispatch reliably in the background.
- **Still to do:** run a **queue worker** in production (`php artisan queue:work`); set a real `MAIL_FROM`; wire provider (Mailgun/SES) for high throughput.

### C. Security hardening
- ✅ **Rate limiting** on login, register, forgot-password, checkout, and 2FA challenge (named limiters in `AppServiceProvider`).
- ✅ **2FA/MFA (TOTP)** built — RFC 6238, works with Google Authenticator/Authy/1Password. Setup with QR code, one-time recovery codes (hashed + consumed), login challenge flow, disable. No external package (self-contained).
- ✅ **Security headers** on every response via middleware: CSP, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, HSTS over HTTPS.
- ✅ **Audit trail** on payment/subscription events (refunds, expiry) via activity log.
- **Still to do:** field-level file scan/MIME hardening for uploaded proofs/CSV at scale; cookie `Secure` flag enforcement behind a proxy.

### D. Multi-tenancy & teams are incomplete
- **Team membership** exists (module owner adds users) but no **invitation-by-email flow** (it creates accounts silently), no role-based field-level security on module data, no per-user timezones/locales.
- No **data residency / tenant custom domain** (a premium feature for selling to businesses).

### E. Observability & ops
- No **scheduled jobs** wired into Laravel's scheduler (recurring transactions, subscription renewals, backup jobs, report emails) — they're implemented as classes but the `routes/console.php` scheduler wiring isn't shown to be live.
- No **queue worker** config in production, no monitoring (no Sentry/Logflare), no health endpoint.
- No **CI/CD** pipeline or deployment docs.

### F. Business & legal polish
- No **terms of service / privacy policy / refund policy** pages.
- No **onboarding tour** or sample-data seed for new signups (accounting is daunting to start from zero).
- No **changelog / what's-new**, no in-app help.
- No **licensing / branding** — buyer can't white-label it yet.

### G. Product-market completeness
- **Only ONE sellable module** (Personal Accounting). The chooser/browse machinery exists, but there's just one product — limits the "multi-module suite" pitch.
- Accounting module is **single-currency, personal-focused**; a business-grade variant (double-entry, chart of accounts, GST/BKBD report, multi-entity) would target a far more valuable market.

---

## 4. A realistic "minimum sellable product" (MVP) checklist

To take real money, in priority order:

**Tier 1 — Must have (you literally cannot sell without these):**
1. ✅ Already done: multitenancy, subscriptions, module chooser, add-module, backups, permissions, **recurring billing engine**, **refunds**, **SSLCommerz IPN + signature verification**.
2. **Connect one real payment gateway** (SSLCommerz) — the code + IPN is done; the remaining step is your sandbox/live credentials + certification.
3. **Working transactional email** — SMTP config + test-send UI done; remaining is real credentials + a mail provider (Mailgun/SES) and queueing behind a worker.
4. **A live scheduler + queue** — recurring billing + transaction jobs are wired in the scheduler; enable a queue worker for email/report jobs in production.
5. ✅ **Rate limiting + 2FA + security headers** — all built and tested.

**Tier 2 — Should have for credibility:**
6. ✅ **Terms / Privacy / Refund** pages — live at `/terms`, `/privacy`, `/refund`, linked in the public footer.
7. ✅ **Onboarding + sample data** — new tenants get a welcome banner on the PA dashboard with a one-click "Load sample data" (realistic transactions, budgets, a savings goal and a loan, fully tenant-scoped + idempotent).
8. ⏳ **Multi-currency settings** per tenant and a cleaner **billing center** (plan change/upgrade/downgrade/cancel self-serve) — billing center mostly exists; multi-currency not yet.
9. **A second module** (even a small one) to make the "suite" claim real and showcase the registry.

**Tier 3 — Competitive differentiators (later):**
10. Business accounting depth, team invitations + SSO, custom domains, white-label, API access, mobile/export-first workflows, audit-grade compliance (SOC2-lite posture).

---

## 5. Commercial framing (how to sell it)

- **Market:** Bangladesh / South Asia first (bKash, Nagad, BDT), where good localized small-business accounting is scarce.
- **Pricing model:** Free tier (1 module, limited) → Lite (single module, ~৳399) → Pro (~৳799) → suite bundles for multiple modules. Add per-seat pricing for teams.
- **Positioning:** "Your business's command center — accounting, [future modules] in one place, one invoice." The module-suite story is your real moat vs. standalone tools.
- **License & delivery:** It's a **SaaS** (you run it) *or* a **white-label / self-hosted license** (you sell it per-install). Both are viable; the codebase supports either. Self-hosted licensing has higher margin per deal but needs licensing controls.

---

## 6. Honest risks

- **One developer + a complex modular monolith** = maintenance burden; needs at least one more module and CI tests before scaling.
- **Payment provider dependency** — SSLCommerz API changes, sandbox→live certification required.
- **Competition:** Wave, Zoho Books, Tally (dominant in BD), local apps. Differentiate on localization + module-suite + simplicity.
- **Trust:** Accounting is sensitive; a data-loss bug (backups) is catastrophic to brand. The backup work done here mitigates this well — keep investing in it.

---

## 7. Bottom line

**Can it be sold? Yes — the architecture and one strong module are sellable-quality. Is it sellable today? Not yet.** The missing 20% is operational and business-completeness, not code: a live payment gateway, real email, scheduled jobs running, security hardening, legal pages, and onboarding. That 20% is what converts "a great demo" into "people pay for this."

**Estimated effort to reach minimum-sellable:** 2–4 focused weeks of the operational layers (payment + email + scheduler + security + legal/onboarding), assuming the current code stays as stable as it is.
