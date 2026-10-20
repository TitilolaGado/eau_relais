<?php
namespace App\Services;
use App\Models\{Incident,StatusHistory,User,Notification,AuditLog};
use Illuminate\Validation\ValidationException;
class Workflow {
 const TRANSITIONS=['signale'=>['verification','non_retenu','doublon'],'verification'=>['planifie','non_retenu','doublon'],'planifie'=>['repare','verification'],'repare'=>['confirme','verification'],'confirme'=>['verification'],'non_retenu'=>['verification'],'doublon'=>['verification']];
 public function transition(Incident $incident,string $next,string $comment,?User $actor):void {
 if(!in_array($next,self::TRANSITIONS[$incident->status]??[]))throw ValidationException::withMessages(['status'=>'Ce changement de statut n’est pas autorisé.']);
 if($next==='planifie' && !$incident->interventions()->exists())throw ValidationException::withMessages(['status'=>'Planifiez une intervention avant de changer ce statut.']);
 if($next==='repare' && !$incident->interventions()->whereNotNull('performed_at')->whereNotNull('work_done')->exists())throw ValidationException::withMessages(['status'=>'Un compte rendu de réparation est nécessaire.']);
 $previous=$incident->status;
 $incident->update(['status'=>$next,'first_response_at'=>$incident->first_response_at??now(),'confirmed_at'=>$next==='confirme'?now():null]);
 StatusHistory::create(['incident_id'=>$incident->id,'actor_id'=>$actor?->id,'from_status'=>$previous,'to_status'=>$next,'comment'=>$comment]);
 AuditLog::create(['user_id'=>$actor?->id,'action'=>'statut:'.$next,'subject'=>'incident:'.$incident->id]);
 // La confirmation du fonctionnement n'est pas un résultat d'analyse sanitaire.
 if($next==='confirme')$incident->waterPoint?->update(['state'=>'fonctionnel','state_updated_at'=>now()]);
 if($next==='verification' && in_array($previous,['confirme','repare']))$incident->waterPoint?->update(['state'=>'inconnu','state_updated_at'=>now()]);
 $ids=User::where('role','admin')->orWhere(fn($q)=>$q->whereIn('role',['gestionnaire','relais'])->where('commune_id',$incident->locality->commune_id))->pluck('id');
 if($incident->reporter_user_id)$ids->push($incident->reporter_user_id);
 foreach($ids->unique() as $id)Notification::create(['user_id'=>$id,'title'=>$incident->reference.' : '.$next,'url'=>'/incidents/'.$incident->id]);
 }
}
