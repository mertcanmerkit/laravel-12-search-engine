<?php

namespace App\Repositories;

use App\DTO\ContentDTO;
use App\DTO\ScoreDTO;
use App\Models\Content;
use App\Models\Provider;
use Illuminate\Support\Facades\DB;

final class ContentRepository
{
    public function upsertFromDTO(ContentDTO $dto): Content
    {
        return DB::transaction(function () use ($dto) {
            // Normalize provider to a Provider record
            $provider = Provider::firstOrCreate(
                ['slug' => $dto->provider],
                ['name' => ucfirst(str_replace(['_', '-'], ' ', $dto->provider))]
            );

            return Content::updateOrCreate(
                ['provider_id' => $provider->id, 'provider_item_id' => $dto->providerItemId],
                [
                    'provider_id'  => $provider->id,
                    'title'        => $dto->title,
                    'type'         => $dto->type,
                    'published_at' => $dto->publishedAt,
                    'tags'         => $dto->tags,
                    'metrics'      => $this->packMetrics($dto),
                ]
            );
        });
    }

    public function applyScores(Content $c, ScoreDTO $s): void
    {
        $c->base_score       = $s->base;
        $c->freshness_score  = $s->freshness;
        $c->engagement_score = $s->engagement;
        $c->final_score      = $s->final;
        $c->save();
    }

    private function packMetrics(ContentDTO $dto): array
    {
        if ($dto->type === 'video') {
            /** @var \App\DTO\VideoMetricsDTO $m */
            $m = $dto->metrics;
            return [
                'views'            => $m->views,
                'likes'            => $m->likes,
                'duration_seconds' => $m->durationSeconds,
            ];
        }

        /** @var \App\DTO\ArticleMetricsDTO $m */
        $m = $dto->metrics;
        return [
            'reading_time' => $m->readingTimeMinutes,
            'reactions'    => $m->reactions,
        ];
    }
}
