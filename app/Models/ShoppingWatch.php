<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShoppingWatch extends Model
{
    protected $guarded = [];
    protected $attributes = ['is_active' => true, 'last_signal' => false, 'generation' => 0];
    protected $casts = ['is_active' => 'boolean', 'last_signal' => 'boolean', 'last_notified_at' => 'datetime', 'email_verified_at' => 'datetime'];
    public function product() { return $this->belongsTo(Product::class); }
    public function notifications() { return $this->hasMany(WatchNotification::class); }
}
