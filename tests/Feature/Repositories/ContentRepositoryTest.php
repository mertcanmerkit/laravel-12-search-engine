<?php

namespace Tests\Feature\Repositories;

use App\DTO\ArticleMetricsDTO;
use App\DTO\ContentDTO;
use App\DTO\ScoreDTO;
use App\DTO\VideoMetricsDTO;
use App\Models\Content;
use App\Models\Provider;
use App\Repositories\ContentRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private int $providerId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->providerId = Provider::factory()->json()->create()->id;
    }

    #[Test]
    public function upsert_creates_and_updates_video(): void
    {
        $repo = new ContentRepository();

        $dto1 = new ContentDTO(
            providerId: $this->providerId,
            providerItemId: 'v1',
            title: 'Video Title',
            type: 'video',
            publishedAt: now(),
            tags: ['php','laravel'],
            metrics: new VideoMetricsDTO(views: 1000, likes: 50, durationSeconds: 120)
        );

        $created = $repo->upsertFromDTO($dto1);
        $this->assertInstanceOf(Content::class, $created);
        $this->assertNotEmpty($created->public_id);
        $this->assertSame('Video Title', $created->title);

        $dto2 = new ContentDTO(
            providerId: $this->providerId,
            providerItemId: 'v1', // same pair → update
            title: 'Video Title (Updated)',
            type: 'video',
            publishedAt: now(),
            tags: ['php'],
            metrics: new VideoMetricsDTO(views: 2000, likes: 80, durationSeconds: 130)
        );

        $updated = $repo->upsertFromDTO($dto2);
        $this->assertSame($created->id, $updated->id);
        $this->assertSame('Video Title (Updated)', $updated->title);
        $this->assertSame(['php'], $updated->tags);
        $this->assertSame(2000, $updated->metrics['views']);
    }

    #[Test]
    public function apply_scores_persists_numbers(): void
    {
        $repo = new ContentRepository();

        $dto = new ContentDTO(
            providerId: $this->providerId,
            providerItemId: 'a1',
            title: 'Article',
            type: 'article',
            publishedAt: now(),
            tags: [],
            metrics: new ArticleMetricsDTO(readingTimeMinutes: 7, reactions: 21)
        );

        $c = $repo->upsertFromDTO($dto);

        $score = new ScoreDTO(base: 8.0, typeMultiplier: 1.0, freshness: 3.0, engagement: 5.0, final: 16.0);
        $repo->applyScores($c, $score);

        $c->refresh();
        $this->assertEquals(8.0, (float)$c->base_score);
        $this->assertEquals(3.0, (float)$c->freshness_score);
        $this->assertEquals(5.0, (float)$c->engagement_score);
        $this->assertEquals(16.0, (float)$c->final_score);
    }

    #[Test]
    public function upsert_sets_content_hash_and_synced_at_on_create(): void
    {
        $repo = new ContentRepository();

        $dto = new ContentDTO(
            providerId: $this->providerId,
            providerItemId: 'x1',
            title: 'T',
            type: 'video',
            publishedAt: now(),
            tags: [],
            metrics: new VideoMetricsDTO(views: 1, likes: 1, durationSeconds: 1)
        );

        $c = $repo->upsertFromDTO($dto);
        $this->assertNotNull($c->synced_at);
        $this->assertNotNull($c->content_hash);
    }

    #[Test]
    public function unchanged_payload_keeps_hash_and_bumps_synced(): void
    {
        $repo = new ContentRepository();

        $dto = new ContentDTO(
            providerId: $this->providerId,
            providerItemId: 'v-sync-1',
            title: 'Same Title',
            type: 'video',
            publishedAt: now(),
            tags: ['php','laravel'],
            metrics: new VideoMetricsDTO(views: 100, likes: 10, durationSeconds: 60)
        );

        $created = $repo->upsertFromDTO($dto)->fresh();
        $originalHash      = $created->content_hash;
        $originalSyncedAt  = $created->synced_at;

        Carbon::setTestNow(now()->addMinute());
        $again = $repo->upsertFromDTO($dto)->fresh();

        $this->assertSame($created->id, $again->id);
        $this->assertSame($originalHash, $again->content_hash);

        $this->assertTrue($again->synced_at->gt($originalSyncedAt));

        Carbon::setTestNow();
    }

    #[Test]
    public function changed_payload_updates_fields_and_hash_and_bumps_synced(): void
    {
        $repo = new ContentRepository();

        $dto1 = new ContentDTO(
            providerId: $this->providerId,
            providerItemId: 'v-sync-2',
            title: 'Old',
            type: 'video',
            publishedAt: now(),
            tags: ['a'],
            metrics: new VideoMetricsDTO(views: 1, likes: 1, durationSeconds: 10)
        );

        $created = $repo->upsertFromDTO($dto1)->fresh();
        $oldHash       = $created->content_hash;
        $oldSyncedAt   = $created->synced_at;

        Carbon::setTestNow(now()->addSeconds(2));

        $dto2 = new ContentDTO(
            providerId: $this->providerId,
            providerItemId: 'v-sync-2',
            title: 'New', // changed
            type: 'video',
            publishedAt: $dto1->publishedAt,
            tags: ['a','b'], // changed
            metrics: new VideoMetricsDTO(views: 2, likes: 3, durationSeconds: 12) // changed
        );

        $updated = $repo->upsertFromDTO($dto2)->fresh();

        $this->assertSame($created->id, $updated->id);

        $this->assertSame('New', $updated->title);
        $this->assertSame(['a','b'], $updated->tags);
        $this->assertSame(2, $updated->metrics['views']);

        $this->assertNotSame($oldHash, $updated->content_hash);

        $this->assertTrue($updated->synced_at->gte($oldSyncedAt));

        Carbon::setTestNow();
    }

}
