<?php

namespace App\Ai\Tools;

use App\Domains\Ledger\Queries\LedgerQuery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class ListAccounts implements Tool
{
    public function __construct(private int $tenantId) {}

    public function description(): string
    {
        return 'List the chart of accounts (code, name, type). '
             . 'Use to map an account name to its code before querying movements.';
    }

    public function handle(Request $request): string
    {
        return json_encode(LedgerQuery::listAccounts($this->tenantId));
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
