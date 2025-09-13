<?php

namespace Tests\Feature\Repositories;

use App\DTO\ArticleMetricsDTO;
use App\DTO\ContentDTO;
use App\DTO\ScoreDTO;
use App\DTO\VideoMetricsDTO;
use App\Models\Content;
use App\Repositories\ContentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function upsert_creates_and_updates_video(): void
    {
        $repo = new ContentRepository();

        $dto1 = new ContentDTO(
            provider: 'json_provider',
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
            provider: 'json_provider',
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
            provider: 'xml_provider',
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
}
