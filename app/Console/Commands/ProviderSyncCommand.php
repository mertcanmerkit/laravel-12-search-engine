<?php

namespace App\Console\Commands;

use App\Services\ProviderSync\ProviderSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ProviderSyncCommand extends Command
{
    protected $signature = 'provider:sync {--per-page=50} {--max-pages=1}';
    protected $description = 'Fetches data from providers';

    public function handle(ProviderSyncService $service): int
    {
        $perPage  = (int) $this->option('per-page');
        $maxPages = (int) $this->option('max-pages');

        $summary = $service->runOnce($perPage, $maxPages);

        Cache::tags(['contents'])->flush();

        $this->info("Processed: {$summary['processed']} | Skipped: {$summary['skipped']}");
        return self::SUCCESS;
    }
}
