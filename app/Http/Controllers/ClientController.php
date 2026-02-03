<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Client;

class ClientController extends Controller
{
    public function index(Request $request){
        $clients = Client::when($request->search, function($query, $search){
            return $query->where('name', 'like', '%'.$search.'%')
                ->orWhere('business_name', 'like', '%'.$search.'%');
        })->paginate(10);
        return view('clients.index', compact('clients'));
    }

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'address' => 'required',
            'district' => 'required'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        Client::create($request->all());

        return response()->json([
            'status' => true
        ]);
    }

    public function storeInSale(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:clients'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        Client::create($request->all());

        return response()->json([
            'status' => true
        ]);
    }

    public function edit(Request $request, Client $client){
        return response()->json($client);
    }

    public function update(Request $request, Client $client){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'address' => 'required',
            'district' => 'required'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $client->update($request->all());

        return response()->json([
            'status' => true
        ]);
    }

    public function destroy(Request $request, Client $client){
        $client->delete();

        return response()->json([
            'status' => true
        ]);
    }

    public function api(Request $request){
        $clients = Client::where('name', 'like', "%{$request->q}%")
            ->orWhere('business_name', 'like', "%{$request->q}%")
            ->orWhere('document', 'like', "%{$request->q}%")
            ->get();
            
        return response()->json([
            'items' => $clients
        ]);
    }
}
