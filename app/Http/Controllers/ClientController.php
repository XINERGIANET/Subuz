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
            return $query->where(function($q) use($search){
                $q->where('name', 'like', '%'.$search.'%')
                ->orWhere('business_name', 'like', '%'.$search.'%')
                ->orWhere('document', 'like', '%'.$search.'%');
            });
        })
        ->when($request->type, function($query, $type){
            return $query->where('type', $type);
        })
        ->latest('id')
        ->paginate(10);
        return view('clients.index', compact('clients'));
    }

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'address' => 'required',
            'district' => 'required',
            'type' => 'required|in:Contado,Credito'
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
            'name' => 'required|unique:clients',
            'type' => 'required|in:Contado,Credito'
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
            'district' => 'required',
            'type' => 'required|in:Contado,Credito'
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
        try {
            $client->delete();

            return response()->json([
                'status' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => 'No se pudo eliminar el cliente'
            ]);
        }
    }

    public function api(Request $request){
        $q = $request->q ?? '';
        $query = Client::query();

        if ($q !== '') {
            $query->where(function($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('business_name', 'like', "%{$q}%")
                    ->orWhere('document', 'like', "%{$q}%");
            });
        }

        $clients = $query->select('id', 'name', 'business_name', 'document', 'type')
            ->limit(30)
            ->get();
            
        return response()->json([
            'items' => $clients
        ]);
    }
}
