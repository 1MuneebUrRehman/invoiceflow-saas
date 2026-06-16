# InvoiceFlow — Multi-Tenant Invoicing SaaS

> A production-style invoicing platform for freelancers and small agencies: create branded
> invoices, get paid online via Stripe, and let automated reminders chase late payments.

<!-- ![Dashboard](../public/screenshots/dashboard.png) -->
<!-- ![Public invoice payment page](../public/screenshots/public-invoice.png) -->

---

## At a Glance

| | |
|---|---|
| **Role** | Full-stack developer (solo) — architecture, backend, UI, tests |
| **Type** | Multi-tenant B2B SaaS |
| **Stack** | Laravel 13 · Livewire 4 · Tailwind CSS 4 · MySQL 8 · Stripe · Sanctum · Pest |
| **Status** | Feature-complete · feature-tested against real MySQL |
| **Links** | <!-- TODO: Live demo --> · <!-- TODO: Source repo --> |

---

## The Problem

Freelancers and small agencies lose real revenue to late payments and burn hours every
month creating invoices, chasing follow-ups, and reconciling who has actually paid.
**InvoiceFlow turns the whole "getting paid" workflow into one place** — clients,
invoices, payments, and reminders — and automates the chasing so it stops being a
part-time job.

---

## What I Built

- **Multi-tenant workspaces** — every team gets a fully isolated workspace with owner /
  member roles; one customer's data is never visible to another.
- **Client CRM** — searchable client records with contact details, billing address,
  preferred currency, and notes (soft-deleted, so nothing is lost by accident).
- **Invoice builder** — line items, tax, and discounts with automatic per-tenant
  sequential numbering (`INV-2026-0001`) and a `Draft → Sent → Paid / Overdue / Cancelled`
  lifecycle.
- **Branded PDF invoices** — generated in the background and attached to a polished email
  with the tenant's own logo.
- **Online payments** — clients pay by card through **Stripe Checkout** from a secure,
  signed, unguessable public link — no login required. Offline payments can be recorded
  manually too.
- **Automated late-payment reminders** — overdue invoices are detected on a schedule and
  send polite reminders at **+3, +7, and +14 days**, each exactly once.
- **Dashboard analytics** — outstanding balance, revenue this month, overdue count, a
  revenue chart, and recent activity at a glance.
- **REST API (v1)** — a token-authenticated API (clients, invoices, payments, billing)
  for integrating accounting tools, CRMs, or internal apps.
- **Free / Pro plans** — Free (3 invoices/month) vs Pro (unlimited), with upgrades and
  cancellations handled through the **Stripe Customer Portal** (no custom billing UI).
- **Account & security** — registration, email verification, password reset, rate-limited
  logins, and self-service API token management.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.5, Laravel 13 |
| UI | Livewire 4 + Tailwind CSS 4 (reactive UI, no separate JS frontend) |
| Database | MySQL 8 (used in dev **and** tests) |
| Payments | Stripe Checkout + Customer Portal (`stripe/stripe-php`) |
| Auth | Session (web) + Laravel Sanctum tokens (API) |
| Background work | Laravel Queues + Scheduler (PDFs, emails, reminders) |
| Testing | Pest (feature-test heavy, against real MySQL) |
| Quality | Laravel Pint + Larastan (PHPStan) |

---

## Engineering Highlights

The decisions I'd most want to talk through in a technical review:

- **Single-database multi-tenancy, proven by tests.** A `BelongsToTenant` trait applies a
  global query scope and auto-stamps `tenant_id` on create, reading the active tenant from
  a container-scoped `CurrentTenant` object set by middleware. Because a missed scope would
  be a data leak, cross-tenant isolation is asserted directly in the test suite.
- **Money as integer minor units — no floats, ever.** Every amount is stored as integer
  cents with an explicit `currency` column, so currency math is exact and rounding is
  visible in code review. Invoice totals are **stored snapshots**, not recomputed on read.
- **Transactional Action classes, thin controllers.** Business logic lives in
  single-purpose classes (`app/Actions/`), each owning a database-transaction boundary;
  controllers just validate (Form Requests), authorize (Policies), and delegate.
- **Idempotent, event-driven Stripe webhook.** The signature-verified webhook records a
  payment and fires an `InvoicePaid` event; queued listeners handle the side effects, so
  the handler stays fast and safe for Stripe to retry.
- **Unguessable public links.** Public invoices use **ULID** identifiers behind Laravel's
  `signed` middleware — tamper-proof URLs that never leak sequential IDs or business volume.
- **Everything heavy is queued.** PDF rendering, invoice emails, and reminder emails run on
  the queue and are idempotent, so requests stay fast and re-runs never double-charge or
  double-email.

---

## What It Demonstrates

Multi-tenant SaaS architecture, payment integration with webhooks, background job and
scheduler design, REST API design with token auth and rate limiting, a money-handling
domain modeled correctly, and a test suite focused on the things that must never break —
tenant isolation, money math, and webhook idempotency.

---

## Documentation & Links

- **Client overview** — [PROJECT_OVERVIEW_CLIENT.md](PROJECT_OVERVIEW_CLIENT.md)
- **Technical / interview overview** — [PROJECT_OVERVIEW_INTERVIEW.md](PROJECT_OVERVIEW_INTERVIEW.md)
- **Architecture decisions** — [decisions.md](decisions.md)
- **API reference** — [api.md](api.md)
- **README** — [../README.md](../README.md)
- **Live demo** — <!-- TODO -->
- **Source code** — <!-- TODO -->
