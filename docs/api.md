# InvoiceFlow REST API v1

Base URL: `https://your-domain.com/api/v1`

All authenticated endpoints require an `Authorization: Bearer <token>` header.
All requests and responses use `application/json`.
Monetary amounts are in **minor units** (cents / pence).

---

## Authentication

### Issue a token
```
POST /v1/auth/tokens
```
Rate-limited: 10 requests/min per IP.

**Body:**
```json
{
    "email": "you@example.com",
    "password": "secret",
    "device_name": "My App"
}
```
**Response 201:**
```json
{
    "token": "1|abc123…",
    "token_type": "Bearer"
}
```

### Revoke current token
```
DELETE /v1/auth/tokens/current
```
**Response 200:** `{ "message": "Token revoked." }`

---

## Clients

All routes require `Authorization: Bearer <token>`. Responses are automatically scoped to the authenticated user's tenant.

### List clients
```
GET /v1/clients?search=acme&page=1
```
Returns paginated collection. Supports `?search=` to filter by name, email, or company.

**Response 200:**
```json
{
    "data": [
        {
            "id": 42,
            "name": "Acme Corp",
            "company_name": "Acme Corporation",
            "email": "billing@acme.com",
            "phone": "+1 555-0100",
            "address": {
                "line1": "123 Main St",
                "line2": null,
                "city": "San Francisco",
                "state": "CA",
                "postal_code": "94105",
                "country": "US"
            },
            "currency": "USD",
            "notes": null,
            "created_at": "2026-01-15T10:00:00+00:00",
            "updated_at": "2026-01-15T10:00:00+00:00"
        }
    ],
    "links": { "first": "…", "last": "…", "prev": null, "next": null },
    "meta": { "current_page": 1, "per_page": 25, "total": 1 }
}
```

### Create client
```
POST /v1/clients
```
**Body:** `name` (required), `email` (required), `company_name`, `phone`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `country`, `currency` (ISO 4217, 3-char), `notes`

**Response 201:** Client object wrapped in `data`.

### Get client
```
GET /v1/clients/{id}
```
**Response 200:** Client object wrapped in `data`.

### Update client
```
PUT /v1/clients/{id}
```
All fields optional (PATCH semantics on PUT).

**Response 200:** Updated client object.

### Delete client
```
DELETE /v1/clients/{id}
```
Soft-deletes the client. **Response 204:** No content.

---

## Invoices

### List invoices
```
GET /v1/invoices?status=sent&client_id=42&page=1
```
Supports `?status=` (`draft`, `sent`, `paid`, `overdue`, `cancelled`) and `?client_id=`.

**Response 200:** Paginated collection of invoice objects (includes `client` and `items`).

**Invoice object:**
```json
{
    "id": "01hw…",
    "number": "INV-2026-0001",
    "status": "sent",
    "currency": "USD",
    "subtotal": 100000,
    "tax_rate": "10.00",
    "tax_amount": 10000,
    "total": 110000,
    "amount_paid": 0,
    "amount_due": 110000,
    "issue_date": "2026-01-01",
    "due_date": "2026-01-15",
    "sent_at": "2026-01-01T09:00:00+00:00",
    "paid_at": null,
    "notes": null,
    "client": { "…": "…" },
    "items": [
        {
            "id": 1,
            "description": "Design services",
            "quantity": "2.00",
            "unit_price": 50000,
            "amount": 100000,
            "position": 0
        }
    ],
    "payments": [],
    "created_at": "2026-01-01T09:00:00+00:00",
    "updated_at": "2026-01-01T09:00:00+00:00"
}
```

### Create invoice
```
POST /v1/invoices
```
> **Plan limit:** Free plan allows 3 invoices per calendar month. Returns `402` when the limit is exceeded.

**Body:**
```json
{
    "client_id": 42,
    "currency": "USD",
    "tax_rate": 10,
    "issue_date": "2026-06-01",
    "due_date": "2026-06-15",
    "notes": "Net 14",
    "items": [
        { "description": "Design work", "quantity": 2, "unit_price": 50000 }
    ]
}
```
`unit_price` is in minor units (e.g. 50000 = $500.00). Totals are computed server-side.

**Response 201:** Invoice object.

**Response 402 (plan limit):**
```json
{
    "message": "Free plan allows 3 invoices per month. Upgrade to Pro for unlimited invoices.",
    "error": "plan_limit_exceeded",
    "used": 3,
    "limit": 3
}
```

### Get invoice
```
GET /v1/invoices/{id}
```
`id` is the invoice's `public_id` (ULID). Includes `client`, `items`, and `payments`.

### Update invoice
```
PUT /v1/invoices/{id}
```
Updatable fields: `status`, `currency`, `tax_rate`, `issue_date`, `due_date`, `notes`.

### Delete invoice
```
DELETE /v1/invoices/{id}
```
Soft-deletes. **Response 204.**

---

## Payments

### List all payments
```
GET /v1/payments?page=1
```

### List payments for an invoice
```
GET /v1/invoices/{id}/payments
```

**Payment object:**
```json
{
    "id": 7,
    "invoice_id": 3,
    "amount": 110000,
    "currency": "USD",
    "provider": "manual",
    "provider_reference": null,
    "paid_at": "2026-01-10T12:00:00+00:00",
    "created_at": "2026-01-10T12:00:00+00:00"
}
```

### Record a manual payment
```
POST /v1/invoices/{id}/payments
```
Invoice must be in `sent` or `overdue` status.

**Body:**
```json
{
    "amount": 110000,
    "currency": "USD",
    "provider_reference": "bank-transfer-001",
    "paid_at": "2026-01-10"
}
```
`paid_at` and `provider_reference` are optional.

**Response 201:** Payment object.
**Response 422:** Invoice is not payable.

---

## Billing

### Get current plan
```
GET /v1/billing
```
**Response 200:**
```json
{
    "plan": "free",
    "monthly_invoice_limit": 3
}
```
`monthly_invoice_limit` is `null` on the Pro plan (unlimited).

### Open billing portal
```
POST /v1/billing/portal
```
Returns a Stripe Customer Portal URL for the tenant to manage their Pro subscription.

**Response 200:**
```json
{ "url": "https://billing.stripe.com/…" }
```
**Response 422:** No active billing account.

---

## Errors

All errors follow the same shape:

```json
{
    "message": "Human-readable description.",
    "errors": { "field": ["Validation message."] }
}
```

`errors` is only present on validation failures (422). Common HTTP status codes:

| Code | Meaning |
|------|---------|
| 400 | Bad request (e.g. invalid webhook signature) |
| 401 | Unauthenticated — missing or invalid token |
| 402 | Plan limit exceeded |
| 404 | Resource not found or belongs to another tenant |
| 422 | Validation error |
| 429 | Rate limit exceeded (120 req/min authenticated, 20/min unauthenticated) |
| 500 | Server error |

---

## Rate Limits

| Endpoint group | Limit |
|----------------|-------|
| `POST /v1/auth/tokens` | 10/min per IP |
| All other `/v1/*` (authenticated) | 120/min per user |
| All other `/v1/*` (unauthenticated) | 20/min per IP |

Rate-limit headers are returned on every response: `X-RateLimit-Limit`, `X-RateLimit-Remaining`.
