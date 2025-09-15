<?php

namespace App\Services\ProviderSync;

use App\Integrations\Providers\Contracts\ProviderClient;
use App\Integrations\Providers\JsonProviderClient;
use App\Integrations\Providers\XmlProviderClient;
use App\Models\Provider;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class ProviderClientFactory
{
    /** @var array<string,class-string<ProviderClient>> */
    private const MAP = [
        'json_provider' => JsonProviderClient::class,
        'xml_provider'  => XmlProviderClient::class,
    ];

    public function __construct(private Container $container) {}

    public function forProvider(Provider $provider): ProviderClient
    {
        $slug = strtolower(trim((string) $provider->slug));
        $base = (string) $provider->base_url;
        $rate = max(1, (int) $provider->rate_per_minute);

        if ($base === '' || ! filter_var($base, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Provider [{$slug}] has invalid base_url");
        }

        $class = self::MAP[$slug] ?? null;
        if (! $class) {
            throw new InvalidArgumentException("Unknown provider slug [{$slug}]");
        }

        return $this->container->make($class, [
            'baseUrl' => $base,
            'slug' => $slug,
            'ratePerMinute' => $rate,
        ]);
    }
}
