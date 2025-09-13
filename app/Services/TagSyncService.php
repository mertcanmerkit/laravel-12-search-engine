<?php

namespace App\Services;

use App\Models\Content;
use App\Models\Tag;
use Illuminate\Support\Str;

final class TagSyncService
{
    public function sync(Content $content, array $names): array
    {
        $names = $this->normalize($names);
        if (empty($names)) {
            $content->tags()->sync([]);
            $content->tags = [];
            $content->save();
            return [];
        }

        $existing = Tag::query()
            ->whereIn('name', $names)
            ->pluck('id', 'name')
            ->all();

        $missing = array_values(array_diff($names, array_keys($existing)));

        if (!empty($missing)) {
            $rows = array_map(function (string $name) {
                return [
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $missing);

            Tag::insertOrIgnore($rows);

            $existing = Tag::query()
                ->whereIn('name', $names)
                ->pluck('id', 'name')
                ->all();
        }

        $tagIds = array_values($existing);
        $content->tags()->sync($tagIds);

        // Persist normalized names to jsonb for Meili filters
        $pivotNames = Tag::query()->whereIn('id', $tagIds)->orderBy('name')->pluck('name')->all();
        $content->tags = $pivotNames;
        $content->save();

        return $tagIds;
    }

    private function normalize(array $names): array
    {
        $names = array_map(
            fn ($n) => Str::of((string)$n)->lower()->trim()->substr(0, 64)->value(),
            $names
        );

        $names = array_values(array_unique(array_filter($names)));
        return array_slice($names, 0, 20);
    }
}
