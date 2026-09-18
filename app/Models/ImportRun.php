<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class ImportRun extends Model {protected $guarded=[];protected $casts=['errors'=>'array','started_at'=>'datetime','finished_at'=>'datetime'];}
