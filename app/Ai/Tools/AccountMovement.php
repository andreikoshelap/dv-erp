<?php

namespace App\Ai\Tools;

use App\Domains\Ledger\Queries\LedgerQuery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class AccountMovement implements Tool
{
    public function __construct(private int $tenantId) {}

    public function description(): string
    {
        return 'Net debit/credit movement for one account in one period. '
             . 'Inputs: code (account code from list_accounts), period ("YYYY-MM").';
    }

    public function handle(Request $request): string
    {
        return json_encode(LedgerQuery::accountMovement(
            $this->tenantId, (string) $request['code'], (string) $request['period'],
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'code'   => $schema->string()->required(),
            'period' => $schema->string()->required(),
        ];
    }
}
