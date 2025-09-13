<?php

namespace App\DTO;

final class ProviderPageDTO
{
    /**
     * @param array<int,array> $items
     */
    public function __construct(
        public array $items,
        public ?int $nextPage,
        public int $page,
        public int $perPage,
        public ?int $total = null,
    ) {}
}
