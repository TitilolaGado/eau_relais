<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Maintenance extends Model { protected $guarded=[]; public function waterPoint(){return $this->belongsTo(WaterPoint::class);} public function responsible(){return $this->belongsTo(User::class,'responsible_id');} }
