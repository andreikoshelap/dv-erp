<?php

namespace App\Console\Commands;

use App\Domains\Ledger\Jobs\SyncErpSource;
use App\Domains\Ledger\Models\SourceSystem;
use App\Domains\Ledger\Models\Tenant;
use Illuminate\Console\Command;

class SyncErp extends Command
{
    protected $signature = 'erp:sync {--source= : source_system id (auto-creates a demo one if omitted)}';
    protected $description = 'Pull a source ERP into the DWH (uses fake connector while ERP_FAKE=true)';

    public function handle(): int
    {
        $source = $this->option('source')
            ? SourceSystem::findOrFail($this->option('source'))
            : $this->demoSource();

        $this->info("Syncing source #{$source->id} ({$source->type})…");
        SyncErpSource::dispatchSync($source->id); // run inline so results are immediate

        $this->newLine();
        $this->table(
            ['accounts', 'entries', 'lines'],
            [[$source->accounts()->count(), $source->entries()->count(),
              $source->entries()->withCount('lines')->get()->sum('lines_count')]],
        );

        return self::SUCCESS;
    }

    private function demoSource(): SourceSystem
    {
        $tenant = Tenant::firstOrCreate(['name' => 'GROW demo client OÜ'], ['reg_code' => '99999999']);

        return SourceSystem::firstOrCreate(
            ['tenant_id' => $tenant->id, 'type' => 'smartaccounts'],
            ['label' => 'SmartAccounts (demo)', 'config' => []],
        );
    }
}
