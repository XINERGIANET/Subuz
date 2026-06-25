<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(Request $request){
        $products = Product::where('is_combo', false)->when($request->search, function($query, $search){
            return $query->where('name', 'like', '%'.$search.'%');
        })->paginate(5, ['*'], 'products_page');

        $combos = Product::where('is_combo', true)->when($request->search, function($query, $search){
            return $query->where('name', 'like', '%'.$search.'%');
        })->paginate(5, ['*'], 'combos_page');
        $all_products = Product::where('is_combo', false)->get();
        return view('products.index', compact('products', 'combos', 'all_products'));
    }

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'price' => 'required_if:is_combo,false|nullable|numeric',
            'stock' => 'nullable|integer',
            'reduces_stock' => 'nullable',
            'combo_products' => 'required_if:is_combo,true|array'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $data = $request->all();
        $data['reduces_stock'] = $request->has('reduces_stock');
        $data['is_loanable'] = $request->has('is_loanable');
        $data['is_combo'] = $request->has('is_combo') && $request->is_combo == '1';
        $data['price'] = $data['is_combo'] ? 0 : $data['price'];
        
        if ($data['is_combo']) {
            $combo_items = [];
            if ($request->has('combo_products')) {
                foreach ($request->combo_products as $cp_id) {
                    $combo_items[] = ['id' => $cp_id, 'quantity' => 1];
                }
            }
            $data['combo_products'] = $combo_items;
            $data['stock'] = 0;
            $data['reduces_stock'] = false;
            $data['is_loanable'] = false;
        } else {
            $data['combo_products'] = null;
        }
        
        if ($request->has('stock') && $request->stock !== null) {
            $data['initial_stock'] = $request->stock;
            $data['stock_updated_at'] = now();
        }
        
        Product::create($data);

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
            'price' => 'required_if:is_combo,false|nullable|numeric',
            'stock' => 'nullable|integer',
            'reduces_stock' => 'nullable',
            'combo_products' => 'required_if:is_combo,true|array'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $data = $request->all();
        $data['reduces_stock'] = $request->has('reduces_stock');
        $data['is_loanable'] = $request->has('is_loanable');
        $data['is_combo'] = $request->has('is_combo') && $request->is_combo == '1';
        $data['price'] = $data['is_combo'] ? 0 : $data['price'];
        
        if ($data['is_combo']) {
            $combo_items = [];
            if ($request->has('combo_products')) {
                foreach ($request->combo_products as $cp_id) {
                    $combo_items[] = ['id' => $cp_id, 'quantity' => 1];
                }
            }
            $data['combo_products'] = $combo_items;
            $data['stock'] = 0;
            $data['reduces_stock'] = false;
            $data['is_loanable'] = false;
        } else {
            $data['combo_products'] = null;
        }
        
        if ($request->has('stock') && $request->stock != $product->stock) {
            $data['initial_stock'] = $request->stock;
            $data['stock_updated_at'] = now();
        }

        $product->update($data);

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
