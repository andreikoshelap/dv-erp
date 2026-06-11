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
 * Live SmartAccounts client. Drop-in for the fake connector — set ERP_FAKE=false
 * and put {api_key, secret} into the source's config.
 *
 * Signing mirrors the reference WooCommerce client (smartman/woocommerce_smartaccounts):
 *   sig = HMAC-SHA256(urlParams [+ body], secret), hex.  GET signs URL params only.
 * Estonian timezone, timestamp ddMMyyyyHHmmss, 60 req/min & 1000 req/24h, dd.mm.yyyy dates.
 */
class SmartAccountsConnector implements ErpConnector
{
    public function fetchChartOfAccounts(SourceSystem $source): iterable
    {
        $json = $this->signedGet($source, '/settings/accounts:get');

        foreach ($this->unwrap($json, ['accounts', 'objects', 'rows']) as $a) {
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

            foreach ($this->unwrap($json, ['entries', 'objects', 'rows']) as $e) {
                yield new JournalEntryData(
                    ref: (string) $e['id'],
                    date: Carbon::createFromFormat('d.m.Y', $e['date']),
                    docType: $e['docType'] ?? 'LEDGER_ENTRY',
                    lines: array_map(fn ($r) => new JournalLineData(
                        accountCode: (string) $r['account'],
                        debit:       (float) ($r['debitAmount'] ?? 0),
                        credit:      (float) ($r['creditAmount'] ?? 0),
                        description: $r['description'] ?? null,
                    ), $e['rows'] ?? []),
                    documentNumber: $e['number'] ?? null,
                    currency: $e['currency'] ?? 'EUR',
                );
            }

            $more = (bool) ($json['hasMoreEntries'] ?? false);
            $page++;
        } while ($more);
    }

    /** Public so the probe command and tests can inspect a raw signed call. */
    public function signedGet(SourceSystem $source, string $path, array $params = []): array
    {
        $base = rtrim(config('erp.smartaccounts.base', 'https://sa.smartaccounts.eu/api'), '/');

        // apikey + timestamp first, then caller params — matches the reference client.
        $query = http_build_query(array_merge([
            'apikey'    => $source->config['api_key'] ?? '',
            'timestamp' => now('Europe/Tallinn')->format('dmYHis'),
        ], $params));

        $signature = hash_hmac('sha256', $query, $source->config['secret'] ?? ''); // GET: no body

        return Http::acceptJson()
            ->timeout((int) config('erp.smartaccounts.timeout', 60))
            ->get("{$base}{$path}?{$query}&signature={$signature}")
            ->throw()
            ->json();
    }

    /** Find the list payload regardless of which wrapper key the API uses. */
    private function unwrap(array $json, array $keys): array
    {
        foreach ($keys as $k) {
            if (isset($json[$k]) && is_array($json[$k])) {
                return $json[$k];
            }
        }

        return array_is_list($json) ? $json : []; // top-level array, or nothing recognizable
    }
}
