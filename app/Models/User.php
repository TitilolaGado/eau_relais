<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
class User extends Authenticatable {
 protected $guarded=[]; protected $hidden=['password','remember_token'];
 protected function casts():array{return ['password'=>'hashed'];}
 public function notifications(){return $this->hasMany(Notification::class);}
 public function profile(){return $this->hasOne(TechnicianProfile::class);} public function isManager():bool{return in_array($this->role,['gestionnaire','admin']);}
}
