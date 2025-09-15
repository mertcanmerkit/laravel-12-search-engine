<?php

namespace Tests\Feature\Services;

use App\Jobs\FetchProviderPage;
use App\Models\Provider;
use App\Models\SyncState;
use App\Services\ProviderSync\ProviderSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProviderSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeSut(): ProviderSyncService
    {
        return new ProviderSyncService();
    }

    #[Test]
    public function kickoff_dispatches_one_job_per_provider_on_sync_queue(): void
    {
        $p1 = Provider::factory()->create(['slug' => 'alpha']);
        $p2 = Provider::factory()->create(['slug' => 'beta']);

        Queue::fake();

        $this->makeSut()->kickoff(perPage: 25);

        Queue::assertPushedOn('sync', FetchProviderPage::class);
        Queue::assertPushed(FetchProviderPage::class, 2);

        Queue::assertPushed(FetchProviderPage::class, function (FetchProviderPage $job) use ($p1) {
            return $job->providerId === $p1->id
                && $job->providerSlug === $p1->slug
                && $job->page === 1
                && $job->perPage === 25;
        });

        Queue::assertPushed(FetchProviderPage::class, function (FetchProviderPage $job) use ($p2) {
            return $job->providerId === $p2->id
                && $job->providerSlug === $p2->slug
                && $job->page === 1
                && $job->perPage === 25;
        });

        $this->assertDatabaseHas('sync_states', [
            'provider_id' => $p1->id,
            'next_page'   => 1,
        ]);
        $this->assertDatabaseHas('sync_states', [
            'provider_id' => $p2->id,
            'next_page'   => 1,
        ]);
    }

    #[Test]
    public function kickoff_filters_by_provider_slug_when_specified(): void
    {
        Provider::factory()->create(['slug' => 'keep']);
        Provider::factory()->create(['slug' => 'skip']);

        Queue::fake();

        $this->makeSut()->kickoff(onlyProviderSlug: 'keep', perPage: 10);

        Queue::assertPushed(FetchProviderPage::class, 1);
        Queue::assertPushed(FetchProviderPage::class, function (FetchProviderPage $job) {
            return $job->providerSlug === 'keep'
                && $job->page === 1
                && $job->perPage === 10;
        });
    }

    #[Test]
    public function kickoff_uses_existing_sync_state_next_page(): void
    {
        $p = Provider::factory()->create(['slug' => 'resume']);
        SyncState::query()->create([
            'provider_id' => $p->id,
            'next_page' => 7,
        ]);

        Queue::fake();

        $this->makeSut()->kickoff(perPage: 15);

        Queue::assertPushed(FetchProviderPage::class, function (FetchProviderPage $job) use ($p) {
            return $job->providerId === $p->id
                && $job->page === 7
                && $job->perPage === 15;
        });
    }

    #[Test]
    public function kickoff_skips_when_cache_lock_not_acquired(): void
    {
        $p = Provider::factory()->create(['slug' => 'locked']);

        Cache::shouldReceive('lock')->once()->with("sync:provider:{$p->slug}", 30)
            ->andReturn(new class {
                public function get(): bool { return false; }
            });

        Queue::fake();

        $this->makeSut()->kickoff(perPage: 5);

        Queue::assertNothingPushed();
        $this->assertDatabaseMissing('sync_states', ['provider_id' => $p->id]);
    }
}
