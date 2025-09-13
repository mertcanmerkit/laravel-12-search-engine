<?php

namespace Tests\Unit\Application\Validation;

use App\Application\Validation\ContentPayloadValidator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContentPayloadValidatorTest extends TestCase
{
    #[Test]
    public function validates_and_normalizes_video_payload(): void
    {
        $raw = [
            'provider'         => 'json_provider',
            'provider_item_id' => 'v1',
            'title'            => 'Video Title',
            'type'             => 'video',
            'published_at'     => now()->toIso8601String(),
            'tags'             => [' PHP ', 'laravel', 'php'],
            'metrics'          => ['views' => 1000, 'likes' => 120, 'duration' => '1:02:03'],
        ];

        $valid = (new ContentPayloadValidator())->validate($raw);

        $this->assertSame(['php','laravel'], $valid['tags']);
        $this->assertSame(1000, $valid['metrics']['views']);
        $this->assertSame(120,  $valid['metrics']['likes']);
        $this->assertSame(3723, $valid['metrics']['duration_seconds']);
        $this->assertArrayNotHasKey('duration', $valid['metrics']);
    }

    #[Test]
    public function validates_and_normalizes_article_payload(): void
    {
        $raw = [
            'provider'         => 'xml_provider',
            'provider_item_id' => 'a1',
            'title'            => 'Article Title',
            'type'             => 'article',
            'published_at'     => now()->subDays(3)->toIso8601String(),
            'categories'       => ['Dev', 'Backend'],
            'metrics'          => ['reading_time' => 8, 'reactions' => 25],
        ];

        $valid = (new ContentPayloadValidator())->validate($raw);

        $this->assertSame(['dev','backend'], $valid['tags']);
        $this->assertSame(8,  $valid['metrics']['reading_time']);
        $this->assertSame(25, $valid['metrics']['reactions']);
    }

    #[Test]
    public function throws_on_invalid_video_metrics(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $raw = [
            'provider'         => 'json_provider',
            'provider_item_id' => 'bad',
            'title'            => 'X',
            'type'             => 'video',
            'published_at'     => now()->toIso8601String(),
            'metrics'          => ['views' => -1, 'likes' => 0, 'duration' => '10:00'],
        ];

        (new ContentPayloadValidator())->validate($raw);
    }

    #[Test]
    public function limits_and_deduplicates_tags(): void
    {
        $raw = [
            'provider'         => 'json_provider',
            'provider_item_id' => 't1',
            'title'            => 'T',
            'type'             => 'article',
            'published_at'     => now()->toIso8601String(),
            'tags'             => array_fill(0, 30, 'LaraVel  '),
            'metrics'          => ['reading_time' => 5, 'reactions' => 0],
        ];

        $valid = (new ContentPayloadValidator())->validate($raw);

        $this->assertSame(['laravel'], $valid['tags']);
    }
}
