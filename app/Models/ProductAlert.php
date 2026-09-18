<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAlert extends Model
{
    protected $guarded = [];

    protected $casts = [
        'target_price' => 'decimal:2',
        'is_active' => 'boolean',
        'triggered_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
