<?php

namespace App\DTO;

use Carbon\Carbon;

final class ContentDTO
{
    public function __construct(
        public int $providerId,
        public string $providerItemId,
        public string $title,
        /** @var 'video'|'article' */
        public string $type,
        public Carbon $publishedAt,
        /** @var string[] */
        public array $tags,
        public VideoMetricsDTO|ArticleMetricsDTO $metrics,
        public array $rawPayload = [],
    ) {}
}
