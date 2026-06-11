# gatto — Slice 2: natural-language layer over the ledger (Laravel AI SDK)

Ask finance questions in plain language; Claude answers using **read-only,
tenant-scoped tools** over the DWH from Slice 1 — it never sees raw SQL and
cannot invent numbers.

## Setup (on top of Slice 1)
```bash
composer require laravel/ai
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
php artisan migrate            # creates agent_conversations tables
# .env:
#   ANTHROPIC_API_KEY=sk-ant-...
```
Copy `app/Ai/`, `app/Domains/Ledger/Queries/`, `app/Console/Commands/AskFinance.php`,
and the updated `config/erp.php` into the project.

## Run
```bash
php artisan finance:ask "какой был денежный поток в марте 2026?"   # ~ 23130 EUR
php artisan finance:ask "какая была прибыль в марте?"              # revenue 22150, expenses 13350, profit 8800
php artisan finance:ask "сколько на банковском счёте пришло за февраль?"
php artisan finance:ask "What's the revenue trend across the quarter?"
```

## How it works
```
question ─▶ FinanceAnalyst (agent, Provider=Anthropic)
                 │ picks tool + params (model reasoning)
                 ▼
   AvailablePeriods / ListAccounts / AccountMovement / Cashflow / ProfitAndLoss
                 │ each scoped to tenant_id
                 ▼
   LedgerQuery  ── parameterized aggregates ──▶ PostgreSQL (Slice 1 DWH)
```

- `LedgerQuery` — every read the model is allowed to do, always `where tenant_id`.
- Tools are thin adapters: description + JSON schema + handle() → `LedgerQuery`.
- The model only chooses *which* tool and *what* params; figures come from the DB.

## Model
`FinanceAnalyst` pins `#[Provider(Lab::Anthropic)]` + a Haiku model (safe default).
Bump the `#[Model(...)]` string to a Sonnet/Opus model for stronger multi-step reasoning.
