<?php

namespace App\Ai\Tools;

use App\Domains\Ledger\Queries\LedgerQuery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class AvailablePeriods implements Tool
{
    public function __construct(private int $tenantId) {}

    public function description(): string
    {
        return 'List the accounting periods (YYYY-MM) that have data. '
             . 'Call this first to resolve relative dates like "March".';
    }

    public function handle(Request $request): string
    {
        return json_encode(LedgerQuery::availablePeriods($this->tenantId));
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
