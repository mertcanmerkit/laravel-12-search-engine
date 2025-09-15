<?php

namespace App\Console\Commands;

use App\Services\ProviderSync\ProviderSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ProviderSyncCommand extends Command
{
    protected $signature = 'provider:sync {--provider=} {--per-page=10}';
    protected $description = 'Kick off provider sync via queue';

    public function handle(ProviderSyncService $svc): int
    {
        $svc->kickoff(
            $this->option('provider') ?: null,
            (int) $this->option('per-page')
        );
        $this->info('Sync started.');
        return self::SUCCESS;
    }
}
