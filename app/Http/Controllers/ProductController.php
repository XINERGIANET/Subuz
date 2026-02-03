<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(Request $request){
        $products = Product::when($request->search, function($query, $search){
            return $query->where('name', 'like', '%'.$search.'%');
        })->paginate(5);
        return view('products.index', compact('products'));
    }

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'price' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        Product::create($request->all());

        return response()->json([
            'status' => true
        ]);
    }

    public function edit(Request $request, Product $product){
        return response()->json($product);
    }

    public function update(Request $request, Product $product){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'price' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $product->update($request->all());

        return response()->json([
            'status' => true
        ]);
    }

    public function destroy(Request $request, Product $product){
        $product->delete();

        return response()->json([
            'status' => true
        ]);
    }

    public function api(Request $request){
        $products = Product::where('name', 'like', "%{$request->q}%")->get();
            
        return response()->json([
            'items' => $products
        ]);
    }
}
