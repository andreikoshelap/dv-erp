<?php

namespace App\Domains\Erp\DTO;

use App\Domains\Erp\Enums\AccountType;

final readonly class AccountData
{
    public function __construct(
        public string $code,
        public AccountType $type,
        public string $nameEt,
        public ?string $nameEn = null,
    ) {}
}
