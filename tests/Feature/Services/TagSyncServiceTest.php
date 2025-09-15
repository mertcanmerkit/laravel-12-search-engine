<?php

namespace Tests\Feature\Services;

use App\Models\Content;
use App\Models\Provider;
use App\Models\Tag;
use App\Services\TagSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TagSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function creates_missing_tags_and_syncs_pivot_and_jsonb(): void
    {
        $pJson = Provider::factory()->json()->create();
        $content = Content::create([
            'provider_id' => $pJson->id,
            'provider_item_id' => 'X1',
            'title' => 'T',
            'type' => 'video',
            'published_at' => now(),
            'tags' => [],
            'metrics' => ['views' => 1, 'likes' => 0],
        ]);

        $svc = new TagSyncService();
        $ids = $svc->sync($content, [' PHP ', 'laravel', 'php']);

        $this->assertGreaterThan(0, count($ids));
        $this->assertSame(['laravel','php'], Tag::orderBy('name')->pluck('name')->all());
        $content->refresh();
        $this->assertSame(['laravel','php'], $content->tags);
        $this->assertCount(2, $content->tags()->pluck('tags.id'));
    }

    #[Test]
    public function normalizes_and_limits_names(): void
    {
        $pJson = Provider::factory()->json()->create();
        $content = Content::create([
            'provider_id' => $pJson->id,
            'provider_item_id' => 'X2',
            'title' => 'T2',
            'type' => 'article',
            'published_at' => now(),
            'tags' => [],
            'metrics' => ['reading_time' => 5, 'reactions' => 0],
        ]);

        // 30 duplicates; should result in 1 tag "laravel"
        $names = array_fill(0, 30, '  LARAVEL  ');
        $ids = (new TagSyncService())->sync($content, $names);

        $content->refresh();
        $this->assertSame(['laravel'], $content->tags);
        $this->assertCount(1, $ids);
    }

    #[Test]
    public function clears_when_empty_list(): void
    {
        $t1 = Tag::create(['name' => 'php', 'slug' => 'php']);
        $t2 = Tag::create(['name' => 'laravel', 'slug' => 'laravel']);

        $pXml = Provider::factory()->xml()->create();
        $content = Content::create([
            'provider_id' => $pXml->id,
            'provider_item_id' => 'X3',
            'title' => 'T3',
            'type' => 'video',
            'published_at' => now(),
            'tags' => ['php','laravel'],
            'metrics' => ['views' => 10, 'likes' => 1],
        ]);

        $content->tags()->sync([$t1->id, $t2->id]);

        (new TagSyncService())->sync($content, []);

        $content->refresh();
        $this->assertSame([], $content->tags);
        $this->assertCount(0, $content->tags()->pluck('tags.id'));
    }
}
