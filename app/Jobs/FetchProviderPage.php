<?php

// app/Jobs/FetchProviderPage.php
namespace App\Jobs;

use App\Models\Provider;
use App\Models\SyncState;
use App\Services\ProviderSync\PageProcessor;
use App\Services\ProviderSync\ProviderClientFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class FetchProviderPage implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 5;
    public function backoff(): array { return [30, 60, 120, 300]; }

    public function __construct(
        public int $providerId,
        public string $providerSlug,
        public int $page,
        public int $perPage
    ) {}

    public function handle(ProviderClientFactory $clients, PageProcessor $processor): void
    {
        $provider = Provider::findOrFail($this->providerId);
        $client = $clients->forProvider($provider);

        $pageDto = $client->fetchPage($this->page, $this->perPage);

        $processor->process($this->providerId, $pageDto->items);

        $next = $pageDto->nextPage;

        SyncState::updateOrCreate(
            ['provider_id' => $this->providerId],
            ['next_page' => $next, 'last_synced_at' => now()]
        );

        if ($next !== null) {
            self::dispatch($this->providerId, $this->providerSlug, $next, $this->perPage)
                //->onQueue("sync-{$this->providerSlug}");
                ->onQueue("sync");
        }
    }
}

