<?php

namespace App\Services\ProviderSync;

use App\Application\Factories\ContentDTOFactory;
use App\Application\Validation\ContentPayloadValidator;
use App\Repositories\ContentRepository;
use App\Services\ScoringService;
use App\Services\TagSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PageProcessor
{
    public function __construct(
        private ContentPayloadValidator $validator,
        private ContentDTOFactory $factory,
        private ContentRepository $repository,
        private ScoringService $scoring,
        private TagSyncService $tags,
    ) {}

    public function process(int $providerId, array $items): void
    {
        DB::transaction(function () use ($items, $providerId) {
            foreach ($items as $raw) {
                try {
                    $raw['provider_id'] = $providerId;

                    $valid   = $this->validator->validate($raw);
                    $dto     = $this->factory->make($valid);
                    $content = $this->repository->upsertFromDTO($dto);

                    $scores = $this->scoring->compute($content);
                    $this->repository->applyScores($content, $scores);

                    $this->tags->sync($content, $dto->tags);

                } catch (\Illuminate\Validation\ValidationException $e) {
                    Log::warning('sync.validation_skip', ['reason'=>$e->getMessage()]);
                } catch (\Throwable $e) {
                    Log::error('sync.page_error', ['error'=>$e->getMessage()]);
                    throw $e;
                }
        }}, 3);
    }
}
