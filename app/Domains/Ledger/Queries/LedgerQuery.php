<?php

namespace App\Domains\Ledger\Queries;

use App\Domains\Ledger\Models\Account;
use Illuminate\Support\Facades\DB;

/**
 * All ledger reads the AI tools are allowed to perform.
 * Pure aggregates, always scoped by tenant_id. No raw SQL reaches the model.
 */
class LedgerQuery
{
    /** @return list<string> e.g. ['2026-01','2026-02','2026-03'] */
    public static function availablePeriods(int $tenantId): array
    {
        return DB::table('journal_entries')
            ->where('tenant_id', $tenantId)
            ->distinct()->orderBy('period')
            ->pluck('period')->all();
    }

    /** @return list<array{code:string,name:string,type:string}> */
    public static function listAccounts(int $tenantId): array
    {
        return Account::where('tenant_id', $tenantId)
            ->orderBy('source_code')
            ->get(['source_code', 'name', 'name_en', 'type'])
            ->map(fn ($a) => [
                'code' => $a->source_code,
                'name' => $a->name,
                'name_en' => $a->name_en,
                'type' => $a->type->value,
            ])->all();
    }

    public static function accountMovement(int $tenantId, string $code, string $period): array
    {
        $account = Account::where('tenant_id', $tenantId)
            ->where('source_code', $code)->first();

        if (! $account) {
            return ['error' => "Account {$code} not found"];
        }

        $row = self::lineQuery($tenantId, $period)
            ->where('jl.account_id', $account->id)
            ->selectRaw('COALESCE(SUM(jl.debit),0) d, COALESCE(SUM(jl.credit),0) c')
            ->first();

        return [
            'code'   => $code,
            'name'   => $account->name,
            'period' => $period,
            'debit'  => (float) $row->d,
            'credit' => (float) $row->c,
            'net'    => (float) $row->d - (float) $row->c, // +inflow on asset accounts
        ];
    }

    public static function cashflow(int $tenantId, string $period): array
    {
        $codes = config('erp.cash_accounts', ['1010', '1210']);
        $ids   = Account::where('tenant_id', $tenantId)
            ->whereIn('source_code', $codes)->pluck('id');

        $net = (float) self::lineQuery($tenantId, $period)
            ->whereIn('jl.account_id', $ids)
            ->selectRaw('COALESCE(SUM(jl.debit),0) - COALESCE(SUM(jl.credit),0) as net')
            ->value('net');

        return ['period' => $period, 'currency' => 'EUR', 'net_cashflow' => $net,
                'liquidity_accounts' => $codes];
    }

    public static function profitAndLoss(int $tenantId, string $period): array
    {
        $income = (float) self::lineQuery($tenantId, $period)
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('a.type', 'income')
            ->selectRaw('COALESCE(SUM(jl.credit),0) - COALESCE(SUM(jl.debit),0) v')
            ->value('v');

        $expense = (float) self::lineQuery($tenantId, $period)
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('a.type', 'expense')
            ->selectRaw('COALESCE(SUM(jl.debit),0) - COALESCE(SUM(jl.credit),0) v')
            ->value('v');

        return ['period' => $period, 'currency' => 'EUR',
                'revenue' => $income, 'expenses' => $expense, 'profit' => $income - $expense];
    }

    private static function lineQuery(int $tenantId, string $period)
    {
        return DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('je.tenant_id', $tenantId)
            ->where('je.period', $period);
    }
}
