<?php

namespace Tests\Feature\Services;

use App\Models\Content;
use App\Models\Provider;
use App\Services\ContentSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContentSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function returns_cached_paginated_results(): void
    {
        $pXml = Provider::create(['slug' => 'xml_provider', 'name' => 'XML Provider']);

        Content::create([
            'provider_id' => $pXml->id,
            'provider_item_id' => 'a1',
            'title' => 'PHP Article',
            'type' => 'article',
            'published_at' => now(),
            'tags' => ['php'],
            'metrics' => ['reading_time' => 5, 'reactions' => 0],
            'final_score' => 10,
        ]);

        $svc = new ContentSearchService();
        $page = $svc->search(['q' => 'php', 'per_page' => 10, 'page' => 1]);

        $this->assertSame(1, $page->total());
        $this->assertSame('PHP Article', $page->items()[0]->title);
    }
}
