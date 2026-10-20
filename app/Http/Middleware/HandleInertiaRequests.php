<?php
namespace App\Http\Middleware;
use Illuminate\Http\Request;
use Inertia\Middleware;
class HandleInertiaRequests extends Middleware {
 protected $rootView='app';
 public function share(Request $request):array {return array_merge(parent::share($request),[
 'auth'=>['user'=>$request->user()?->only('id','name','role','commune_id')],
 'flash'=>['message'=>fn()=>$request->session()->get('message')],
 'notifications'=>fn()=>$request->user()?->notifications()->latest()->limit(8)->get() ?? [],
 'demo'=>app()->environment('local','testing') && (bool)env('DEMO_PASSWORD'),
 ]);}
}
