<?php

namespace App\Ai\Agents;

use App\Ai\Tools\AccountMovement;
use App\Ai\Tools\AvailablePeriods;
use App\Ai\Tools\Cashflow;
use App\Ai\Tools\ListAccounts;
use App\Ai\Tools\ProfitAndLoss;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::Anthropic)]
#[Model('claude-haiku-4-5-20251001')] // safe documented default; bump to a Sonnet/Opus model for stronger tool reasoning
#[MaxSteps(8)]                // room for: available_periods -> list_accounts -> aggregate
class FinanceAnalyst implements Agent, HasTools
{
    use Promptable;

    public function __construct(public int $tenantId) {}

    public function instructions(): string
    {
        return <<<TXT
        You are a finance analyst over an accounting client's general ledger.
        Rules:
        - NEVER invent figures. Every number must come from a tool result.
        - All amounts are in EUR.
        - Periods are 'YYYY-MM'. The ledger holds 2026 data. Map month names
          ("март", "March") to the matching YYYY-MM; if the year is unclear, call
          available_periods first.
        - Resolve account names to codes via list_accounts when needed.
        - Answer in the same language as the question, concisely.
        - If a tool returns no data, say so plainly — do not guess.
        TXT;
    }

    /** @return \Laravel\Ai\Contracts\Tool[] */
    public function tools(): iterable
    {
        return [
            new AvailablePeriods($this->tenantId),
            new ListAccounts($this->tenantId),
            new AccountMovement($this->tenantId),
            new Cashflow($this->tenantId),
            new ProfitAndLoss($this->tenantId),
        ];
    }
}
