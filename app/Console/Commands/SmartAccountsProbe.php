<?php

namespace App\Console\Commands;

use App\Domains\Erp\Connectors\SmartAccountsConnector;
use App\Domains\Ledger\Models\SourceSystem;
use Illuminate\Console\Command;
use Throwable;

class SmartAccountsProbe extends Command
{
    protected $signature = 'smartaccounts:probe {--source= : source id (defaults to first smartaccounts source)}';
    protected $description = 'Make one live SmartAccounts call to verify signing and reveal the response shape';

    public function handle(SmartAccountsConnector $connector): int
    {
        $source = $this->option('source')
            ? SourceSystem::findOrFail($this->option('source'))
            : SourceSystem::where('type', 'smartaccounts')->firstOrFail();

        if (empty($source->config['api_key'])) {
            $this->error('No api_key on this source. Run `smartaccounts:connect` first.');

            return self::FAILURE;
        }

        try {
            $this->info('Calling /settings/accounts:get …');
            $raw = $connector->signedGet($source, '/settings/accounts:get');

            $this->line('Raw response (top-level keys: '
                . implode(', ', array_keys($raw)) . '):');
            $this->line(substr(json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 0, 1200));

            $parsed = iterator_to_array($connector->fetchChartOfAccounts($source));
            $this->newLine();
            $this->info('Parsed ' . count($parsed) . ' accounts.');

            if (count($parsed) === 0) {
                $this->warn('Zero parsed — the wrapper key differs from accounts/objects/rows. '
                    . 'Tell me the top-level keys above and I will adjust unwrap().');
            } else {
                $this->line('Signing works. Now run `php artisan erp:sync` to ingest into the DWH.');
            }
        } catch (Throwable $e) {
            $this->error('Live call failed: ' . $e->getMessage());
            $this->line('401 → signature/keys; 503 → rate limit or unpaid billing; '
                . 'timeout → network. Check Settings → Connected services in SmartAccounts.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
