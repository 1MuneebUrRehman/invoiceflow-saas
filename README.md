# InvoiceFlow

> A multi-tenant invoicing SaaS for freelancers and small agencies — create branded invoices, get paid online via Stripe, and let automated reminders chase late payments for you.

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.5%2B-777BB4?logo=php&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-4-FB70A9)
![Tests](https://img.shields.io/badge/Tests-Pest-8BC34A)
![License](https://img.shields.io/badge/License-MIT-blue)

<!-- Screenshot: dashboard -->
<!-- Screenshot: public invoice payment page -->

## Documentation

| Audience | Document |
|---|---|
| Clients / prospects (plain language) | [docs/PROJECT_OVERVIEW_CLIENT.md](docs/PROJECT_OVERVIEW_CLIENT.md) |
| Technical / interview deep-dive | [docs/PROJECT_OVERVIEW_INTERVIEW.md](docs/PROJECT_OVERVIEW_INTERVIEW.md) |
| Portfolio case study | [docs/PORTFOLIO.md](docs/PORTFOLIO.md) |
| API reference | [docs/api.md](docs/api.md) |
| Architecture decisions | [docs/decisions.md](docs/decisions.md) |
| Design system | [DESIGN_SYSTEM.md](DESIGN_SYSTEM.md) |

## The Problem

Freelancers and small agencies lose real revenue to late payments and waste hours every month creating invoices, sending follow-ups, and reconciling who has paid. InvoiceFlow centralizes clients, invoices, payments, and reminders in one place — so getting paid stops being a part-time job.

## Features

- **Multi-tenant workspaces** — each team gets an isolated workspace with owner/member roles
- **Invoice builder** — line items, taxes, discounts, per-tenant sequential numbering (`INV-2026-0001`)
- **Branded PDF invoices** — generated in the background via queued jobs
- **Online payments** — clients pay via Stripe Checkout from a secure, signed public invoice link
- **Automated reminders** — overdue invoices trigger reminder emails at +3, +7, and +14 days (idempotent, queue-based)
- **Dashboard analytics** — outstanding balance, revenue this month, overdue count, revenue chart
- **REST API (v1)** — token-authenticated API for clients, invoices, and payments
- **Plan limits** — Free plan (3 invoices/month) and Pro plan (unlimited), managed via the Stripe Customer Portal

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.5, Laravel 13 |
| UI | Livewire 4 + Tailwind CSS 4 |
| Database | MySQL 8 |
| Payments | Stripe Checkout + Customer Portal (`stripe/stripe-php`) |
| Auth | Session (web) + Laravel Sanctum (API) |
| Background work | Laravel Queues + Scheduler |
| Testing | Pest |
| Code style | Laravel Pint |

## Architecture Highlights

A few deliberate decisions, with their trade-offs:

- **Single-database multi-tenancy.** Every tenant-owned model uses a `BelongsToTenant` trait that applies a global query scope and auto-assigns `tenant_id` on create. Isolation is enforced at the model layer and proven by dedicated Pest tests. *Trade-off:* simpler operations and one schema to migrate, at the cost of weaker isolation than database-per-tenant — an acceptable trade at this scale, with a documented migration path if needed.
- **Thin controllers, Action classes.** Business logic lives in single-purpose classes in `app/Actions/`, each wrapping state changes in a database transaction. Controllers validate (Form Requests), authorize (Policies), and delegate.
- **No repository layer.** Eloquent already provides the persistence abstraction this project needs; adding repositories here would be indirection without benefit. Documented so the omission is a choice, not an oversight.
- **Money as integer minor units.** All amounts are stored as integers (cents) with an explicit `currency` column. No floats, ever.
- **Events for side effects.** `InvoicePaid` fires from the Stripe webhook handler; queued listeners send receipts and update metrics, keeping the webhook fast and safe to retry.
- **Statuses as enums.** `InvoiceStatus` (Draft, Sent, Paid, Overdue, Cancelled) is a backed PHP enum cast on the model and mapped to consistent badge colors across the UI.

## Getting Started

### Requirements

- PHP 8.5+
- Composer
- MySQL 8
- Node.js 20+ (asset build)
- A Stripe account (test mode is fine)

### Installation

```bash
git clone https://github.com/<your-username>/invoiceflow.git
cd invoiceflow

composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate
```

Configure your database and Stripe keys in `.env` (see below), then:

```bash
php artisan migrate --seed   # seeds a demo tenant with sample invoices
php artisan storage:link     # required for tenant logo uploads
php artisan queue:work       # required for PDFs, emails, reminders
php artisan serve
```

Log in with the seeded demo account: `demo@invoiceflow.test` / `password`.

### Running the App

The easiest way to run everything for development (server + queue worker + logs + Vite) in one command:

```bash
composer run dev
```

Or run each piece yourself:

| Command | Purpose |
|---|---|
| `php artisan serve` | HTTP server at `http://localhost:8000` |
| `php artisan queue:work` | Queue worker — **required**; PDFs, invoice emails, and reminders are queued |
| `npm run dev` | Vite dev server (hot-reloads CSS/JS) — or `npm run build` for a one-off build |
| `php artisan pail` | Tail application logs |
| `php artisan schedule:work` | Run the scheduler locally (overdue marking at 00:05, reminders at 09:00) |

Scheduled jobs can also be triggered manually at any time:

```bash
php artisan invoices:mark-overdue     # mark sent invoices past their due date as overdue
php artisan invoices:send-reminders   # queue reminder emails at +3/+7/+14 days overdue
php artisan schedule:list             # see what is scheduled and when
```

### Local Development with Lerd

This repository's local environment uses [Lerd](https://lerd.dev) (PHP 8.5, MySQL, Redis, Mailpit in containers). If you use it, prefix every PHP-related command so it runs inside the project container:

```bash
lerd console <artisan-command>    # e.g. lerd console migrate --seed
lerd composer <composer-command>  # e.g. lerd composer test
lerd test                         # run the Pest suite
lerd db:create                    # create the app + testing databases
```

Mailpit is bundled with Lerd, so locally sent emails can be inspected in its web UI.

### Useful Verification Commands

```bash
composer test                          # full check: pint + phpstan + pest (same as CI)
php artisan test --compact             # Pest suite only
vendor/bin/pint --dirty                # auto-fix code style on changed files
vendor/bin/phpstan analyse --memory-limit=1G  # static analysis (level 7)
php artisan route:list --path=api      # confirm API routes
php artisan config:show services.stripe      # confirm Stripe keys are loaded
```

### Environment Configuration

| Variable | Purpose |
|---|---|
| `DB_*` | MySQL connection |
| `QUEUE_CONNECTION` | `database` locally, `redis` recommended in production |
| `MAIL_*` | Mail transport (use [Mailpit](https://github.com/axllent/mailpit) locally) |
| `STRIPE_PUBLISHABLE_KEY` | Stripe publishable key (`pk_test_…`) |
| `STRIPE_SECRET_KEY` | Stripe secret key (`sk_test_…`) |
| `STRIPE_WEBHOOK_SECRET` | Webhook signing secret (`whsec_…`) |

To receive Stripe webhooks locally, install the [Stripe CLI](https://docs.stripe.com/stripe-cli) and run:

```bash
stripe listen --forward-to localhost:8000/webhooks/stripe
```

Copy the `whsec_…` secret it prints into `STRIPE_WEBHOOK_SECRET` in `.env`. You can then pay invoices end-to-end with the test card `4242 4242 4242 4242`.

## API

All endpoints are versioned under `/api/v1` and authenticated with Sanctum bearer tokens. Create a token from **Settings → API Tokens** in the dashboard.

```bash
curl https://your-app.test/api/v1/invoices \
  -H "Authorization: Bearer <token>" \
  -H "Accept: application/json"
```

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/clients` | List clients (paginated) |
| POST | `/api/v1/clients` | Create a client |
| GET | `/api/v1/invoices` | List invoices (filter by status) |
| POST | `/api/v1/invoices` | Create an invoice with line items |
| POST | `/api/v1/invoices/{id}/send` | Email invoice to the client |
| GET | `/api/v1/payments` | List recorded payments |

Full endpoint reference: [docs/api.md](docs/api.md).

Responses follow a consistent JSON resource format; errors return RFC-style problem details with proper status codes. Rate limiting is applied per token.

## Testing

```bash
php artisan test          # full Pest suite
composer test             # pint + phpstan + pest, same as CI
```

The suite runs against a real MySQL database (`invoiceflow_saas_testing`) to match production engine behavior — see `docs/decisions.md` §9.

The suite prioritizes the parts that must never break: **tenant isolation**, **money calculations**, **Stripe webhook handling** (with fakes), and **reminder idempotency**.

## Deployment

Tested on a standard VPS / Laravel Forge setup:

1. PHP 8.5 + MySQL 8 + Redis, behind Nginx with HTTPS
2. `php artisan migrate --force` on deploy
3. `php artisan optimize` (config, route, view caching)
4. Run queue workers under Supervisor; `php artisan queue:restart` on every deploy
5. Add the scheduler cron: `* * * * * php artisan schedule:run`
6. Point a Stripe webhook at `/webhooks/stripe` and set `STRIPE_WEBHOOK_SECRET`

## Roadmap

- Recurring invoices and retainers
- Multi-currency invoices with exchange-rate snapshots
- Redis + Horizon for queue monitoring
- Client portal with payment history
- Docker-based local environment
- Optional database-per-tenant driver for enterprise isolation

## License

MIT — see [LICENSE](LICENSE).

---

Built by **Muneeb ur Rehman** — Laravel full-stack developer.
Portfolio <!-- TODO: add URL --> · LinkedIn <!-- TODO: add URL --> · Email <!-- TODO: add address -->
