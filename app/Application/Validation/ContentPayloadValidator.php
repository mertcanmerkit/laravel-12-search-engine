<?php

namespace App\Application\Validation;

use App\Support\DurationParser;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

final class ContentPayloadValidator
{
    public function validate(array $raw): array
    {
        $base = Validator::make($raw, [
            'provider_id'      => ['required','integer'],
            'provider_item_id' => ['required','string'],
            'title'            => ['required','string'],
            'type'             => ['required','in:video,article'],
            'published_at'     => ['required','date'],
            'metrics'          => ['required','array'],
        ])->validate();

        // Type-specific rules
        if ($base['type'] === 'video') {
            Validator::make($raw, [
                'metrics.views'    => ['required','integer','min:0'],
                'metrics.likes'    => ['required','integer','min:0'],
                'metrics.duration' => ['nullable','string'],
            ])->validate();
        } else {
            Validator::make($raw, [
                'metrics.reading_time' => ['required','integer','min:1'],
                'metrics.reactions'    => ['required','integer','min:0'],
            ])->validate();
        }

        $tags = $this->normalizeTags($raw);
        $durationSeconds = null;

        if (($raw['type'] ?? null) === 'video') {
            $durationSeconds = DurationParser::toSeconds(Arr::get($raw, 'metrics.duration'));
        }

        $normalized = $raw;
        $normalized['tags'] = $tags;

        if ($raw['type'] === 'video') {
            $normalized['metrics'] = [
                'views'            => (int)$raw['metrics']['views'],
                'likes'            => (int)$raw['metrics']['likes'],
                'duration_seconds' => $durationSeconds,
            ];
        } else {
            $normalized['metrics'] = [
                'reading_time' => (int)$raw['metrics']['reading_time'],
                'reactions'    => (int)$raw['metrics']['reactions'],
            ];
        }

        return $normalized;
    }

    private function normalizeTags(array $raw): array
    {
        $tags = $raw['tags'] ?? $raw['categories'] ?? [];
        $tags = is_array($tags) ? $tags : [$tags];

        $tags = array_map(fn ($t) =>
        Str::of((string)$t)->lower()->trim()->substr(0, 64)->value(),
            $tags);

        $tags = array_values(array_unique(array_filter($tags)));
        return array_slice($tags, 0, 20);
    }
}
