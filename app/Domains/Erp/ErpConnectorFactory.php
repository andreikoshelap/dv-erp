<?php

namespace App\Domains\Erp;

use App\Domains\Erp\Connectors\FakeSmartAccountsConnector;
use App\Domains\Erp\Connectors\SmartAccountsConnector;
use App\Domains\Erp\Contracts\ErpConnector;
use App\Domains\Ledger\Models\SourceSystem;
use InvalidArgumentException;

class ErpConnectorFactory
{
    public function for(SourceSystem $source): ErpConnector
    {
        // Flip to false (or set ERP_FAKE=false) once real API keys are in $source->config.
        $fake = (bool) config('erp.fake', true);

        return match ($source->type) {
            'smartaccounts' => $fake
                ? app(FakeSmartAccountsConnector::class)
                : app(SmartAccountsConnector::class),
            // 'saf', 'erply', 'directo' => ...  // same pattern, added later
            default => throw new InvalidArgumentException("No connector for [{$source->type}]"),
        };
    }
}
