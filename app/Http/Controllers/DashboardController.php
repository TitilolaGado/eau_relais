<?php
namespace App\Http\Controllers;
use App\Models\{Incident,Commune,Locality,Maintenance,WaterPoint};
use Illuminate\Http\Request;
use Inertia\Inertia;
class DashboardController {
 public function index(Request $r){
 $r->validate(['from'=>'nullable|date','to'=>'nullable|date|after_or_equal:from','commune_id'=>'nullable|exists:communes,id','locality_id'=>'nullable|exists:localities,id']);
 $q=IncidentController::scope($r);
 if($r->filled('from'))$q->whereDate('created_at','>=',$r->from);if($r->filled('to'))$q->whereDate('created_at','<=',$r->to);
 if($r->filled('commune_id'))$q->whereHas('locality',fn($x)=>$x->where('commune_id',$r->commune_id));if($r->filled('locality_id'))$q->where('locality_id',$r->locality_id);
 $all=$q->with('waterPoint','locality.commune','interventions')->get();
 if($r->boolean('export'))return response()->streamDownload(function()use($all){$safe=fn($v)=>preg_match('/^[=+@\-\t\r]/u',(string)$v)?"'".$v:$v;$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,['Référence','Point d’eau','Localité','Statut','Date'],';');foreach($all as $i)fputcsv($out,array_map($safe,[$i->reference,$i->waterPoint?->name,$i->locality->name,$i->status,$i->created_at]),';');fclose($out);},'eaurelais-incidents.csv');
 $closed=$all->where('status','confirme')->filter(fn($i)=>$i->confirmed_at);$responded=$all->filter(fn($i)=>$i->first_response_at);
 $hours=fn($set,$field)=>$set->count()?round($set->avg(fn($i)=>$i->created_at->diffInSeconds($i->$field)/3600),1):null;
 $points=WaterPoint::query();$maintenance=Maintenance::with('waterPoint.locality','responsible')->whereNull('completed_at');
 if($r->user()->role!=='admin'){$points->whereHas('locality',fn($x)=>$x->where('commune_id',$r->user()->commune_id));$maintenance->whereHas('waterPoint.locality',fn($x)=>$x->where('commune_id',$r->user()->commune_id));}
 if($r->filled('commune_id')){$points->whereHas('locality',fn($x)=>$x->where('commune_id',$r->commune_id));$maintenance->whereHas('waterPoint.locality',fn($x)=>$x->where('commune_id',$r->commune_id));}
 if($r->filled('locality_id')){$points->where('locality_id',$r->locality_id);$maintenance->whereHas('waterPoint',fn($x)=>$x->where('locality_id',$r->locality_id));}
 if($r->user()->role==='technicien')$maintenance->where('responsible_id',$r->user()->id);
 $recurrent=$all->whereNotNull('water_point_id')->where('status','!=','doublon')->groupBy('water_point_id')->filter(fn($g)=>$g->count()>1)->map(fn($g)=>['name'=>$g->first()->waterPoint?->name,'count'=>$g->count()])->values();
 return Inertia::render('Dashboard',['stats'=>['open'=>$all->whereNotIn('status',['confirme','non_retenu','doublon'])->count(),'confirmed'=>$closed->count(),'response_hours'=>$hours($responded,'first_response_at'),'response_count'=>$responded->count(),'resolution_hours'=>$hours($closed,'confirmed_at'),'resolution_count'=>$closed->count(),'out_of_service'=>$points->where('state','hors_service')->count(),'blocked'=>$all->filter(fn($i)=>$i->blocker || $i->interventions->contains(fn($v)=>(bool)$v->blocker))->count(),'by_status'=>$all->countBy('status')],'recent'=>$all->sortByDesc('created_at')->take(6)->values(),'maintenances'=>$maintenance->orderBy('scheduled_at')->limit(6)->get(),'recurrent'=>$recurrent,'communes'=>Commune::all(),'localities'=>Locality::all(),'filters'=>$r->only('from','to','commune_id','locality_id')]);
 }
}
