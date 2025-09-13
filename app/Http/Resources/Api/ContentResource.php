<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class ContentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => (string)$this->public_id,
            'title'        => $this->title,
            'type'         => $this->type,
            'tags'         => (array)$this->tags,
            'final_score'  => (float)$this->final_score,
            'published_at' => optional($this->published_at)->toIso8601String(),
        ];
    }
}
