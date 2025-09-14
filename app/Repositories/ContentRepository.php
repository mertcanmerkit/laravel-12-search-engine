<?php

namespace App\Repositories;

use App\DTO\ContentDTO;
use App\DTO\ScoreDTO;
use App\Models\Content;
use App\Models\Provider;
use App\Support\ContentHash;
use Illuminate\Support\Facades\DB;

final class ContentRepository
{
    public function upsertFromDTO(ContentDTO $dto): Content
    {
        return DB::transaction(function () use ($dto) {
            $provider = Provider::firstOrCreate(
                ['slug' => $dto->provider],
                ['name' => ucfirst(str_replace(['_', '-'], ' ', $dto->provider))]
            );

            $canonicalPayload = $this->makeCanonicalPayload($dto);
            $hash             = ContentHash::canonicalHash($canonicalPayload);

            /** @var Content|null $existing */
            $existing = Content::where('provider_id', $provider->id)
                ->where('provider_item_id', $dto->providerItemId)
                ->first();

            if ($existing && $existing->content_hash === $hash) {
                $existing->fill(['synced_at' => now()])->saveQuietly();
                return $existing->refresh();
            }

            $attributes = [
                'provider_id'      => $provider->id,
                'provider_item_id' => $dto->providerItemId,

                ...$canonicalPayload,

                'content_hash' => $hash,
                'synced_at'    => now()
            ];


            if ($existing) {
                $existing->fill($attributes)->save();
                return $existing->refresh();
            }

            return Content::create($attributes);
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

    private function makeCanonicalPayload(ContentDTO $dto): array
    {
        return [
            'title'        => $dto->title,
            'type'         => $dto->type,
            'metrics'      => $this->packMetrics($dto),
            'published_at' => $dto->publishedAt,
            'tags'         => $dto->tags,
        ];
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
