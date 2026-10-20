<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TechnicianProfile extends Model { protected $guarded=[]; protected function casts():array{return ['skills'=>'array','referenced'=>'boolean'];} public function user(){return $this->belongsTo(User::class);} public function zones(){return $this->belongsToMany(Commune::class,'technician_zones');} }
