<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $stocks = \App\Models\Stock::when($request->search, function($query, $search){
            return $query->where('name', 'like', '%'.$search.'%');
        })->paginate(10);
        return view('stocks.index', compact('stocks'));
    }

    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required',
            'literage' => 'nullable',
            'location' => 'nullable',
            'status' => 'required',
            'stock' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        \App\Models\Stock::create($request->all());

        return response()->json([
            'status' => true
        ]);
    }

    public function edit(\App\Models\Stock $stock)
    {
        return response()->json($stock);
    }

    public function update(Request $request, \App\Models\Stock $stock)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required',
            'literage' => 'nullable',
            'location' => 'nullable',
            'status' => 'required',
            'stock' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $stock->update($request->all());

        return response()->json([
            'status' => true
        ]);
    }

    public function destroy(\App\Models\Stock $stock)
    {
        $stock->delete();
        return response()->json([
            'status' => true
        ]);
    }
}
