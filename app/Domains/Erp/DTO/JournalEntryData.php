<?php

namespace App\Domains\Erp\DTO;

use Carbon\CarbonInterface;

final readonly class JournalEntryData
{
    /** @param list<JournalLineData> $lines */
    public function __construct(
        public string $ref,
        public CarbonInterface $date,
        public string $docType,
        public array $lines,
        public ?string $documentNumber = null,
        public string $currency = 'EUR',
    ) {}
}
