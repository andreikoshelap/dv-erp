<?php

use App\Domains\Ledger\Jobs\SyncErpSource;
use App\Domains\Ledger\Models\Account;
use App\Domains\Ledger\Models\JournalEntry;
use App\Domains\Ledger\Models\JournalLine;

beforeEach(function () {
    $this->source = seedLedger();
});

it('ingests the expected number of rows', function () {
    expect(Account::count())->toBe(8)
        ->and(JournalEntry::count())->toBe(12)
        ->and(JournalLine::count())->toBe(27);
});

it('is idempotent — re-syncing does not duplicate', function () {
    SyncErpSource::dispatchSync($this->source->id);
    SyncErpSource::dispatchSync($this->source->id);

    expect(Account::count())->toBe(8)
        ->and(JournalEntry::count())->toBe(12)
        ->and(JournalLine::count())->toBe(27);
});

it('keeps the ledger balanced (sum debit == sum credit)', function () {
    expect((float) JournalLine::sum('debit'))
        ->toBe((float) JournalLine::sum('credit'))
        ->toBe(161470.0);
});
