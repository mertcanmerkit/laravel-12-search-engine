<?php

namespace App\Console\Commands;

use App\Models\Content;
use Illuminate\Console\Command;
use Meilisearch\Client;

class SearchConfigureCommand extends Command
{
    protected $signature = 'search:configure {--reindex : Run scout import after settings}';
    protected $description = 'Apply Meilisearch index settings';

    public function handle(): int
    {
        if (config('scout.driver') !== 'meilisearch') {
            $this->warn('SCOUT_DRIVER is not meilisearch.');
            return self::SUCCESS;
        }

        $client = new Client(
            config('scout.meilisearch.host'),
            config('scout.meilisearch.key')
        );

        $indexUid = (new Content)->searchableAs();
        // Get index
        try {
            $client->getIndex($indexUid);
        } catch (\Throwable $e) {
            $client->createIndex($indexUid);
        }

        $client->index($indexUid)->updateSettings([
            'filterableAttributes' => ['type', 'tags'],
            'sortableAttributes'   => ['final_score', 'published_at', 'title', 'type'],
        ]);

        $this->info("Meilisearch settings updated for index: {$indexUid}");

        if ($this->option('reindex')) {
            // Rebuild the index via Scout
            $this->call('scout:import', ['model' => Content::class]);
        }

        return self::SUCCESS;
    }
}
