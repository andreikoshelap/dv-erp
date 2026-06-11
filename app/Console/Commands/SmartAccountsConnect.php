<?php

namespace App\Console\Commands;

use App\Domains\Ledger\Models\SourceSystem;
use App\Domains\Ledger\Models\Tenant;
use Illuminate\Console\Command;

class SmartAccountsConnect extends Command
{
    protected $signature = 'smartaccounts:connect
        {--tenant= : tenant id (defaults to the first tenant)}
        {--key= : SmartAccounts public API key}
        {--secret= : SmartAccounts API secret}
        {--label=SmartAccounts : label for the source}';

    protected $description = 'Store live SmartAccounts API credentials on a source (encrypted at rest)';

    public function handle(): int
    {
        $key    = $this->option('key')    ?: $this->secret('Public API key');
        $secret = $this->option('secret') ?: $this->secret('API secret');

        if (! $key || ! $secret) {
            $this->error('Both --key and --secret are required.');

            return self::FAILURE;
        }

        $tenant = $this->option('tenant')
            ? Tenant::findOrFail($this->option('tenant'))
            : Tenant::firstOrFail();

        $source = SourceSystem::updateOrCreate(
            ['tenant_id' => $tenant->id, 'type' => 'smartaccounts'],
            ['label' => $this->option('label'), 'config' => ['api_key' => $key, 'secret' => $secret]],
        );

        $this->info("Stored credentials on source #{$source->id} (tenant: {$tenant->name}).");
        $this->line('Next: set ERP_FAKE=false in .env, then `php artisan smartaccounts:probe`.');

        return self::SUCCESS;
    }
}
