<?php

namespace App\DTO;

use Carbon\Carbon;

final class ContentDTO
{
    public function __construct(
        public string $provider,
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
