<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferVariantAvailabilityHistory extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['recorded_at' => 'datetime'];

    public function variant()
    {
        return $this->belongsTo(OfferVariant::class, 'offer_variant_id');
    }
}
