# Search Engine (Laravel + Livewire + Scout + Horizon)

A search application that ingests content from external providers (JSON and XML), normalizes and scores it, syncs, and indexes searchable data via Laravel Scout (Meilisearch). Includes a Livewire UI for searching, filtering, and sorting.

- Runtime: PHP 8.2, Laravel 12, Node 20+
- Services: PostgreSQL, Redis, Meilisearch (via Docker), Horizon

## Contents
- Features
- Architecture overview
- Requirements
- Setup & local development
- Environment configuration
- Provider sync
- Search (Scout/Meilisearch)
- Queues & Horizon
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
  - Selected via app/Services/ProviderSync/ProviderClientFactory based on Provider.slug in the database (e.g., json_provider, xml_provider).

- Ingestion pipeline
  - app/Services/ProviderSync/ProviderSyncService.php orchestrates ingestion and enqueues page-fetch jobs.
  - app/Jobs/FetchProviderPage.php fetches, processes, and chains next pages on the "sync" queue.
  - app/Application/Validation/ContentPayloadValidator validates provider payloads.
  - app/Application/Factories/ContentDTOFactory builds typed DTOs (app/DTO/*).
  - app/Repositories/ContentRepository upserts normalized Content and persists metrics; also applies scores from app/Services/ScoringService.
  - app/Services/TagSyncService maintains tag relationships.

- Domain & persistence
  - Models: app/Models/Content, Provider, Tag, SyncState.
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
- Configure Redis (see Environment configuration).
- Configure Scout to use Meilisearch (see Search section).

3) Run migrations (includes seeding default providers)

```sh
php artisan migrate:fresh --seed
```

4) Start the app

Option A — full dev stack (app + Horizon + logs + Vite):

```sh
composer run dev
```

Option B — run pieces manually:

```sh
php artisan serve
php artisan horizon
npm run dev
```

Run scheduled tasks:

```sh
php artisan schedule:work
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
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

- Scout / Meilisearch

```dotenv
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=masterKey
```

Note: Provider endpoints and rate limits are configured via database records (seeded by ProviderSeeder). Edit the Provider table (slug, base_url, rate_per_minute) to change sources.

## Provider sync
The ingestion pipeline fetches from configured providers, validates payloads, maps to DTOs, upserts Content, computes scores, syncs tags, and enqueues subsequent pages.

- Command

```sh
php artisan provider:sync
```

- Options:
  - `--provider=` Limit to a provider slug (e.g., json_provider, xml_provider)
  - `--per-page=` Page size to request from providers (default 10)

- Jobs run on the `sync` queue; make sure a Redis-backed worker (e.g., Horizon) is running.
- To automate, schedule the command as needed.

## Search (Scout / Meilisearch)
- Set relevant env keys in .env

```dotenv
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=masterKey
```

- "contents" index settings (filterable/sortable attributes) are declared in config/scout.php.
- The Content model exposes `title`, `type`, `tags`, `final_score`, and `published_at` to the index.

```sh
php artisan scout:flush "App\\Models\\Content"
php artisan scout:import "App\\Models\\Content"
php artisan scout:sync-index-settings
```

## Queues & Horizon
Horizon manages Redis-backed queue workers and provides a dashboard.

- Start workers locally
  - `php artisan horizon`

- Dashboard
  - Open http://localhost:8000/horizon (local only by default)

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
