<?php

namespace App\Integrations\Providers;

use App\DTO\ProviderPageDTO;
use App\Integrations\Providers\Contracts\ProviderClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter as Limiter;

final class JsonProviderClient implements ProviderClient
{
    public function __construct(private string $baseUrl) {}

    public function fetchPage(int $page, int $perPage): ProviderPageDTO
    {
        $bucket = 'provider:json:' . now()->format('YmdHi');
        if (Limiter::tooManyAttempts($bucket, (int)config('services.providers.rate_per_minute', 60))) {
            usleep(200_000);
        }
        Limiter::hit($bucket, 60);

        $res = Http::timeout(3)->retry(3, 200)->get($this->baseUrl)->throw()->json();

        $items = [];
        foreach ((array)($res['contents'] ?? []) as $it) {
            $items[] = [
                'provider'         => 'json_provider',
                'provider_item_id' => $it['id'] ?? null,
                'title'            => $it['title'] ?? null,
                'type'             => $it['type'] ?? null,
                'published_at'     => $it['published_at'] ?? null,
                'tags'             => $it['tags'] ?? [],
                'metrics'          => (array)($it['metrics'] ?? []),
            ];
        }

        $meta     = (array)($res['pagination'] ?? []);
        $cur      = (int)($meta['page'] ?? $page);
        $pp       = (int)($meta['per_page'] ?? $perPage);
        $total    = isset($meta['total']) ? (int)$meta['total'] : null;

        return new ProviderPageDTO($items, null, $cur, $pp, $total);
    }

    public function streamAll(int $startPage, int $perPage, int $maxPages = 10): \Generator
    {
        $dto = $this->fetchPage($startPage, $perPage);
        foreach ($dto->items as $raw) {
            yield $raw;
        }
    }
}
