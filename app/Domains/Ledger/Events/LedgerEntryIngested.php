<?php

namespace App\Domains\Ledger\Events;

use Illuminate\Foundation\Events\Dispatchable;

// Consumed later by: anomaly detection (AI valgusfoor), dashboard projector, etc.
class LedgerEntryIngested
{
    use Dispatchable;

    public function __construct(public int $journalEntryId) {}
}
