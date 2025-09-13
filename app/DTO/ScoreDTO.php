<?php

namespace App\DTO;

final class ScoreDTO
{
    public function __construct(
        public float $base,
        public float $typeMultiplier,
        public float $freshness,
        public float $engagement,
        public float $final,
    ) {}
}
