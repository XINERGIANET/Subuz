<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    public function index(){
        return view('settings');
    }

    public function update(Request $request){
        $request->validate([
            'password' => 'required',
            'new_password' => 'required|min:5'
        ]);

        if(!Hash::check($request->password, auth()->user()->password)){
            return back()->withErrors([
                'password' => 'La contraseña actual no coincide'
            ]);
        }

        auth()->user()->update([
            'password' => Hash::make($request->new_password)
        ]);

        session()->flash('message', 'Ajustes guardados');

        return redirect()->route('settings.index');
    }
}
