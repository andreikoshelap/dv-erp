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
use Illuminate\Support\Facades\Http;

/**
 * Real SmartAccounts client. Drop-in replacement for the fake one:
 * just bind this in ErpServiceProvider when you have API keys in $source->config.
 *
 * Docs: https://sa.smartaccounts.eu/api  (HMAC-SHA256 signed, Estonian timezone,
 * 60 req/min, 1000 req/24h, dd.MM.yyyy dates, pagination via hasMoreEntries).
 */
class SmartAccountsConnector implements ErpConnector
{
    private const BASE = 'https://sa.smartaccounts.eu/en/api';

    public function fetchChartOfAccounts(SourceSystem $source): iterable
    {
        $json = $this->signedGet($source, '/settings/accounts:get');

        foreach ($json['accounts'] ?? $json['objects'] ?? [] as $a) {
            yield new AccountData(
                code:   (string) $a['code'],
                type:   AccountType::fromSource($a['type'] ?? 'ASSET'),
                nameEt: $a['descriptionEt'] ?? $a['code'],
                nameEn: $a['descriptionEn'] ?? null,
            );
        }
    }

    public function fetchJournalEntries(SourceSystem $source, ?CarbonInterface $since = null): iterable
    {
        $page = 1;
        do {
            $json = $this->signedGet($source, '/general/entries:get', [
                'dateType'   => 'modifydate',
                'dateFrom'   => ($since ?? Carbon::create(2000))->format('d.m.Y'),
                'fetchRows'  => 'true',
                'pageNumber' => $page,
            ]);

            foreach ($json['entries'] ?? $json['objects'] ?? [] as $e) {
                yield new JournalEntryData(
                    ref:            (string) $e['id'],
                    date:           Carbon::createFromFormat('d.m.Y', $e['date']),
                    docType:        $e['docType'] ?? 'LEDGER_ENTRY',
                    documentNumber: $e['number'] ?? null,
                    currency:       $e['currency'] ?? 'EUR',
                    lines: array_map(fn ($r) => new JournalLineData(
                        accountCode: (string) $r['account'],
                        debit:       (float) ($r['debitAmount'] ?? 0),
                        credit:      (float) ($r['creditAmount'] ?? 0),
                        description: $r['description'] ?? null,
                    ), $e['rows'] ?? []),
                );
            }
            $more = (bool) ($json['hasMoreEntries'] ?? false);
            $page++;
        } while ($more);
    }

    private function signedGet(SourceSystem $source, string $path, array $params = []): array
    {
        $params = array_merge($params, [
            'timestamp' => now('Europe/Tallinn')->format('dmYHis'),
            'apikey'    => $source->config['api_key'],
        ]);

        $query     = http_build_query($params);
        $signature = hash_hmac('sha256', $query, $source->config['secret']); // body empty on GET

        return Http::acceptJson()
            ->get(self::BASE . $path . '?' . $query . '&signature=' . $signature)
            ->throw()
            ->json();
    }
}
