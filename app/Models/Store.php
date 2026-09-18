<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class Store extends Model {protected $guarded=[];protected $casts=['is_active'=>'boolean','last_synced_at'=>'datetime'];public function offers(){return $this->hasMany(Offer::class);}}
