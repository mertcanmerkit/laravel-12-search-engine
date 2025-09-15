<?php

namespace App\Integrations\Providers;

use App\DTO\ProviderPageDTO;
use App\Integrations\Providers\Contracts\ProviderClient;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

final class XmlProviderClient extends AbstractProviderClient implements ProviderClient
{
    public function __construct(
        string $baseUrl,
        string $slug = 'xml',
        int $ratePerMinute = 60,
    ) {
        parent::__construct($baseUrl, $slug, $ratePerMinute);
    }

    public function fetchPage(int $page, int $perPage): ProviderPageDTO
    {
        $this->throttle();

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
                'provider_item_id' => (string)($n->id ?? ''),
                'title'            => (string)($n->headline ?? ''),
                'type'             => $type,
                'published_at'     => (string)($n->publication_date ?? ''),
                'tags'             => $tags,
                'metrics'          => $metrics,
            ];
        }

        $meta  = $feed->meta ?? null;
        $cur   = $meta ? (int)$meta->current_page : $page;
        $pp    = $meta ? (int)$meta->items_per_page : $perPage;
        $total = $meta ? (int)$meta->total_count : null;

        $next = null;
        if ($total !== null && $pp > 0) {
            $totalPages = (int)ceil($total / $pp);
            $next = $cur < $totalPages ? $cur + 1 : null;
        }

        return new ProviderPageDTO($items, $next, $cur, $pp, $total);
    }
}
