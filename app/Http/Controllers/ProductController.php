<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Supply;

class ProductController extends Controller
{
    public function index(Request $request){
        $products = Product::with('supplies')->where('is_combo', false)->when($request->search, function($query, $search){
            return $query->where('name', 'like', '%'.$search.'%');
        })->paginate(5, ['*'], 'products_page');

        $combos = Product::where('is_combo', true)->when($request->search, function($query, $search){
            return $query->where('name', 'like', '%'.$search.'%');
        })->paginate(5, ['*'], 'combos_page');
        $all_products = Product::where('is_combo', false)->get();
        $supplies = Supply::orderBy('name')->get();
        $supplies_options = $supplies->map(function($supply) {
            return [
                'id' => $supply->id,
                'name' => $supply->name,
                'unit' => $supply->unit,
            ];
        })->values();
        $supplies_list = Supply::when($request->search, function($query, $search){
            return $query->where('name', 'like', '%'.$search.'%');
        })->orderBy('name')->paginate(5, ['*'], 'supplies_page');

        return view('products.index', compact('products', 'combos', 'all_products', 'supplies', 'supplies_options', 'supplies_list'));
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

        $data = $request->except(['supply_ids', 'supply_quantities']);
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
        
        $product = Product::create($data);

        $this->syncSupplies($product, $request);

        return response()->json([
            'status' => true
        ]);
    }

    public function edit(Request $request, Product $product){
        $product->load('supplies');

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

        $data = $request->except(['supply_ids', 'supply_quantities']);
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
        $this->syncSupplies($product, $request);

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

    private function syncSupplies(Product $product, Request $request)
    {
        if ($product->is_combo) {
            $product->supplies()->sync([]);
            return;
        }

        $supplyIds = $request->input('supply_ids', []);
        $quantities = $request->input('supply_quantities', []);
        $sync = [];

        foreach ($supplyIds as $index => $supplyId) {
            $quantity = isset($quantities[$index]) ? (float) $quantities[$index] : 0;

            if (!$supplyId || $quantity <= 0) {
                continue;
            }

            if (isset($sync[$supplyId])) {
                $sync[$supplyId]['quantity'] += $quantity;
            } else {
                $sync[$supplyId] = ['quantity' => $quantity];
            }
        }

        $product->supplies()->sync($sync);
    }

    public function purchaseHistory(Product $product)
    {
        $expenses = \App\Models\Expense::with('payment_method')
            ->where('description', 'like', "Compra de stock: {$product->name} (Producto)%")
            ->orderBy('date', 'desc')
            ->get()
            ->map(function($e) {
                preg_match('/Cant: ([\d\.]+)/', $e->description, $matches);
                $quantity = $matches[1] ?? '0';

                return [
                    'date' => $e->date->format('d/m/Y H:i'),
                    'real_date' => $e->real_date ? \Carbon\Carbon::parse($e->real_date)->format('d/m/Y') : '-',
                    'quantity' => $quantity,
                    'amount' => $e->amount,
                    'payment_method' => $e->payment_method ? $e->payment_method->name : '-',
                    'receipt_number' => $e->receipt_number ?: '-',
                    'operation_number' => $e->operation_number ?: '-'
                ];
            });

        return response()->json($expenses);
    }
}
