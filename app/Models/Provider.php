<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'base_url',
        'rate_per_minute',
    ];

    protected $casts = [
        'rate_per_minute' => 'integer',
    ];

    public function contents()
    {
        return $this->hasMany(Content::class);
    }
}

