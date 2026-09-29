# Aplikasi Sarpras — Agent Rules

## Source of truth

This repository is the source of truth for the Sarpras application.

Stack:

- Laravel 13
- PHP 8.4
- MySQL / MariaDB in production
- Redis for cache, session, queue and rate limiting
- private S3-compatible object storage
- JUARA as a read-only student directory

## Non-negotiable stock rules

Never mutate inventory quantities directly from controllers, views, seeders, or ad-hoc queries.

All stock mutations must go through `App\Services\StockService` and be executed inside a database transaction.

For every `unit_stocks` row:

```text
total_qty =
    available_qty
  + reserved_qty
  + borrowed_qty
  + damaged_qty
  + lost_qty
```

Rules:

1. Stock may never become negative.
2. Final mutations must re-check quantities while holding `SELECT ... FOR UPDATE` locks.
3. Mutations must produce `stock_movements` ledger entries.
4. Final actions must be idempotent.
5. Multi-row locks should use deterministic item/unit ordering to reduce deadlock risk.
6. Never trust quantities validated only in the browser.

## Distribution state

Creating a distribution allocates source stock:

```text
available -> reserved
```

The stock remains owned by the source unit until the signed handover document is uploaded and the distribution is completed.

Completion:

```text
source.reserved -> target.available
source.total    -> target.total
```

Cancellation returns the allocation:

```text
reserved -> available
```

Do not bypass this flow.

## Borrowing state

Public student requests do not reduce stock.

Approval:

```text
available -> reserved
```

Physical handover:

```text
reserved -> borrowed
```

Verified return:

```text
borrowed -> available | damaged | lost
```

The borrower-uploaded photo is evidence only. Stock changes only after authorized unit staff verify the physical return.

## Authorization

- `admin`: system administration plus all Sarpras access
- `sarpras`: central operational access
- `unit`: access is determined by `unit_memberships`

Never trust a unit ID from the request to authorize unit users. Resolve authorization using `UnitAccessService`.

Kaprodi/head accounts may manage only their assigned units unless they also have a higher global role.

## Public QR borrowing

Public routes are intentionally accountless but must remain constrained by:

- random unit public token;
- 6-digit unit PIN;
- session-bound PIN grant with version + expiry;
- rate limits;
- noindex/no-store headers;
- bounded prefix search;
- no endpoint that dumps the full student directory.

Never place the unit PIN in the QR code or URL.

## JUARA

JUARA is read-only from Sarpras.

Use the `juara` Laravel DB connection and the `sarpras_student_directory` view.

The application credential must have SELECT permission only on that view. Do not write to JUARA and do not broaden permissions to the whole database.

Current directory fields:

- student_id
- name
- nis
- class_name
- class_code

JUARA stores student NIS in `users.nip`.

## Files

Object storage is private. Do not expose raw S3/MinIO object URLs.

Serve protected files through authorization-aware application routes or short-lived signed URLs.

Signed handover documents and return evidence are sensitive records.

## Testing requirements

Before considering a change complete:

```bash
composer validate --strict
php artisan test
```

CI also validates migrations against MySQL 8.4.

Changes touching stock must include regression tests for:

- non-negative quantities;
- bucket invariants;
- idempotency when relevant;
- rollback on failure.

Do not report completion if CI is failing.
