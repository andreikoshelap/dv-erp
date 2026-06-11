<?php

return [
    // While true, every connector resolves to its Fake variant (seeded data, no network).
    'fake' => env('ERP_FAKE', true),

    // Liquidity accounts used by the Cashflow tool (cash + bank source codes).
    'cash_accounts' => ['1010', '1210'],

    'smartaccounts' => [
        'base'    => env('SMARTACCOUNTS_BASE', 'https://sa.smartaccounts.eu/api'),
        'timeout' => env('SMARTACCOUNTS_TIMEOUT', 60),
    ],
];
