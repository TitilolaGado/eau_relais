<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Commune extends Model { protected $guarded=[]; public function localities(){return $this->hasMany(Locality::class);} }
