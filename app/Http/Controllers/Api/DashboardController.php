<?php

namespace App\Http\Controllers\Api;

use App\Ai\Agents\FinanceAnalyst;
use App\Domains\Ledger\Models\Tenant;
use App\Domains\Ledger\Queries\LedgerQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        $tenant = Tenant::firstOrFail();

        return response()->json([
            'tenant'   => $tenant->name,
            'months'   => LedgerQuery::monthlySummary($tenant->id),
            'accounts' => LedgerQuery::accountTotals($tenant->id),
        ]);
    }

    public function ask(Request $request): JsonResponse
    {
        $data   = $request->validate(['question' => 'required|string|max:500']);
        $tenant = Tenant::firstOrFail();

        $answer = (new FinanceAnalyst($tenant->id))->prompt($data['question']);

        return response()->json(['answer' => (string) $answer]);
    }
}
