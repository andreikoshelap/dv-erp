<?php

namespace App\Domains\Ledger\Jobs;

use App\Domains\Erp\ErpConnectorFactory;
use App\Domains\Ledger\Events\LedgerEntryIngested;
use App\Domains\Ledger\Models\Account;
use App\Domains\Ledger\Models\JournalEntry;
use App\Domains\Ledger\Models\SourceSystem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class SyncErpSource implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public int $sourceSystemId) {}

    public function handle(ErpConnectorFactory $factory): void
    {
        $source    = SourceSystem::findOrFail($this->sourceSystemId);
        $connector = $factory->for($source);

        // 1) Accounts first — entries reference them by code.
        foreach ($connector->fetchChartOfAccounts($source) as $acc) {
            Account::updateOrCreate(
                ['source_system_id' => $source->id, 'source_code' => $acc->code],
                ['tenant_id' => $source->tenant_id, 'name' => $acc->nameEt,
                 'name_en' => $acc->nameEn, 'type' => $acc->type],
            );
        }
        $accountIds = $source->accounts()->pluck('id', 'source_code'); // code => id

        // 2) Entries — incremental, idempotent per entry.
        foreach ($connector->fetchJournalEntries($source, $source->last_synced_at) as $dto) {
            DB::transaction(function () use ($source, $dto, $accountIds) {
                $entry = JournalEntry::updateOrCreate(
                    ['source_system_id' => $source->id, 'source_ref' => $dto->ref],
                    ['tenant_id'        => $source->tenant_id,
                     'entry_date'       => $dto->date,
                     'period'           => $dto->date->format('Y-m'),
                     'document_type'    => $dto->docType,
                     'document_number'  => $dto->documentNumber,
                     'currency'         => $dto->currency],
                );

                $entry->lines()->delete(); // replace on re-sync = clean idempotency
                $entry->lines()->createMany(
                    array_map(fn ($l) => [
                        'account_id' => $accountIds[$l->accountCode] ?? null,
                        'debit'      => $l->debit,
                        'credit'     => $l->credit,
                        'currency'   => $dto->currency,
                    ], $dto->lines),
                );

                LedgerEntryIngested::dispatch($entry->id);
            });
        }

        $source->update(['last_synced_at' => now()]);
    }
}
