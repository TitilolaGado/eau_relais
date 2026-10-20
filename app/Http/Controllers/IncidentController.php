<?php
namespace App\Http\Controllers;
use App\Models\{Incident,Commune,Locality,WaterPoint,User,StatusHistory,Attachment,Notification,Intervention};
use App\Services\Workflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Gate,Hash};
use Illuminate\Support\Str;
use Inertia\Inertia;
class IncidentController {
 public function create(){return Inertia::render('Report',['localities'=>Locality::with('commune')->get(),'points'=>WaterPoint::all(['id','name','locality_id'])]);}
 public function store(Request $r){
 $d=$r->validate(['client_uuid'=>'required|uuid','tracking_secret'=>'required|string|min:32|max:128','locality_id'=>'required|exists:localities,id','water_point_id'=>'nullable|exists:water_points,id','reporter_name'=>'required|string|max:100','phone'=>['required','regex:/^\+?[0-9 ()-]{8,24}$/'],'relationship'=>'required|in:usager,ecole,relais,autre','problem'=>'required|in:absence,debit,pompe,fuite,equipement,aspect,autre','description'=>'required|string|min:10|max:3000','landmark'=>'nullable|string|max:500','observed_at'=>'required|date|before_or_equal:today','latitude'=>'nullable|numeric|between:-90,90|required_with:longitude','longitude'=>'nullable|numeric|between:-180,180|required_with:latitude','gps_consent'=>'exclude_without:latitude,longitude|required|accepted','photo'=>'nullable|file|mimes:jpg,jpeg,png,webp|max:3072']);
 if(!empty($d['water_point_id']))abort_unless(WaterPoint::findOrFail($d['water_point_id'])->locality_id==(int)$d['locality_id'],422,'Ce point d’eau ne correspond pas à la localité.');
 // Une répétition ne divulgue pas les données du signalement sans son secret.
 if($existing=Incident::where('client_uuid',$d['client_uuid'])->first()){
 abort_unless(Hash::check($d['tracking_secret'],$existing->tracking_hash),403);
 return response()->json(['reference'=>$existing->reference,'received'=>true]);
 }
 $secret=$d['tracking_secret'];unset($d['tracking_secret'],$d['gps_consent'],$d['photo']);
 try { $incident=DB::transaction(function()use($r,$d,$secret){
 $incident=Incident::create(array_merge($d,['reference'=>'EAU-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),'tracking_hash'=>Hash::make($secret),'created_by'=>$r->user()?->id,'reporter_user_id'=>$r->user()?->role==='usager'?$r->user()->id:null]));
 $incident->histories()->create(['to_status'=>'signale','actor_id'=>$r->user()?->id,'comment'=>'Signalement reçu.']);
 if($r->hasFile('photo'))$incident->attachments()->create(['path'=>$r->file('photo')->store('incidents'),'mime'=>$r->file('photo')->getMimeType()]);
 $users=User::where('role','admin')->orWhere(fn($q)=>$q->where('role','gestionnaire')->where('commune_id',$incident->locality->commune_id))->get();
 foreach($users as $u)Notification::create(['user_id'=>$u->id,'title'=>'Nouveau signalement '.$incident->reference,'url'=>'/incidents/'.$incident->id]);
 return $incident;
 });
 } catch(\Illuminate\Database\QueryException $e) { $existing=Incident::where('client_uuid',$d['client_uuid'])->first();if(!$existing)throw $e;abort_unless(Hash::check($secret,$existing->tracking_hash),403);return response()->json(['reference'=>$existing->reference,'received'=>true]); }
 $possible= !empty($d['water_point_id'])?Incident::where('water_point_id',$d['water_point_id'])->where('id','!=',$incident->id)->whereNotIn('status',['confirme','non_retenu','doublon'])->count():0;
 return response()->json(['reference'=>$incident->reference,'received'=>true,'possible_duplicate'=>$possible>0],201);
 }
 public static function scope(Request $r){
 $q=Incident::query();$u=$r->user();
 if($u->role==='technicien')$q->whereHas('interventions',fn($x)=>$x->where('technician_id',$u->id));
 elseif(in_array($u->role,['gestionnaire','relais']))$q->whereHas('locality',fn($x)=>$x->where('commune_id',$u->commune_id));
 elseif($u->role!=='admin')$q->where(fn($x)=>$x->where('reporter_user_id',$u->id)->orWhere('created_by',$u->id));
 return $q;
 }
 public function index(Request $r){$q=self::scope($r)->with('waterPoint','locality.commune');if($r->filled('status'))$q->where('status',$r->status);if($r->filled('q'))$q->where(fn($x)=>$x->where('reference','like','%'.$r->q.'%')->orWhere('description','like','%'.$r->q.'%'));return Inertia::render('Incidents',['incidents'=>$q->latest()->paginate(15)->withQueryString(),'filters'=>$r->only('status','q')]);}
 public function show(Request $r,Incident $incident){Gate::authorize('view',$incident);$incident->load('waterPoint','locality.commune','histories.actor','interventions.technician','interventions.parts','attachments');return Inertia::render('Incident',['incident'=>$incident->makeVisible(['phone','reporter_name']),'technicians'=>User::where('role','technicien')->whereHas('profile',fn($q)=>$q->where('referenced',true)->whereHas('zones',fn($z)=>$z->where('communes.id',$incident->locality->commune_id)))->get(['id','name']),'nearbyPoints'=>WaterPoint::where('locality_id',$incident->locality_id)->get(['id','name']),'canManage'=>Gate::allows('update',$incident),'canConfirm'=>Gate::allows('confirm',$incident)]);}
 public function update(Request $r,Incident $incident,Workflow $flow){
 Gate::authorize('update',$incident);$d=$r->validate(['status'=>'nullable|in:verification,planifie,repare,confirme,non_retenu,doublon','comment'=>'required|string|min:5|max:2000','priority'=>'nullable|in:normale,haute,urgente','priority_reason'=>'required_with:priority|nullable|string|max:500','blocker'=>'nullable|string|max:500','duplicate_of'=>'nullable|exists:incidents,id','water_point_id'=>'nullable|exists:water_points,id']);
 DB::transaction(function()use($incident,$d,$r,$flow){$locked=Incident::lockForUpdate()->findOrFail($incident->id);
 if(($d['status']??null)==='doublon') { $parent=Incident::find($d['duplicate_of']??0);abort_unless($parent && $parent->id!==$locked->id && $parent->locality_id===$locked->locality_id && $parent->status!=='doublon',422,'Choisissez un incident principal valide de la même localité.'); }
 if(!empty($d['water_point_id']))abort_unless(WaterPoint::findOrFail($d['water_point_id'])->locality_id===$locked->locality_id,422,'Le point d’eau doit appartenir à la même localité.');
 if(($d['status']??null)==='confirme')abort(422,'Utilisez la confirmation de remise en service.');
 $locked->update(collect($d)->only('priority','priority_reason','blocker','duplicate_of','water_point_id')->all());
 if(!empty($d['status']))$flow->transition($locked,$d['status'],$d['comment'],$r->user());
 else $locked->histories()->create(['actor_id'=>$r->user()->id,'from_status'=>$locked->status,'to_status'=>$locked->status,'comment'=>$d['comment']]);
 });return back()->with('message','Suivi mis à jour.');
 }
 public function confirm(Request $r,Incident $incident,Workflow $flow){Gate::authorize('confirm',$incident);$d=$r->validate(['working'=>'required|boolean','comment'=>'required|string|min:5|max:2000']);abort_unless($incident->status==='repare',422);DB::transaction(function()use($incident,$d,$flow,$r){$i=Incident::lockForUpdate()->findOrFail($incident->id);abort_unless($i->status==='repare',422);$flow->transition($i,$d['working']?'confirme':'verification',$d['comment'],$r->user());});return back()->with('message','Votre retour a été enregistré.');}
 public function trackForm(){return Inertia::render('Track');}
 private function tracked(Request $r):Incident{$r->validate(['reference'=>'required|string|max:40','secret'=>'required|string|max:128']);$i=Incident::where('reference',$r->reference)->first();abort_unless($i && Hash::check($r->secret,$i->tracking_hash),404,'Référence ou code de suivi incorrect.');return $i;}
 public function track(Request $r){$i=$this->tracked($r);return response()->json(['reference'=>$i->reference,'status'=>$i->status,'point'=>$i->waterPoint?->name??'Point non répertorié','created_at'=>$i->created_at,'history'=>$i->histories()->get(['to_status','created_at'])]);}
 public function trackAction(Request $r,Workflow $flow){$i=$this->tracked($r);$d=$r->validate(['comment'=>'required|string|min:5|max:2000','working'=>'nullable|boolean']);DB::transaction(function()use($i,$d,$r,$flow){$locked=Incident::lockForUpdate()->findOrFail($i->id);if($r->has('working')){abort_unless($locked->status==='repare',422);$flow->transition($locked,$d['working']?'confirme':'verification','Retour du déclarant : '.$d['comment'],null);}else $locked->histories()->create(['from_status'=>$locked->status,'to_status'=>$locked->status,'comment'=>'Précision du déclarant : '.$d['comment']]);});return response()->json(['saved'=>true]);}
 public function photo(Request $r,Attachment $attachment){Gate::authorize('view',Incident::findOrFail($attachment->incident_id));return response()->file(storage_path('app/private/'.$attachment->path),['Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);}
}
