<?php

namespace Tests\Unit\Application\Factories;

use App\Application\Factories\ContentDTOFactory;
use App\DTO\ArticleMetricsDTO;
use App\DTO\VideoMetricsDTO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ContentDTOFactoryTest extends TestCase
{
    #[Test]
    public function makes_video_dto(): void
    {
        $valid = [
            'provider'         => 'json_provider',
            'provider_item_id' => 'v1',
            'title'            => 'Video Title',
            'type'             => 'video',
            'published_at'     => now()->toIso8601String(),
            'tags'             => ['php','laravel'],
            'metrics'          => ['views' => 1500, 'likes' => 120, 'duration_seconds' => 3723],
        ];

        $dto = (new ContentDTOFactory())->make($valid);

        $this->assertSame('json_provider', $dto->provider);
        $this->assertSame('v1', $dto->providerItemId);
        $this->assertSame('video', $dto->type);
        $this->assertSame(['php','laravel'], $dto->tags);
        $this->assertInstanceOf(VideoMetricsDTO::class, $dto->metrics);
        $this->assertSame(1500, $dto->metrics->views);
        $this->assertSame(120,  $dto->metrics->likes);
        $this->assertSame(3723, $dto->metrics->durationSeconds);
    }

    #[Test]
    public function makes_article_dto(): void
    {
        $valid = [
            'provider'         => 'xml_provider',
            'provider_item_id' => 'a1',
            'title'            => 'Article Title',
            'type'             => 'article',
            'published_at'     => now()->subDays(2)->toIso8601String(),
            'tags'             => ['backend'],
            'metrics'          => ['reading_time' => 8, 'reactions' => 25],
        ];

        $dto = (new ContentDTOFactory())->make($valid);

        $this->assertSame('xml_provider', $dto->provider);
        $this->assertSame('a1', $dto->providerItemId);
        $this->assertSame('article', $dto->type);
        $this->assertSame(['backend'], $dto->tags);
        $this->assertInstanceOf(ArticleMetricsDTO::class, $dto->metrics);
        $this->assertSame(8,  $dto->metrics->readingTimeMinutes);
        $this->assertSame(25, $dto->metrics->reactions);
    }
}
