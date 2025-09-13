<?php

namespace App\Integrations\Providers;

use App\DTO\ProviderPageDTO;
use App\Integrations\Providers\Contracts\ProviderClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter as Limiter;
use SimpleXMLElement;

final class XmlProviderClient implements ProviderClient
{
    public function __construct(private string $baseUrl) {}

    public function fetchPage(int $page, int $perPage): ProviderPageDTO
    {
        $bucket = 'provider:xml:' . now()->format('YmdHi');
        if (Limiter::tooManyAttempts($bucket, (int)config('services.providers.rate_per_minute', 60))) {
            usleep(200_000);
        }
        Limiter::hit($bucket, 60);

        $xml = Http::timeout(3)->retry(3, 200)->get($this->baseUrl)->throw()->body();

        $feed = new SimpleXMLElement($xml);
        $items = [];

        foreach ($feed->items->item as $n) {
            $type = (string)($n->type ?? '');
            $metrics = $type === 'video'
                ? [
                    'views'    => (int)($n->stats->views ?? 0),
                    'likes'    => (int)($n->stats->likes ?? 0),
                    'duration' => (string)($n->stats->duration ?? ''),
                ]
                : [
                    'reading_time' => (int)($n->stats->reading_time ?? 0),
                    'reactions'    => (int)($n->stats->reactions ?? 0),
                ];

            $tags = [];
            if (isset($n->categories->category)) {
                foreach ($n->categories->category as $c) {
                    $tags[] = (string)$c;
                }
            }

            $items[] = [
                'provider'         => 'xml_provider',
                'provider_item_id' => (string)($n->id ?? ''),
                'title'            => (string)($n->headline ?? ''),
                'type'             => $type,
                'published_at'     => (string)($n->publication_date ?? ''),
                'tags'             => $tags,
                'metrics'          => $metrics,
            ];
        }

        $meta     = $feed->meta ?? null;
        $cur      = $meta ? (int)$meta->current_page : $page;
        $pp       = $meta ? (int)$meta->items_per_page : $perPage;
        $total    = $meta ? (int)$meta->total_count : null;

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
