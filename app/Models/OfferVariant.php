<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class OfferVariant extends Model {protected $guarded=[];public function offer(){return $this->belongsTo(Offer::class);}public function availabilityHistories(){return $this->hasMany(OfferVariantAvailabilityHistory::class);}}
