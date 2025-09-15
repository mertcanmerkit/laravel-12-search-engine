<?php

namespace App\Integrations\Providers;

use App\DTO\ProviderPageDTO;
use App\Integrations\Providers\Contracts\ProviderClient;
use Illuminate\Support\Facades\Http;

final class JsonProviderClient extends AbstractProviderClient implements ProviderClient
{
    public function __construct(
        string $baseUrl,
        string $slug = 'json',
        int $ratePerMinute = 60,
    ) {
        parent::__construct($baseUrl, $slug, $ratePerMinute);
    }

    public function fetchPage(int $page, int $perPage): ProviderPageDTO
    {
        $this->throttle();

        $res = Http::timeout(3)->retry(3, 200)->get($this->baseUrl)->throw()->json();

        $items = [];
        foreach ((array)($res['contents'] ?? []) as $it) {
            $items[] = [
                'provider_item_id' => $it['id'] ?? null,
                'title'            => $it['title'] ?? null,
                'type'             => $it['type'] ?? null,
                'published_at'     => $it['published_at'] ?? null,
                'tags'             => $it['tags'] ?? [],
                'metrics'          => (array)($it['metrics'] ?? []),
            ];
        }

        $meta  = (array)($res['pagination'] ?? []);
        $cur   = (int)($meta['page'] ?? $page);
        $pp    = (int)($meta['per_page'] ?? $perPage);
        $total = isset($meta['total']) ? (int)$meta['total'] : null;

        $next = null;
        if ($total !== null && $pp > 0) {
            $totalPages = (int)ceil($total / $pp);
            $next = $cur < $totalPages ? $cur + 1 : null;
        }

        return new ProviderPageDTO($items, $next, $cur, $pp, $total);
    }
}
