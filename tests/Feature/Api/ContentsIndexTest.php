<?php

namespace Tests\Feature\Api;

use App\Models\Content;
use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContentsIndexTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function lists_paginated_contents_with_filters_and_sort(): void
    {
        $pJson = Provider::create(['slug' => 'json_provider', 'name' => 'JSON Provider']);
        $pXml  = Provider::create(['slug' => 'xml_provider',  'name' => 'XML Provider']);

        Content::create([
            'provider_id' => $pJson->id,
            'provider_item_id' => 'v1',
            'title' => 'Laravel Video',
            'type' => 'video',
            'published_at' => now()->subDays(1),
            'tags' => ['php'],
            'metrics' => ['views' => 1000, 'likes' => 50],
            'final_score' => 10.5,
        ]);

        Content::create([
            'provider_id' => $pXml->id,
            'provider_item_id' => 'a1',
            'title' => 'PHP Article',
            'type' => 'article',
            'published_at' => now()->subDays(2),
            'tags' => ['php'],
            'metrics' => ['reading_time' => 7, 'reactions' => 21],
            'final_score' => 36.8,
        ]);

        $resp = $this->getJson('/api/contents?type=article&sort=final_score&order=desc&per_page=1&page=1');

        $resp->assertOk()
            ->assertJsonStructure([
                'data' => [['id','title','type','tags','final_score','published_at']],
                'links' => ['first','last','prev','next'],
                'meta'  => ['current_page','last_page','from','to','per_page','total'],
            ]);

        $this->assertSame('PHP Article', $resp->json('data.0.title'));
    }

    #[Test]
    public function applies_query_search_fallback_on_db(): void
    {
        $pJson = Provider::create(['slug' => 'json_provider', 'name' => 'JSON Provider']);

        Content::create([
            'provider_id' => $pJson->id,
            'provider_item_id' => 'v2',
            'title' => 'Elasticsearch Intro',
            'type' => 'article',
            'published_at' => now()->subDays(3),
            'tags' => ['search'],
            'metrics' => ['reading_time' => 5, 'reactions' => 0],
            'final_score' => 5.0,
        ]);

        $resp = $this->getJson('/api/contents?q=elastic');

        $resp->assertOk();
        $this->assertSame('Elasticsearch Intro', $resp->json('data.0.title'));
    }
}
