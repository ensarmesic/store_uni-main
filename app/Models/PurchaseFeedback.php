<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseFeedback extends Model
{
    protected $table = 'purchase_feedback';
    protected $guarded = [];
    public function product() { return $this->belongsTo(Product::class); }
}
