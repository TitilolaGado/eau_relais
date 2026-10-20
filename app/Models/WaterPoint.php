<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WaterPoint extends Model { protected $guarded=[]; public function locality(){return $this->belongsTo(Locality::class);} public function manager(){return $this->belongsTo(User::class,'manager_id');} public function maintenances(){return $this->hasMany(Maintenance::class);} }
