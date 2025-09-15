<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncState extends Model
{
    protected $fillable = ['provider_id','next_page','last_synced_at'];
}
