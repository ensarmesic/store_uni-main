<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchNotification extends Model
{
    protected $guarded = [];
    protected $casts = ['payload' => 'array', 'read_at' => 'datetime', 'emailed_at' => 'datetime', 'last_attempt_at' => 'datetime'];
    public function watch() { return $this->belongsTo(ShoppingWatch::class, 'shopping_watch_id'); }
}
