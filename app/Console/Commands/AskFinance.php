<?php

namespace App\Console\Commands;

use App\Ai\Agents\FinanceAnalyst;
use App\Domains\Ledger\Models\Tenant;
use Illuminate\Console\Command;

class AskFinance extends Command
{
    protected $signature = 'finance:ask {question} {--tenant=}';
    protected $description = 'Ask a natural-language finance question against the ledger (LLM + tools)';

    public function handle(): int
    {
        $tenant = $this->option('tenant')
            ? Tenant::findOrFail($this->option('tenant'))
            : Tenant::firstOrFail();

        $this->info("Q: {$this->argument('question')}");
        $this->newLine();

        $answer = (new FinanceAnalyst($tenant->id))->prompt($this->argument('question'));

        $this->line((string) $answer);
        $this->newLine();

        return self::SUCCESS;
    }
}
