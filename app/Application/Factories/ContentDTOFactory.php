<?php

namespace App\Application\Factories;

use App\DTO\ArticleMetricsDTO;
use App\DTO\ContentDTO;
use App\DTO\VideoMetricsDTO;
use Carbon\Carbon;

final class ContentDTOFactory
{
    public function make(array $valid): ContentDTO
    {
        $metrics = $valid['type'] === 'video'
            ? new VideoMetricsDTO(
                views: (int)$valid['metrics']['views'],
                likes: (int)$valid['metrics']['likes'],
                durationSeconds: $valid['metrics']['duration_seconds'] ?? null,
            )
            : new ArticleMetricsDTO(
                readingTimeMinutes: (int)$valid['metrics']['reading_time'],
                reactions: (int)$valid['metrics']['reactions'],
            );

        return new ContentDTO(
            provider:        (string)$valid['provider'],
            providerItemId:  (string)$valid['provider_item_id'],
            title:           (string)$valid['title'],
            type:            (string)$valid['type'], // 'video'|'article'
            publishedAt:     Carbon::parse($valid['published_at']),
            tags:            $valid['tags'] ?? [],
            metrics:         $metrics,
            rawPayload:      $valid,
        );
    }
}
