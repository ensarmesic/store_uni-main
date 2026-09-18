<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantPriceHistory extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['price' => 'float', 'recorded_at' => 'datetime'];
}
