<?php

use App\Domains\Ledger\Queries\LedgerQuery;

beforeEach(function () {
    $this->tenant = seedLedger()->tenant_id;
});

it('lists available periods in order', function () {
    expect(LedgerQuery::availablePeriods($this->tenant))
        ->toBe(['2026-01', '2026-02', '2026-03']);
});

it('lists all eight accounts', function () {
    expect(LedgerQuery::listAccounts($this->tenant))->toHaveCount(8);
});

it('computes March cashflow as 23130', function () {
    expect(LedgerQuery::cashflow($this->tenant, '2026-03')['net_cashflow'])->toBe(23130.0);
});

it('computes March profit and loss', function () {
    $pnl = LedgerQuery::profitAndLoss($this->tenant, '2026-03');

    expect($pnl['revenue'])->toBe(22150.0)
        ->and($pnl['expenses'])->toBe(13350.0)
        ->and($pnl['profit'])->toBe(8800.0);
});

it('computes a single account movement', function () {
    $m = LedgerQuery::accountMovement($this->tenant, '1210', '2026-03');

    expect($m['net'])->toBe(23130.0)
        ->and($m['debit'])->toBe(26580.0)
        ->and($m['credit'])->toBe(3450.0);
});

it('reports an error for an unknown account', function () {
    expect(LedgerQuery::accountMovement($this->tenant, '9999', '2026-03'))
        ->toHaveKey('error');
});

it('builds the three-month summary', function () {
    $months = LedgerQuery::monthlySummary($this->tenant);

    expect($months)->toHaveCount(3);
    expect($months[2])->toMatchArray([
        'period'   => '2026-03',
        'revenue'  => 22150.0,
        'expenses' => 13350.0,
        'profit'   => 8800.0,
        'cashflow' => 23130.0,
    ]);
});

it('computes per-account totals (cash account excluded — no movement)', function () {
    $byCode = collect(LedgerQuery::accountTotals($this->tenant))->keyBy('code');

    expect($byCode)->toHaveCount(7) // 1010 Kassa has zero movement, so it is absent
        ->and($byCode['1210']['net'])->toBe(53710.0)
        ->and($byCode['4010']['net'])->toBe(-53050.0)
        ->and($byCode['2110']['net'])->toBe(-24200.0);
});
