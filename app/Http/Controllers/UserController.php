<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    public function indexDispatchers(){
        $dispatchers = User::where('role', 'despachador')->latest('id')->paginate(10);
        return view('users.dispatchers.index', compact('dispatchers'));
    }

    public function createDispatcher(){
        return view('users.dispatchers.create');
    }

    public function storeDispatcher(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'user' => 'required|string|max:255|unique:users,user',
            'password' => 'required|string|min:5|confirmed'
        ]);

        User::create([
            'name' => $request->name,
            'user' => $request->user,
            'password' => Hash::make($request->password),
            'role' => 'despachador'
        ]);

        return redirect()->route('users.dispatchers.index')->with('message', 'Usuario despachador creado');
    }

    public function editDispatcher(User $dispatcher){
        if($dispatcher->role !== 'despachador'){
            abort(404);
        }
        return view('users.dispatchers.edit', compact('dispatcher'));
    }

    public function updateDispatcher(Request $request, User $dispatcher){
        if($dispatcher->role !== 'despachador'){
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'user' => 'required|string|max:255|unique:users,user,'.$dispatcher->id,
            'password' => 'nullable|string|min:5|confirmed'
        ]);

        $data = [
            'name' => $request->name,
            'user' => $request->user
        ];

        if($request->password){
            $data['password'] = Hash::make($request->password);
        }

        $dispatcher->update($data);

        return redirect()->route('users.dispatchers.index')->with('message', 'Usuario actualizado');
    }

    public function destroyDispatcher(User $dispatcher){
        if($dispatcher->role !== 'despachador'){
            abort(404);
        }

        $dispatcher->delete();

        return redirect()->route('users.dispatchers.index')->with('message', 'Usuario eliminado');
    }
}
