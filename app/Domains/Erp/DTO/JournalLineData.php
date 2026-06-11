<?php

namespace App\Domains\Erp\DTO;

final readonly class JournalLineData
{
    public function __construct(
        public string $accountCode,
        public float $debit = 0.0,
        public float $credit = 0.0,
        public ?string $description = null,
    ) {}
}
