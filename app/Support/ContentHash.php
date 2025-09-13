<?php

namespace App\Support;

class ContentHash
{
    public static function canonicalHash(array $payload): string
    {
        $fields = [
            'title' => $payload['title'] ?? null,
            'type' => $payload['type'] ?? null,
            'metrics' => $payload['metrics'] ?? null,
            'published_at' => $payload['published_at'] ?? null,
            'tags' => $payload['tags'] ?? null,
        ];

        $ordered = [
            'title' => $fields['title'],
            'type' => $fields['type'],
            'metrics' => self::normalizeValue($fields['metrics']),
            'published_at' => $fields['published_at'],
            'tags' => self::normalizeValue($fields['tags']),
        ];

        $json = json_encode($ordered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return hash('sha256', $json);
    }

    protected static function normalizeValue($value)
    {
        if (is_array($value)) {
            if (self::isAssoc($value)) {
                ksort($value);
                foreach ($value as $k => $v) {
                    $value[$k] = self::normalizeValue($v);
                }
                return $value;
            } else {
                $mapped = array_map(function ($v) {
                    return is_array($v)
                        ? json_encode(self::normalizeValue($v), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : (string)$v;
                }, $value);
                sort($mapped, SORT_REGULAR);
                return array_map(function ($v) {
                    $decoded = json_decode($v, true);
                    return $decoded === null ? $v : $decoded;
                }, $mapped);
            }
        }
        return $value;
    }

    protected static function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
