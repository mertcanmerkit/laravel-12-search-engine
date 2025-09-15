<?php

namespace App\Services\ProviderSync;

use App\Jobs\FetchProviderPage;
use App\Models\Provider;
use App\Models\SyncState;
use Illuminate\Support\Facades\Cache;

final class ProviderSyncService
{
    /**
     * Start/resume sync by enqueuing the next page per provider.
     * @param string|null $onlyProviderSlug
     * @param int $perPage
     */
    public function kickoff(?string $onlyProviderSlug = null, int $perPage = 10): void
    {
        $providers = Provider::query()
            ->when($onlyProviderSlug, fn($q) => $q->where('slug', $onlyProviderSlug))
            ->get(['id','slug']);

        foreach ($providers as $p) {
            $lock = Cache::lock("sync:provider:{$p->slug}", 30);

            if (! $lock->get()) {
                continue;
            }

            $state = SyncState::firstOrCreate(
                ['provider_id' => $p->id],
                ['next_page' => 1]
            );

            $page = $state->next_page ?? 1;

            FetchProviderPage::dispatch(
                providerId: $p->id,
                providerSlug: $p->slug,
                page: $page,
                perPage: $perPage
            //)->onQueue("sync-{$p->slug}");
            )->onQueue("sync");
        }
    }
}
