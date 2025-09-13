<?php

namespace Tests\Feature\Services;

use App\Application\Factories\ContentDTOFactory;
use App\Application\Validation\ContentPayloadValidator;
use App\Integrations\Providers\JsonProviderClient;
use App\Integrations\Providers\XmlProviderClient;
use App\Models\Provider;
use App\Repositories\ContentRepository;
use App\Services\ProviderSync\ProviderSyncService;
use App\Services\ScoringService;
use App\Services\TagSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProviderSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function processes_valid_items_and_skips_invalid_ones(): void
    {
        $jsonUrl = 'https://example.test/provider1';
        $xmlUrl  = 'https://example.test/provider2';

        config()->set('services.providers.json_url', $jsonUrl);
        config()->set('services.providers.xml_url',  $xmlUrl);

        Http::fake([
            $jsonUrl => Http::response([
                'contents' => [
                    [
                        'id' => 'v1',
                        'title' => 'Video OK',
                        'type' => 'video',
                        'published_at' => now()->toIso8601String(),
                        'tags' => ['PHP'],
                        'metrics' => ['views' => 1000, 'likes' => 50, 'duration' => '10:00'],
                    ],
                    [
                        'id' => 'v-bad',
                        'title' => 'Video BAD',
                        'type' => 'video',
                        'published_at' => now()->toIso8601String(),
                        'tags' => ['X'],
                        'metrics' => ['views' => -1, 'likes' => 0, 'duration' => '05:00'], // invalid
                    ],
                ],
                'pagination' => ['total' => 2, 'page' => 1, 'per_page' => 50],
            ], 200),
            $xmlUrl => Http::response(<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<feed>
  <items>
    <item>
      <id>a1</id>
      <headline>Article OK</headline>
      <type>article</type>
      <stats>
        <reading_time>7</reading_time>
        <reactions>21</reactions>
      </stats>
      <publication_date>2025-09-01</publication_date>
      <categories><category>Backend</category></categories>
    </item>
  </items>
  <meta><total_count>1</total_count><current_page>1</current_page><items_per_page>50</items_per_page></meta>
</feed>
XML, 200),
        ]);

        Log::spy();

        $service = new ProviderSyncService(
            new JsonProviderClient($jsonUrl),
            new XmlProviderClient($xmlUrl),
            new ContentPayloadValidator(),
            new ContentDTOFactory(),
            new ContentRepository(),
            new ScoringService(),
            new TagSyncService(),
        );

        $summary = $service->runOnce(50, 1);

        $this->assertSame(2, $summary['processed']); // 1 video + 1 article
        $this->assertSame(1, $summary['skipped']);   // invalid video

        Log::shouldHaveReceived('warning')->once();

        $this->assertDatabaseCount('contents', 2);

        $jsonProvider = Provider::where('slug', 'json_provider')->first();
        $xmlProvider  = Provider::where('slug', 'xml_provider')->first();

        $this->assertNotNull($jsonProvider);
        $this->assertNotNull($xmlProvider);

        $this->assertDatabaseHas('contents', ['provider_id' => $jsonProvider->id, 'provider_item_id' => 'v1']);
        $this->assertDatabaseHas('contents', ['provider_id' => $xmlProvider->id,  'provider_item_id' => 'a1']);
    }
}
