<?php

namespace App\Services;

use App\Models\Content;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

final class ContentSearchService
{
    private const ALLOWED_SORT   = ['final_score','published_at','title','type'];
    private const DEFAULT_SORT   = 'final_score';
    private const DEFAULT_ORDER  = 'desc';
    private const DEFAULT_LIMIT  = 2;
    private const MAX_PER_PAGE   = 50;
    private const CACHE_TAGS     = ['contents'];
    private const CACHE_TTL      = 120;

    public function search(array $p): LengthAwarePaginator
    {
        $s = $this->normalize($p);
        $this->guardRateLimit($s);
        $cacheKey = $this->makeCacheKey($s);

        return Cache::tags(self::CACHE_TAGS)->remember(
            $cacheKey,
            self::CACHE_TTL,
            fn () => $this->execute($s)
        );
    }

    private function normalize(array $p): array
    {
        $q       = (string)($p['q'] ?? '');
        $type    = $p['type'] ?? null;

        $sortRaw = $p['sort'] ?? self::DEFAULT_SORT;
        $sort    = in_array($sortRaw, self::ALLOWED_SORT, true) ? $sortRaw : self::DEFAULT_SORT;

        $orderRaw = strtolower($p['order'] ?? self::DEFAULT_ORDER);
        $order    = in_array($orderRaw, ['asc','desc'], true) ? $orderRaw : self::DEFAULT_ORDER;

        $page    = max(1, (int)($p['page'] ?? 1));
        $perPage = (int)($p['per_page'] ?? self::DEFAULT_LIMIT);
        $perPage = max(1, min($perPage, self::MAX_PER_PAGE));

        return compact('q','type','sort','order','page','perPage');
    }

    private function guardRateLimit(array $s): void
    {
        $rate       = (int) config('services.search.rate_per_minute', 60);
        $limiterKey = $this->makeLimiterKey($s);

        if (RateLimiter::tooManyAttempts($limiterKey, $rate)) {
            abort(429, 'Too many search requests. Please slow down.');
        }

        RateLimiter::hit($limiterKey, 60);
    }

    private function makeLimiterKey(array $s): string
    {
        $user = auth()->id() ?? 'guest';
        $ip   = request()->ip() ?? '0.0.0.0';
        $sig  = substr(md5($s['q'].'|'.$s['type'].'|'.$s['sort'].'|'.$s['order']), 0, 10);

        return "contents-search:{$user}:{$ip}:{$sig}";
    }

    private function makeCacheKey(array $s): string
    {
        $payload = [
            'q'       => $s['q'],
            'type'    => $s['type'],
            'sort'    => $s['sort'],
            'order'   => $s['order'],
            'page'    => $s['page'],
            'perPage' => $s['perPage'],
        ];

        return 'contents:index:' . md5(json_encode($payload));
    }

    // Execute the actual search and pagination
    private function execute(array $s): LengthAwarePaginator
    {
        $builder = $this->buildQuery($s['q'], $s['type'], $s['sort'], $s['order']);

        return $builder->paginate($s['perPage'],'page', $s['page']);
    }

    private function buildQuery(string $q, $type, string $sort, string $order)
    {
        return Content::search($q)
            ->when($type, fn ($b) => $b->where('type', $type))
            ->orderBy($sort, $order);
    }
}
