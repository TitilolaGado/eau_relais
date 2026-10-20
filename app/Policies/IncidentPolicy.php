<?php
namespace App\Policies;
use App\Models\{User,Incident};
class IncidentPolicy {
 public function view(User $user,Incident $incident):bool {
 if($user->role==='admin')return true;
 if($user->role==='technicien')return $incident->interventions()->where('technician_id',$user->id)->exists();
 if(in_array($user->role,['gestionnaire','relais']))return $incident->locality->commune_id===$user->commune_id;
 return $incident->reporter_user_id===$user->id || $incident->created_by===$user->id;
 }
 public function update(User $user,Incident $incident):bool{return $user->isManager() && $this->view($user,$incident);}
 public function confirm(User $user,Incident $incident):bool{return $user->role!=='technicien' && $this->view($user,$incident);}
}
