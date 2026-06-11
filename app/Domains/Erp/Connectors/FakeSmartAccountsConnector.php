<?php

namespace App\Domains\Erp\Connectors;

use App\Domains\Erp\Contracts\ErpConnector;
use App\Domains\Erp\DTO\AccountData;
use App\Domains\Erp\DTO\JournalEntryData;
use App\Domains\Erp\DTO\JournalLineData;
use App\Domains\Erp\Enums\AccountType;
use App\Domains\Ledger\Models\SourceSystem;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Same output shape as SmartAccountsConnector, but data is generated locally.
 * Lets the whole ETL pipeline run end-to-end before real API keys exist.
 * Deterministic, so re-running erp:sync is a true idempotency test.
 */
class FakeSmartAccountsConnector implements ErpConnector
{
    public function fetchChartOfAccounts(SourceSystem $source): iterable
    {
        yield new AccountData('1010', AccountType::Asset,     'Kassa',                 'Cash');
        yield new AccountData('1210', AccountType::Asset,     'Pangakonto',            'Bank account');
        yield new AccountData('1310', AccountType::Asset,     'Ostjate v'."\u{00F5}".'lgnevused', 'Accounts receivable');
        yield new AccountData('2110', AccountType::Liability, 'Hankijate v'."\u{00F5}".'lgnevused', 'Accounts payable');
        yield new AccountData('2310', AccountType::Liability, 'Käibemaks',             'VAT payable');
        yield new AccountData('4010', AccountType::Income,    'Müügitulu',             'Sales revenue');
        yield new AccountData('5010', AccountType::Expense,   'Kaubakulu',             'Cost of goods');
        yield new AccountData('5510', AccountType::Expense,   'Üldkulud',              'Overheads');
    }

    public function fetchJournalEntries(SourceSystem $source, ?CarbonInterface $since = null): iterable
    {
        $ref = 0;
        foreach (['2026-01', '2026-02', '2026-03'] as $i => $period) {
            $base   = Carbon::parse("$period-05");
            $sales  = [12500, 18400, 22150][$i];
            $cogs   = [6100, 8200, 9900][$i];
            $over   = [3200, 3300, 3450][$i];
            $vat    = round($sales * 0.20, 2);

            // Sales invoice: DR receivables, CR revenue + VAT
            yield new JournalEntryData((string) ++$ref, $base->copy()->addDays(2), 'CLIENT_INVOICE',
                documentNumber: "ARVE-$period",
                lines: [
                    new JournalLineData('1310', debit: $sales + $vat),
                    new JournalLineData('4010', credit: $sales),
                    new JournalLineData('2310', credit: $vat),
                ]);

            // Customer payment lands in bank: DR bank, CR receivables
            yield new JournalEntryData((string) ++$ref, $base->copy()->addDays(14), 'BANK_PAYMENT',
                documentNumber: "LAEK-$period",
                lines: [
                    new JournalLineData('1210', debit: $sales + $vat),
                    new JournalLineData('1310', credit: $sales + $vat),
                ]);

            // Purchase invoice (goods): DR cogs, CR payables
            yield new JournalEntryData((string) ++$ref, $base->copy()->addDays(6), 'VENDOR_INVOICE',
                documentNumber: "OST-$period",
                lines: [
                    new JournalLineData('5010', debit: $cogs),
                    new JournalLineData('2110', credit: $cogs),
                ]);

            // Overheads paid from bank: DR overheads, CR bank
            yield new JournalEntryData((string) ++$ref, $base->copy()->addDays(20), 'BANK_PAYMENT',
                documentNumber: "KULU-$period",
                lines: [
                    new JournalLineData('5510', debit: $over),
                    new JournalLineData('1210', credit: $over),
                ]);
        }
    }
}
