<?php

namespace App\DTO;

final class VideoMetricsDTO
{
    public function __construct(
        public int $views,
        public int $likes,
        public ?int $durationSeconds = null,
    ) {}
}
