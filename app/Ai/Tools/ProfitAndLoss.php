<?php

namespace App\Ai\Tools;

use App\Domains\Ledger\Queries\LedgerQuery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class ProfitAndLoss implements Tool
{
    public function __construct(private int $tenantId) {}

    public function description(): string
    {
        return 'Revenue, expenses and profit for one period. Input: period as "YYYY-MM".';
    }

    public function handle(Request $request): string
    {
        return json_encode(LedgerQuery::profitAndLoss($this->tenantId, (string) $request['period']));
    }

    public function schema(JsonSchema $schema): array
    {
        return ['period' => $schema->string()->required()];
    }
}
