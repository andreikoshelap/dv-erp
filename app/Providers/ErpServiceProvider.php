<?php

namespace App\Providers;

use App\Domains\Erp\Connectors\FakeSmartAccountsConnector;
use App\Domains\Erp\Connectors\SmartAccountsConnector;
use App\Domains\Erp\ErpConnectorFactory;
use Illuminate\Support\ServiceProvider;

class ErpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ErpConnectorFactory::class);
        $this->app->bind(FakeSmartAccountsConnector::class);
        $this->app->bind(SmartAccountsConnector::class);
    }
}
