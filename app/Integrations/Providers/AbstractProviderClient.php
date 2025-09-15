<?php

namespace App\Integrations\Providers;

use App\Integrations\Providers\Contracts\ProviderClient;
use Illuminate\Support\Facades\RateLimiter as Limiter;

/**
 * Base provider client with shared throttling and pagination streaming.
 */
abstract class AbstractProviderClient implements ProviderClient
{
    public function __construct(
        protected string $baseUrl,
        protected string $slug,
        protected int $ratePerMinute,
    ) {}

    /** Child classes must implement page fetching and mapping. */
    abstract public function fetchPage(int $page, int $perPage): \App\DTO\ProviderPageDTO;

    /** Apply simple per-minute throttling per provider slug. */
    protected function throttle(): void
    {
        $bucket = "provider:{$this->slug}:" . now()->format('YmdHi');
        if (Limiter::tooManyAttempts($bucket, $this->ratePerMinute)) {
            // small backoff to smooth spikes
            usleep(200_000);
        }
        // decay after 60 seconds
        Limiter::hit($bucket, 60);
    }

    /**
     * Stream items across pages using the nextPage hint from ProviderPageDTO.
     */
    public function streamAll(int $startPage, int $perPage, int $maxPages = 10): \Generator
    {
        $page = $startPage;
        for ($i = 0; $i < $maxPages; $i++) {
            $dto = $this->fetchPage($page, $perPage);
            foreach ($dto->items as $raw) {
                yield $raw;
            }
            if ($dto->nextPage === null || $dto->nextPage === $page) {
                break;
            }
            $page = $dto->nextPage;
        }
    }
}

