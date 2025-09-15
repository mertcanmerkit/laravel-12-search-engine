<?php

namespace Tests\Feature\Integrations;

use App\Integrations\Providers\JsonProviderClient;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class JsonProviderClientTest extends TestCase
{
    #[Test]
    public function maps_json_payload_to_items_and_meta(): void
    {
        $url = 'https://example.test/provider1';
        Http::fake([
            $url => Http::response([
                'contents' => [
                    [
                        'id' => 'v1',
                        'title' => 'Video #1',
                        'type' => 'video',
                        'published_at' => now()->toIso8601String(),
                        'tags' => ['php','laravel'],
                        'metrics' => ['views' => 1000, 'likes' => 50, 'duration' => '10:00'],
                    ],
                ],
                'pagination' => ['total' => 1, 'page' => 1, 'per_page' => 50],
            ], 200),
        ]);

        $client = new JsonProviderClient($url);
        $page = $client->fetchPage(1, 50);

        $this->assertSame(1, $page->page);
        $this->assertSame(50, $page->perPage);
        $this->assertNull($page->nextPage);
        $this->assertCount(1, $page->items);
        $this->assertSame('v1', $page->items[0]['provider_item_id']);

        // streamAll yields raw items
        $items = iterator_to_array($client->streamAll(1, 50));
        $this->assertCount(1, $items);
        $this->assertSame('Video #1', $items[0]['title']);
    }
}
