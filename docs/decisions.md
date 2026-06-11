# Architectural Decisions

A running log of significant decisions and their trade-offs. Newest entries at the bottom.

## 1. Single-database multi-tenancy with `tenant_id` scoping

**Date:** 2026-06-11 (Phase 1)

**Decision:** All tenant data lives in one MySQL database. Every tenant-owned table carries a `tenant_id` foreign key; the `BelongsToTenant` trait applies a global query scope and stamps `tenant_id` on create. The active tenant is held in a container-scoped `CurrentTenant` object, set by the `SetCurrentTenant` middleware from the authenticated user.

**Why:** At freelancer/small-agency scale, single-database is operationally simpler in every dimension: one migration run, trivial backups, cheap hosting, easy cross-tenant admin reporting and aggregate metrics.

**Trade-off:** Isolation is enforced at the application layer, not the infrastructure layer — a missed scope is a data leak, which is why tenant isolation has its own dedicated test suite (`tests/Feature/Tenancy/`). Noisy-neighbor performance is shared. The escape hatch: `tenant_id` on every table makes a later per-tenant export/migration mechanical.

**Rejected alternative:** Database-per-tenant (stancl/tenancy style) — stronger isolation, but multiplies operational cost (N migrations, N backups, connection juggling) for no benefit at this scale.

## 2. Tenant scope is a no-op without tenant context

**Date:** 2026-06-11 (Phase 1)

**Decision:** When no `CurrentTenant` is set (console commands, queue workers, seeders), queries run **unscoped** rather than returning nothing.

**Why:** Cross-tenant work is legitimate in those contexts — the Phase 3 overdue scanner iterates all tenants; seeders build multiple tenants. Silently-empty queries are the classic failure mode of the alternative ("the scheduled command processed zero invoices and nobody noticed").

**Trade-off:** A forgotten tenant context in a job means unscoped access instead of empty results. Mitigated by: jobs receive already-scoped models, HTTP always has the middleware-set context, and the behavior is pinned by an explicit test so it is a documented contract rather than an accident.

## 3. `User` is not tenant-scoped by the trait

**Date:** 2026-06-11 (Phase 1)

**Decision:** Only domain models (Client, Invoice, InvoiceItem, Payment) use `BelongsToTenant`. `User` has a `tenant_id` and `tenant()` relation but no global scope.

**Why:** Global scopes on the auth model interfere with session resolution, password resets, and platform-admin queries. User lists in the panel will be filtered explicitly instead. `users.tenant_id` is nullable to leave room for platform-admin users (separate admin panel) who belong to no tenant.

## 4. Money as integer minor units

**Date:** 2026-06-11 (Phase 1)

**Decision:** All monetary values (`subtotal`, `tax_amount`, `total`, `amount_paid`, `unit_price`, `amount`) are unsigned big integers in minor units (cents), each row carrying an explicit `currency` column. Tax *rate* is a decimal — it's a ratio, not money.

**Why:** Floats cannot represent decimal currency exactly; rounding errors compound across line items. Integer math is exact and `intdiv`/`round` decisions become visible in code review.

## 5. Invoice totals are stored, not computed on read

**Date:** 2026-06-11 (Phase 1)

**Decision:** `subtotal`, `tax_amount`, and `total` are persisted columns, recalculated only by the action that mutates a draft invoice.

**Why:** An invoice is a financial document. Once sent, its numbers are an immutable snapshot — they must not drift if a tax rule or rounding implementation changes later. Storage also makes dashboard aggregates a cheap `SUM()`.

## 6. Invoice-level tax rate (not per-line)

**Date:** 2026-06-11 (Phase 1)

**Decision:** One `tax_rate` per invoice, applied to the subtotal.

**Why:** Freelancers and small agencies almost always bill at a single rate. Per-line tax complicates the invoice form, the math, and the PDF for a case the target user rarely has. The schema migration to per-line later is straightforward (add `tax_rate` to `invoice_items`, backfill from the parent).

## 7. No repository pattern

**Date:** 2026-06-11 (Phase 1)

**Decision:** Eloquent models + single-purpose Action classes (`app/Actions/`), no repository layer.

**Why:** Eloquent *is* the persistence abstraction. A repository wrapping it would add indirection without enabling anything we need (we are not swapping ORMs, and tests use real models against a real database). Actions give business logic a home and a transaction boundary; that's the abstraction that earns its keep.

## 8. Public invoice identifiers are ULIDs, not primary keys

**Date:** 2026-06-11 (Phase 1)

**Decision:** Invoices carry a unique `public_id` ULID (generated via `HasUlids` with `uniqueIds()` overridden), used in public links. Auto-increment ids never leave the app.

**Why:** Sequential ids in public URLs leak business volume and invite enumeration. ULIDs are unguessable, sortable, and index-friendly. The signed-URL layer (Phase 2) adds tamper-proofing on top.

## 9. MySQL for development *and* tests

**Date:** 2026-06-11 (Phase 1)

**Decision:** Local development and the Pest suite both run on MySQL 8 (`invoiceflow_saas` / `invoiceflow_saas_testing` databases), matching the production engine.

**Why:** Testing on the production engine catches real behavior (strict mode, unique-constraint semantics, decimal handling) that SQLite would mask — money math and the `(tenant_id, number)` unique constraint are exactly the kind of thing engines disagree on.

**Trade-off:** Slower than in-memory SQLite and requires a running MySQL service. Locally, MySQL (and PHP-FPM, nginx, Redis, Mailpit) are provided by [Lerd](https://lerd.dev); artisan/composer/tests run inside the project container (`lerd console`, `lerd composer`, `lerd test`) where the `lerd-mysql` hostname resolves.

## 10. PHP 8.5

**Date:** 2026-06-11 (Phase 1)

**Decision:** The project requires PHP `^8.5` (bumped from the starter's `^8.3`).

**Why:** Greenfield project with no compatibility constraints — target the current release and its performance/language improvements from day one. The local Lerd site is pinned to PHP 8.5, so dev, CI, and the composer platform check all agree.
