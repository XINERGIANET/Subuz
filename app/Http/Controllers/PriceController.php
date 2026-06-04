<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Price;
use App\Models\Product;

class PriceController extends Controller
{
    public function index(Request $request){
        $search = $request->search;

        $prices = Price::when($search, function($query) use ($search) {
            $query->whereHas('client', function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('document', 'like', '%' . $search . '%');
            })->orWhereHas('product', function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        })->paginate(10);

        $products = Product::all();
        return view('prices.index', compact('prices', 'products'));
    }

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'client_id' => 'required',
            'product_id' => 'required',
            'price' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        Price::create($request->all());

        return response()->json([
            'status' => true
        ]);
    }

    public function edit(Request $request, $id){
        $price = Price::with(['client', 'product'])->find($id);
        return response()->json($price);
    }

    public function update(Request $request, Price $price){
        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $price->update($request->all());

        return response()->json([
            'status' => true
        ]);
    }

    public function destroy(Request $request, Price $price){
        $price->delete();

        return response()->json([
            'status' => true
        ]);
    }
    public function getSpecialPrices($client_id){
        $prices = Price::where('client_id', $client_id)->get();
        return response()->json($prices);
    }
}
