<?php

use App\Domains\Ledger\Jobs\SyncErpSource;
use App\Domains\Ledger\Models\SourceSystem;
use App\Domains\Ledger\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class)->in('Feature');

/**
 * Create a tenant + SmartAccounts source and run the fake ETL once.
 * Returns the seeded source. Deterministic — safe to call per test.
 */
function seedLedger(): SourceSystem
{
    config(['erp.fake' => true]); // force the seeded connector regardless of .env

    $tenant = Tenant::create(['name' => 'Test client OÜ', 'reg_code' => '12345678']);
    $source = SourceSystem::create([
        'tenant_id' => $tenant->id,
        'type'      => 'smartaccounts',
        'config'    => [],
    ]);

    SyncErpSource::dispatchSync($source->id);

    return $source->refresh();
}
