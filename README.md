# Slice 4 — Pest tests + CI

Deterministic tests over the data layer. No LLM is called — every figure is
computed by the DB, so the suite runs in milliseconds with no API keys.

## Files
```
tests/Pest.php                       # RefreshDatabase + seedLedger() helper
tests/Feature/LedgerIngestionTest.php # counts, idempotency, debit == credit
tests/Feature/LedgerQueryTest.php     # cashflow, P&L, movements, summary, totals
tests/Feature/DashboardApiTest.php    # GET /api/summary, POST /api/ask validation
.github/workflows/tests.yml           # CI: setup-php → composer → php artisan test
```

## One-time setup
Tests use SQLite in-memory. In `phpunit.xml`, make sure these env lines are
present and uncommented inside `<php>`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

(All migrations use portable column types, so SQLite is fine for tests even
though dev/prod run on PostgreSQL.)

## Run
```bash
php artisan test
# or just the data layer:
php artisan test --filter=LedgerQuery
```

## What's asserted
- ETL ingests exactly 8 accounts / 12 entries / 27 lines, and re-running is idempotent.
- The ledger balances: sum(debit) == sum(credit) == 161 470.
- March cashflow = 23 130, March profit = 8 800, account 1210 net = 53 710, etc.
- `/api/summary` returns the right shape and figures.
- `/api/ask` validates input (422 on empty) — without invoking the model.

## Why no LLM in tests
The agent is non-deterministic and costs an API call. The deterministic core
(`LedgerQuery` + ETL) carries the correctness guarantees; the agent only *selects*
which of these tested functions to call. So the valuable invariants are unit-tested,
and CI needs no secrets.
