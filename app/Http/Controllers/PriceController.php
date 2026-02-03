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
        $prices = Price::paginate(10);
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
        $price = Price::find($id)->with(['client', 'product'])->first();
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
}
