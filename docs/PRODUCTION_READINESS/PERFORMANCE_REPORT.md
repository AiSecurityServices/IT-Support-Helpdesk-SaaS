# Performance report — SupportFlow Cloud

Workload: 1 tenant · 300 tickets · 900 messages · 5 technicians · sqlite :memory:,
PHP 8.0/Windows, debug ON (worst case). Method: `tests/Feature/PerformanceTest.php`.

## Observed (2026-10-12)

| Check | Result | Target |
|---|---|---|
| Dashboard (aggregates + lists) | ~70 ms | < 3000 ms |
| Ticket list (15/page + filters) | ~36 ms | < 3000 ms |
| Ticket show query count | 26 queries | < 40 (N+1 guard) |

No `SELECT *` unbounded listings: every index paginates; reports stream via
cursor; CSV exports stream; attachments stream from disk; dashboard uses
aggregate queries; ticket pages eager-load relations.

## Production method (staging gate)

Seed representative volumes (10k tickets/tenant), run the same suite plus
`ab`/k6 against login, ticket list/create, search, reports; record p50/p95/p99
and slow-query log review. Indexes exist on all tenant FKs and hot columns
(status/priority/dates); add composite indexes only from measured slow queries.

No horizontal-scalability or tenant-capacity claims are made — single-node
Laravel + queued workers; scale via read replicas/workers when measured.
