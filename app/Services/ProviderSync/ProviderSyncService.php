<?php

namespace App\Services\ProviderSync;

use App\Application\Factories\ContentDTOFactory;
use App\Application\Validation\ContentPayloadValidator;
use App\Integrations\Providers\JsonProviderClient;
use App\Integrations\Providers\XmlProviderClient;
use App\Repositories\ContentRepository;
use App\Services\ScoringService;
use App\Services\TagSyncService;
use Illuminate\Support\Facades\Log;

final class ProviderSyncService
{
    public function __construct(
        private JsonProviderClient $jsonClient,
        private XmlProviderClient $xmlClient,
        private ContentPayloadValidator $validator,
        private ContentDTOFactory $factory,
        private ContentRepository $repository,
        private ScoringService $scoring,
        private TagSyncService $tags,
    ) {}

    public function runOnce(int $perPage = 50, int $maxPages = 1): array
    {
        $processed = 0;
        $skipped   = 0;

        foreach ([$this->jsonClient, $this->xmlClient] as $client) {
            foreach ($client->streamAll(1, $perPage, $maxPages) as $raw) {
                try {
                    $valid   = $this->validator->validate($raw);
                    $dto     = $this->factory->make($valid);
                    $content = $this->repository->upsertFromDTO($dto);

                    $scores = $this->scoring->compute($content);
                    $this->repository->applyScores($content, $scores);

                    $this->tags->sync($content, $dto->tags);

                    $processed++;
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $skipped++;
                    Log::warning('provider_sync.skip', [
                        'provider'         => $raw['provider'] ?? 'unknown',
                        'provider_item_id' => $raw['provider_item_id'] ?? null,
                        'reason'           => $e->getMessage(),
                    ]);
                } catch (\Throwable $e) {
                    $skipped++;
                    Log::error('provider_sync.error', [
                        'provider' => $raw['provider'] ?? 'unknown',
                        'error'    => $e->getMessage(),
                    ]);
                }
            }
        }

        Log::info('provider_sync.summary', compact('processed', 'skipped'));
        return compact('processed', 'skipped');
    }
}
