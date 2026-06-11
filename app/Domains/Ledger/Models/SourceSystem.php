<?php

namespace App\Domains\Ledger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SourceSystem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'config'         => 'encrypted:array', // per-source API creds at rest
            'last_synced_at' => 'datetime',
        ];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }
}
