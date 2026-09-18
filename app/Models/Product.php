<?php
namespace App\Models; use Illuminate\Database\Eloquent\Builder;use Illuminate\Database\Eloquent\Model;
class Product extends Model {protected $guarded=[];public function offers(){return $this->hasMany(Offer::class);}public function scopeAvailableForSize(Builder $q,string $size):Builder{return $q->whereHas('offers.variants',fn($v)=>$v->where('size',$size)->where('availability','in_stock'));}}
