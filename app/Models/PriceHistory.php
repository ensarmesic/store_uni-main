<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class PriceHistory extends Model {public $timestamps=false;protected $guarded=[];protected $casts=['recorded_at'=>'datetime'];}
