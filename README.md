# Search Engine (Laravel + Livewire + Scout)

A search application that ingests content from external providers (JSON and XML), normalizes and scores it, syncs, and indexes searchable data via Laravel Scout (Meilisearch). Includes a Livewire UI for searching, filtering, and sorting.

- Runtime: PHP 8.2, Laravel 12, Node 20+
- Services: PostgreSQL, Redis, Meilisearch (via Docker)

## Contents
- Features
- Architecture overview
- Requirements
- Setup & local development
- Environment configuration
- Provider sync
- Search (Scout/Meilisearch)
- Running with Docker services
- Testing

## Features
- Provider ingestion with rate limiting (JSON + XML)
- Validation, DTO mapping, repository upsert, scoring, tag sync
- Searchable Content model via Laravel Scout
- Livewire table with query, filters, sorting, and pagination

## Architecture overview
Key decisions and module boundaries:

- Integrations (Provider clients)
  - app/Integrations/Providers/JsonProviderClient.php
  - app/Integrations/Providers/XmlProviderClient.php
  - HTTP via Laravel HTTP client with retry/timeout.
  - Basic rate limiting using Illuminate RateLimiter (providers.rate_per_minute).
  - Bound via App\Providers\ProviderIntegrationServiceProvider using config/services.php.

- Ingestion pipeline
  - app/Services/ProviderSync/ProviderSyncService.php orchestrates ingestion from both providers.
  - app/Application/Validation/ContentPayloadValidator validates provider payloads.
  - app/Application/Factories/ContentDTOFactory builds typed DTOs (app/DTO/*).
  - app/Repositories/ContentRepository upserts normalized Content and persists metrics; also applies scores from app/Services/ScoringService.
  - app/Services/TagSyncService maintains tag relationships.

- Domain & persistence
  - Models: app/Models/Content, Provider, Tag.
  - Content::toSearchableArray controls indexed fields.

- Search
  - Laravel Scout with Meilisearch config present in config/scout.php.

- UI
  - Livewire component app/Livewire/ContentSearchTable drives a searchable, sortable table.

## Requirements
- PHP >= 8.2
- Composer
- Node.js 20+ and npm
- Docker (for Postgres, Redis, Meilisearch)

## Setup & local development
1) Install dependencies

```sh
composer install
npm install
```

2) Configure environment
- Copy example env and generate key:

```sh
cp .env.example .env
php artisan key:generate
```

- Choose a database:
  - Set in .env: `DB_CONNECTION=pgsql` and adjust host/port/db/user/pass.
- Configure provider URLs in .env (see Environment configuration).
- Configure Redis (see Environment configuration).
- Configure Scout to use Meilisearch (see Search section).

3) Run migrations

```sh
php artisan migrate
```

4) Start the app

```sh
php artisan serve
npm run dev
```

App will be available at http://localhost:8000 by default.

## Environment configuration
Relevant env keys:

- Core

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

- Database (Postgres)

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=app
DB_USERNAME=app
DB_PASSWORD=secret
```

- Cache / Queue

```dotenv
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

- Providers

```dotenv
PROVIDER_JSON_URL=https://raw.githubusercontent.com/WEG-Technology/mock/refs/heads/main/v2/provider1
PROVIDER_XML_URL=https://raw.githubusercontent.com/WEG-Technology/mock/refs/heads/main/v2/provider2
PROVIDER_RATE_PER_MINUTE=60
```

- Scout / Meilisearch

```dotenv
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=masterKey
```

## Provider sync
The ingestion pipeline fetches from configured providers, validates payloads, maps to DTOs, upserts Content, computes scores, syncs tags, and logs summary.

- Command

```sh
php artisan provider:sync
```

- Options:
  - `--per-page` Page size to request from providers (default 50)
  - `--max-pages` Max pages to fetch (default 1)

- After a run, cache tagged "contents" is flushed.
- Failures are logged (provider_sync.skip for validation, provider_sync.error for exceptions) and counted as "Skipped" in the summary.
- To automate, add it to a scheduler/cron as needed.

## Search (Scout / Meilisearch)
- Set relevant env keys in .env

```dotenv
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=masterKey
```

- "contents" index settings (filterable/sortable attributes) are declared in config/scout.php.
- The Content model exposes `title`, `type`, `tags`, `final_score`, and `published_at` to the index.
- Useful commands:

```sh
php artisan scout:sync-index-settings
php artisan scout:flush "App\\Models\\Content"
php artisan scout:import "App\\Models\\Content"
```

## Running with Docker services
This repo includes docker-compose for data services only (app runs locally):

- Services (docker-compose.yml)
  - Postgres 16 (db=app, user=app, password=secret, port 5432)
  - Redis 7 (port 6379)
  - Meilisearch v1.10 (port 7700, MEILI_MASTER_KEY=masterKey)

Steps:

```sh
docker compose up -d
```

2) Update .env to point to these services (examples):

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=app
DB_USERNAME=app
DB_PASSWORD=secret
REDIS_HOST=127.0.0.1
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=masterKey
```

3) Run migrations:

```sh
php artisan migrate
```

## Testing
- Base testing config: phpunit.xml sets SQLite in-memory.
- Quick start:

```sh
cp .env.testing.example .env.testing
php artisan key:generate --env=testing
php artisan test
```

## License
MIT
