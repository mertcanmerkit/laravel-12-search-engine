<?php

namespace Tests\Unit\Services\ProviderSync;

use App\Application\Factories\ContentDTOFactory;
use App\Application\Validation\ContentPayloadValidator;
use App\Models\Content;
use App\Models\Provider;
use App\Repositories\ContentRepository;
use App\Services\ProviderSync\PageProcessor;
use App\Services\ScoringService;
use App\Services\TagSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageProcessorTest extends TestCase
{
    use RefreshDatabase;

    private function makeSut(): PageProcessor
    {
        return new PageProcessor(
            new ContentPayloadValidator(),
            new ContentDTOFactory(),
            new ContentRepository(),
            new ScoringService(),
            new TagSyncService()
        );
    }

    #[Test]
    public function process_happy_path_persists_contents_scores_and_tags(): void
    {
        $provider = Provider::factory()->create(['slug' => 'p1']);

        $raw1 = [
            'provider_id'      => $provider->id,
            'provider_item_id' => 'a1',
            'title'            => 'Item A',
            'type'             => 'video',
            'published_at'     => now()->subDay()->toIso8601String(),
            'metrics'          => ['views' => 1, 'likes' => 1, 'duration_seconds' => 60],
            'tags'             => ['PHP', 'laravel '],
        ];

        $raw2 = [
            'provider_id'      => $provider->id,
            'provider_item_id' => 'b2',
            'title'            => 'Item B',
            'type'             => 'article',
            'published_at'     => now()->toIso8601String(),
            'metrics'          => ['reading_time' => 5, 'reactions' => 2],
            'tags'             => ['Testing'],
        ];

        $sut = $this->makeSut();

        $sut->process($provider->id, [$raw1, $raw2]);

        $this->assertSame(2, Content::count());

        $a = Content::query()->where('provider_item_id', 'a1')->firstOrFail();
        $b = Content::query()->where('provider_item_id', 'b2')->firstOrFail();


        $this->assertIsNumeric((float)$a->final_score);
        $this->assertGreaterThan(0, (float)$a->final_score);
        $this->assertIsNumeric((float)$b->final_score);
        $this->assertGreaterThan(0, (float)$b->final_score);

        $this->assertEqualsCanonicalizing(['laravel', 'php'], $a->tags);
        $this->assertEqualsCanonicalizing(['testing'], $b->tags);
    }

    #[Test]
    public function process_skips_items_that_fail_validation_and_logs_warning(): void
    {
        $provider = Provider::factory()->create();

        $invalid = [
            'provider_id'      => $provider->id,
            'provider_item_id' => 'bad',
            // 'title' missing to trigger validation error
            'type'             => 'video',
            'published_at'     => now()->toIso8601String(),
            'metrics'          => ['views' => 5, 'likes' => 1, 'duration_seconds' => 30],
            'tags'             => ['oops'],
        ];

        $valid = [
            'provider_id'      => $provider->id,
            'provider_item_id' => 'ok',
            'title'            => 'Good One',
            'type'             => 'video',
            'published_at'     => now()->toIso8601String(),
            'metrics'          => ['views' => 10, 'likes' => 2, 'duration_seconds' => 120],
            'tags'             => ['Testing'],
        ];

        Log::spy();

        $sut = $this->makeSut();

        $sut->process($provider->id, [$invalid, $valid]);

        $this->assertSame(1, Content::count());
        $this->assertNotNull(Content::where('provider_item_id', 'ok')->first());
        $this->assertNull(Content::where('provider_item_id', 'bad')->first());


        Log::shouldHaveReceived('warning')
            ->once()
            ->with('sync.validation_skip', Mockery::on(fn ($ctx) => isset($ctx['reason']) && is_string($ctx['reason'])));
    }

    #[Test]
    public function process_logs_error_and_rethrows_on_unexpected_exception(): void
    {
        $providerId = 9999;

        $raw = [
            'provider_id'      => $providerId,
            'provider_item_id' => 'x1',
            'title'            => 'Will Boom',
            'type'             => 'article',
            'published_at'     => now()->toIso8601String(),
            'metrics'          => ['reading_time' => 4, 'reactions' => 0],
            'tags'             => [],
        ];

        Log::spy();

        $sut = $this->makeSut();

        $this->expectException(\Throwable::class);

        try {
            $sut->process($providerId, [$raw]);
        } finally {
            Log::shouldHaveReceived('error')
                ->once()
                ->with('sync.page_error', Mockery::on(fn ($ctx) => isset($ctx['error']) && is_string($ctx['error'])));
        }
    }
}
