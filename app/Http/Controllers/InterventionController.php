<?php
namespace App\Http\Controllers;
use App\Models\{Incident,Intervention,User,Notification,TechnicianProfile};
use App\Services\Workflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Gate,DB};
class InterventionController {
 public function store(Request $r,Incident $incident,Workflow $flow){Gate::authorize('update',$incident);$d=$r->validate(['technician_id'=>'required|exists:users,id','scheduled_at'=>'required|date|after_or_equal:today','estimated_cost'=>'nullable|integer|min:0|max:2000000000']);
 $p=TechnicianProfile::where('user_id',$d['technician_id'])->where('referenced',true)->where('availability','disponible')->whereHas('zones',fn($q)=>$q->where('communes.id',$incident->locality->commune_id))->first();abort_unless($p && $p->user->role==='technicien',422,'Technicien non disponible ou hors zone.');
 DB::transaction(function()use($incident,$d,$flow,$r){$i=Incident::lockForUpdate()->findOrFail($incident->id);abort_unless(in_array($i->status,['verification','planifie']),422,'Vérifiez le signalement avant de planifier.');$i->interventions()->create($d);if($i->status==='verification')$flow->transition($i,'planifie','Intervention planifiée.',$r->user());Notification::create(['user_id'=>$d['technician_id'],'title'=>'Nouvelle affectation : '.$i->reference,'url'=>'/incidents/'.$i->id]);});return back()->with('message','Intervention planifiée.');}
 public function update(Request $r,Intervention $intervention,Workflow $flow){$i=$intervention->incident;Gate::authorize('view',$i);abort_unless($r->user()->isManager() || ($r->user()->role==='technicien' && $intervention->technician_id===$r->user()->id),403);
 $d=$r->validate(['acceptance'=>'required|in:acceptee,indisponible','diagnosis'=>'nullable|string|max:3000','work_done'=>'nullable|string|max:3000','performed_at'=>'nullable|date|before_or_equal:today','blocker'=>'nullable|string|max:1000','estimated_cost'=>'nullable|integer|min:0|max:2000000000','actual_cost'=>'nullable|integer|min:0|max:2000000000','recommendation'=>'nullable|string|max:2000','parts'=>'nullable|array|max:30','parts.*.name'=>'required|string|max:150','parts.*.quantity'=>'required|integer|min:1|max:10000','parts.*.state'=>'required|in:necessaire,utilisee','repaired'=>'required|boolean','photo'=>'nullable|file|mimes:jpg,jpeg,png,webp|max:3072']);
 if($d['repaired']){abort_unless($d['acceptance']==='acceptee' && !empty($d['performed_at']) && !empty($d['work_done']) && !empty($d['diagnosis']),422,'Diagnostic, travaux et date sont requis.');}
 DB::transaction(function()use($intervention,$i,$d,$r,$flow){$locked=Incident::lockForUpdate()->findOrFail($i->id);abort_unless($locked->status==='planifie',422,'Cet incident n’est plus au stade intervention.');$intervention->update(collect($d)->except('parts','repaired','photo')->all());$intervention->parts()->delete();foreach($d['parts']??[] as $part)$intervention->parts()->create($part);
 if($r->hasFile('photo'))$locked->attachments()->create(['intervention_id'=>$intervention->id,'path'=>$r->file('photo')->store('interventions'),'mime'=>$r->file('photo')->getMimeType()]);
 if($d['repaired'])$flow->transition($locked,'repare','Réparation déclarée : '.$d['work_done'],$r->user());else $locked->histories()->create(['actor_id'=>$r->user()->id,'from_status'=>$locked->status,'to_status'=>$locked->status,'comment'=>'Compte rendu technique enregistré. '.($d['blocker']??'')]);
 });return back()->with('message','Compte rendu enregistré.');}
}
