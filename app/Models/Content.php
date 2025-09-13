<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Content extends Model
{
    use SoftDeletes;
    use HasFactory, Searchable;

    protected $fillable = [
        'public_id',
        'provider_id',
        'provider_item_id',
        'title',
        'type',
        'published_at',
        'tags',
        'metrics',
        'base_score',
        'freshness_score',
        'engagement_score',
        'final_score',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'tags' => 'array',
        'metrics' => 'array',
        'base_score' => 'decimal:3',
        'freshness_score' => 'decimal:3',
        'engagement_score' => 'decimal:3',
        'final_score' => 'decimal:4',
        'synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->public_id ??= (string) Str::ulid();
        });
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class)
            ->withTimestamps();
    }

    public function toSearchableArray(): array
    {
        return [
            'title'        => $this->title,
            'type'         => $this->type,
            'tags'         => $this->tags,
            'final_score'  => (float)$this->final_score,
            'published_at' => optional($this->published_at)->toIso8601String(),
        ];
    }
}
