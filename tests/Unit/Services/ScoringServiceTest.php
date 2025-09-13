<?php

namespace Tests\Unit\Services;

use App\Models\Content;
use App\Services\ScoringService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScoringServiceTest extends TestCase
{
    #[Test]
    public function scores_video_with_recent_bonus(): void
    {
        $c = new Content([
            'type' => 'video',
            'published_at' => Carbon::now()->subDays(2),
            'metrics' => ['views' => 3000, 'likes' => 150, 'duration_seconds' => 120],
        ]);

        $s = (new ScoringService())->compute($c);

        // base = 3000/1000 + 150/100 = 3 + 1.5 = 4.5
        // typeMult 1.5 → 6.75; freshness 5; engagement (150/3000)*10 = 0.5
        // final ≈ 12.25
        $this->assertEqualsWithDelta(4.5, $s->base, 0.0001);
        $this->assertSame(1.5, $s->typeMultiplier);
        $this->assertSame(5.0, $s->freshness);
        $this->assertEqualsWithDelta(0.5, $s->engagement, 0.0001);
        $this->assertEqualsWithDelta(12.25, $s->final, 0.0001);
    }

    #[Test]
    public function scores_article_with_30_day_bonus(): void
    {
        $c = new Content([
            'type' => 'article',
            'published_at' => Carbon::now()->subDays(20),
            'metrics' => ['reading_time' => 8, 'reactions' => 40],
        ]);

        $s = (new ScoringService())->compute($c);

        // base = 8 + 40/50 = 8.8; mult 1.0 → 8.8; freshness 3; engagement (40/8)*5 = 25
        // final = 36.8
        $this->assertEqualsWithDelta(8.8, $s->base, 0.0001);
        $this->assertSame(1.0, $s->typeMultiplier);
        $this->assertSame(3.0, $s->freshness);
        $this->assertEqualsWithDelta(25.0, $s->engagement, 0.0001);
        $this->assertEqualsWithDelta(36.8, $s->final, 0.0001);
    }

    #[Test]
    public function guards_division_by_zero_for_video(): void
    {
        $c = new Content([
            'type' => 'video',
            'published_at' => Carbon::now()->subDays(100),
            'metrics' => ['views' => 0, 'likes' => 10],
        ]);

        $s = (new ScoringService())->compute($c);

        $this->assertSame(0.0, $s->freshness);       // >90 days
        $this->assertSame(0.0, $s->engagement);      // likes/views with views=0
        $this->assertEqualsWithDelta(0.1, $s->base, 0.0001);
    }

    #[Test]
    public function applies_90_day_bonus(): void
    {
        $c = new Content([
            'type' => 'article',
            'published_at' => Carbon::now()->subDays(75),
            'metrics' => ['reading_time' => 10, 'reactions' => 10],
        ]);

        $s = (new ScoringService())->compute($c);

        $this->assertSame(1.0, $s->freshness); // 31..90
    }
}
