<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Incident extends Model { protected $guarded=[]; protected $hidden=['tracking_hash','phone','reporter_name']; protected function casts():array{return ['first_response_at'=>'datetime','confirmed_at'=>'datetime'];} public function locality(){return $this->belongsTo(Locality::class);} public function waterPoint(){return $this->belongsTo(WaterPoint::class);} public function histories(){return $this->hasMany(StatusHistory::class)->orderBy('id');} public function interventions(){return $this->hasMany(Intervention::class);} public function attachments(){return $this->hasMany(Attachment::class);} }
