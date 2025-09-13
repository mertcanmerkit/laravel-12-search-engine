<?php

namespace App\Integrations\Providers\Contracts;

use App\DTO\ProviderPageDTO;

interface ProviderClient
{
    public function fetchPage(int $page, int $perPage): ProviderPageDTO;

    /** Streams raw items across pages. */
    public function streamAll(int $startPage, int $perPage, int $maxPages = 10): \Generator;
}
