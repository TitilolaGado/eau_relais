<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Intervention extends Model { protected $guarded=[]; public function incident(){return $this->belongsTo(Incident::class);} public function technician(){return $this->belongsTo(User::class,'technician_id');} public function parts(){return $this->hasMany(Part::class);} }
