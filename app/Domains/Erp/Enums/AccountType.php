<?php

namespace App\Domains\Erp\Enums;

// Matches SmartAccounts /api/settings/accounts "type" field (ASSET/LIABILITY/INCOME/EXPENSE).
enum AccountType: string
{
    case Asset     = 'asset';
    case Liability = 'liability';
    case Income    = 'income';
    case Expense   = 'expense';

    public static function fromSource(string $raw): self
    {
        return match (strtoupper($raw)) {
            'ASSET'     => self::Asset,
            'LIABILITY' => self::Liability,
            'INCOME'    => self::Income,
            'EXPENSE'   => self::Expense,
            default     => self::Asset,
        };
    }
}
