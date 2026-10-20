<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Locality extends Model { protected $guarded=[]; public function commune(){return $this->belongsTo(Commune::class);} }
