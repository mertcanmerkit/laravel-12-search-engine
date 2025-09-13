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
    ];

    public function contents()
    {
        return $this->hasMany(Content::class);
    }
}

