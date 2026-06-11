<?php

namespace App\Domains\Ledger\Models;

use App\Domains\Erp\Enums\AccountType;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['type' => AccountType::class];
    }
}
