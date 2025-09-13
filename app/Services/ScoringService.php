<?php

namespace App\Services;

use App\DTO\ScoreDTO;
use App\Models\Content;
use Carbon\CarbonInterface;

final class ScoringService
{
    public function compute(Content $c): ScoreDTO
    {
        $type    = (string) $c->type;                 // 'video' | 'article'
        $metrics = (array)  $c->metrics;
        $date    = $c->getAttribute('published_at');

        // Compute score parts
        $base           = $this->baseScore($type, $metrics);
        $multiplier     = $this->typeMultiplier($type);
        $freshnessBonus = $this->freshnessBonus($date);
        $engagement     = $this->engagementScore($type, $metrics);

        // Final score formula:
        $final = ($base * $multiplier) + $freshnessBonus + $engagement;

        return new ScoreDTO(
            base:           round($base, 4),
            typeMultiplier: $multiplier,
            freshness:      round($freshnessBonus, 4),
            engagement:     round($engagement, 4),
            final:          round($final, 4),
        );
    }

    private function baseScore(string $type, array $m): float
    {
        if ($type === 'video') {
            // Video: views / 1000 + (likes / 100)
            $views = $this->toInt($m['views'] ?? 0);
            $likes = $this->toInt($m['likes'] ?? 0);
            return ($views / 1000) + ($likes / 100);
        }

        // Article: reading_time + (reactions / 50)
        $rt   = $this->toInt($m['reading_time'] ?? 0); // minutes
        $reac = $this->toInt($m['reactions'] ?? 0);
        return $rt + ($reac / 50);
    }

    private function typeMultiplier(string $type): float
    {
        $map = config('scoring.type_multipliers', ['video' => 1.5, 'article' => 1.0]);
        return (float)($map[$type] ?? 1.0);
    }

    private function freshnessBonus(?CarbonInterface $publishedAt): float
    {
        if (!$publishedAt) {
            return 0.0;
        }

        $days = now()->diffInDays($publishedAt, true);
        return match (true) {
            $days <= 7   => 5.0,
            $days <= 30  => 3.0,
            $days <= 90  => 1.0,
            default      => 0.0,
        };
    }

    private function engagementScore(string $type, array $m): float
    {
        if ($type === 'video') {
            // Video: (likes / views) * 10
            $likes = $this->toInt($m['likes'] ?? 0);
            $views = $this->toInt($m['views'] ?? 0);
            // Guard: views == 0 -> 0.0
            return $this->safeDiv($likes, $views) * 10.0;
        }

        // Article: (reactions / reading_time) * 5
        $reac = $this->toInt($m['reactions'] ?? 0);
        $rt   = $this->toInt($m['reading_time'] ?? 0);
        // Guard: reading_time == 0 -> 0.0
        return $this->safeDiv($reac, $rt) * 5.0;
    }

    private function safeDiv(int|float $num, int|float $den): float
    {
        return $den == 0 ? 0.0 : (float) $num / (float) $den;
    }

    private function toInt(mixed $v): int
    {
        return (int) ($v ?? 0);
    }
}
