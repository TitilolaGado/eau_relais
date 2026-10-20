<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
class AuthController {
 public function form(){return Inertia::render('Login');}
 public function login(Request $r){$data=$r->validate(['email'=>'required|email','password'=>'required|string']);if(!Auth::attempt($data))throw ValidationException::withMessages(['email'=>'Identifiants incorrects.']);$r->session()->regenerate();return redirect()->intended('/tableau-de-bord');}
 public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect('/');}
}
