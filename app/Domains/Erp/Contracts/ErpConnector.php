<?php

namespace App\Domains\Erp\Contracts;

use App\Domains\Erp\DTO\AccountData;
use App\Domains\Erp\DTO\JournalEntryData;
use App\Domains\Ledger\Models\SourceSystem;
use Carbon\CarbonInterface;

interface ErpConnector
{
    /** @return iterable<AccountData> */
    public function fetchChartOfAccounts(SourceSystem $source): iterable;

    /** @return iterable<JournalEntryData> Incremental: only entries modified since $since (null = full). */
    public function fetchJournalEntries(SourceSystem $source, ?CarbonInterface $since = null): iterable;
}
