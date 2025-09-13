<?php

namespace App\DTO;

final class ArticleMetricsDTO
{
    public function __construct(
        public int $readingTimeMinutes,
        public int $reactions,
    ) {}
}
