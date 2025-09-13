<?php

namespace App\Providers;

use App\Integrations\Providers\JsonProviderClient;
use App\Integrations\Providers\XmlProviderClient;
use Illuminate\Support\ServiceProvider;

class ProviderIntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(JsonProviderClient::class, fn () =>
        new JsonProviderClient((string)config('services.providers.json_url'))
        );

        $this->app->singleton(XmlProviderClient::class, fn () =>
        new XmlProviderClient((string)config('services.providers.xml_url'))
        );
    }
}
