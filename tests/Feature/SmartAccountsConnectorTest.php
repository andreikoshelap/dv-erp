<?php

use App\Domains\Erp\Connectors\SmartAccountsConnector;
use App\Domains\Erp\Enums\AccountType;
use App\Domains\Ledger\Models\SourceSystem;
use App\Domains\Ledger\Models\Tenant;
use Illuminate\Support\Facades\Http;

function makeSaSource(): SourceSystem
{
    $tenant = Tenant::create(['name' => 'SA test OÜ']);

    return SourceSystem::create([
        'tenant_id' => $tenant->id,
        'type'      => 'smartaccounts',
        'config'    => ['api_key' => 'pk_test', 'secret' => 'sk_test'],
    ]);
}

it('signs requests and maps the chart of accounts', function () {
    Http::fake(['*settings/accounts:get*' => Http::response([
        'accounts' => [
            ['code' => '1210', 'type' => 'ASSET',  'descriptionEt' => 'Pangakonto', 'descriptionEn' => 'Bank'],
            ['code' => '4010', 'type' => 'INCOME', 'descriptionEt' => 'Müügitulu',  'descriptionEn' => 'Sales'],
        ],
    ])]);

    $accounts = iterator_to_array(app(SmartAccountsConnector::class)->fetchChartOfAccounts(makeSaSource()));

    expect($accounts)->toHaveCount(2)
        ->and($accounts[0]->code)->toBe('1210')
        ->and($accounts[0]->type)->toBe(AccountType::Asset)
        ->and($accounts[1]->type)->toBe(AccountType::Income);

    Http::assertSent(fn ($r) =>
        str_contains($r->url(), 'apikey=pk_test')
        && str_contains($r->url(), 'signature=')
        && str_contains($r->url(), 'settings/accounts:get'));
});

it('maps journal entries and follows pagination', function () {
    $entry = fn (string $id, string $date) => [
        'id' => $id, 'date' => $date, 'docType' => 'BANK_PAYMENT', 'number' => "N{$id}", 'currency' => 'EUR',
        'rows' => [
            ['account' => '1210', 'debitAmount' => 100.0, 'creditAmount' => 0],
            ['account' => '4010', 'debitAmount' => 0, 'creditAmount' => 100.0],
        ],
    ];

    Http::fake(['*general/entries:get*' => Http::sequence()
        ->push(['entries' => [$entry('1', '15.03.2026')], 'hasMoreEntries' => true])
        ->push(['entries' => [$entry('2', '20.03.2026')], 'hasMoreEntries' => false])]);

    $entries = iterator_to_array(app(SmartAccountsConnector::class)->fetchJournalEntries(makeSaSource()));

    expect($entries)->toHaveCount(2) // proves it requested page 2 after hasMoreEntries=true
        ->and($entries[0]->ref)->toBe('1')
        ->and($entries[0]->date->format('Y-m-d'))->toBe('2026-03-15')
        ->and($entries[0]->lines[0]->accountCode)->toBe('1210')
        ->and($entries[0]->lines[0]->debit)->toBe(100.0);
});

it('falls back to a top-level array when there is no wrapper key', function () {
    Http::fake(['*settings/accounts:get*' => Http::response([
        ['code' => '1010', 'type' => 'ASSET', 'descriptionEt' => 'Kassa'],
    ])]);

    $accounts = iterator_to_array(app(SmartAccountsConnector::class)->fetchChartOfAccounts(makeSaSource()));

    expect($accounts)->toHaveCount(1)->and($accounts[0]->code)->toBe('1010');
});
