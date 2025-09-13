<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ContentSearchTable;
use App\Models\Content;
use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContentSearchTableTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function filters_and_sorts(): void
    {
        $pJson = Provider::create(['slug' => 'json_provider', 'name' => 'JSON Provider']);
        $pXml  = Provider::create(['slug' => 'xml_provider',  'name' => 'XML Provider']);

        Content::create([
            'provider_id' => $pJson->id, 'provider_item_id' => 'v1',
            'title' => 'Laravel Video', 'type' => 'video',
            'published_at' => now()->subDays(1),
            'tags' => ['php'], 'metrics' => ['views' => 1, 'likes' => 0],
            'final_score' => 10.0,
        ]);
        Content::create([
            'provider_id' => $pXml->id, 'provider_item_id' => 'a1',
            'title' => 'PHP Article', 'type' => 'article',
            'published_at' => now()->subDays(2),
            'tags' => ['php'], 'metrics' => ['reading_time' => 5, 'reactions' => 0],
            'final_score' => 40.0,
        ]);

        Livewire::test(ContentSearchTable::class)
            ->set('query', 'PHP')
            ->set('type', 'article')
            ->set('sort', 'final_score')
            ->set('order', 'desc')
            ->assertSee('PHP Article')
            ->assertDontSee('Laravel Video');
    }
}
