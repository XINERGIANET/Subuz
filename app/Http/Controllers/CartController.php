<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Product;

class CartController extends Controller
{

    public function index(){
        $cart = session()->get('cart') ? session()->get('cart') : [
            'items' => [],
            'subtotal' => '0.00',
            'igv' => '0.00',
            'total' => '0.00'
        ];

        return response()->json($cart);
    }

    public function store(Request $request){
        
        $cart = session()->get('cart') ? session()->get('cart') : [
            'items' => [],
            'subtotal' => '0.00',
            'igv' => '0.00',
            'total' => '0.00'
        ];

        $product = Product::find($request->id);

        if($product){
            if($product->is_combo && is_array($product->combo_products)){
                foreach($product->combo_products as $cp){
                    $comboProduct = Product::find($cp['id']);
                    if($comboProduct){
                        $this->addProductToCart($cart, $comboProduct, $request->client_id, $cp['quantity'] ?? 1);
                    }
                }
            } else {
                $this->addProductToCart($cart, $product, $request->client_id, 1);
            }

            session()->put('cart', $cart);
            $this->summary();
        }

        return response()->json(['status' => true]);
    }

    public function update(Request $request){

        $cart = session()->get('cart') ? session()->get('cart') : [
            'items' => [],
            'subtotal' => '0.00',
            'igv' => '0.00',
            'total' => '0.00'
        ];

        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric',
            'quantity' => 'required|integer'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $exists = false;
        $itemKey = null;

        foreach($cart['items'] as $key => $item){
            $is_loaned_req = ($request->is_loaned === 'true' || $request->is_loaned === '1' || $request->is_loaned === true);
            $is_loaned_item = isset($item['is_loaned']) ? $item['is_loaned'] : false;
            
            if($item['id'] == $request->id && $is_loaned_item == $is_loaned_req){
                $exists = true;
                $itemKey = $key;
                break;
            }
        }

        if($exists){
            $cart['items'][$itemKey]['quantity'] = intval($request->quantity);
            $cart['items'][$itemKey]['special'] = $request->special == 'true' ? true : false;
            
            if ($request->has('is_loaned')) {
                $is_loaned = $request->is_loaned === 'true' || $request->is_loaned === '1';
                $cart['items'][$itemKey]['is_loaned'] = $is_loaned;
                
                if ($is_loaned) {
                    $cart['items'][$itemKey]['price'] = '0.00';
                } else {
                    $cart['items'][$itemKey]['price'] = $cart['items'][$itemKey]['original_price'] ?? number_format($request->price, 2, '.', '');
                }
            } else {
                if (empty($cart['items'][$itemKey]['is_loaned'])) {
                    $cart['items'][$itemKey]['price'] = number_format($request->price, 2, '.', '');
                    $cart['items'][$itemKey]['original_price'] = number_format($request->price, 2, '.', '');
                }
            }
            
            $cart['items'][$itemKey]['amount'] = number_format($cart['items'][$itemKey]['price'] * $cart['items'][$itemKey]['quantity'], 2, '.', '');
        }

        session()->put('cart', $cart);
        $this->summary();

        return response()->json(['status' => true]);

    }

    public function destroy(Request $request){

        $cart = session()->get('cart') ? session()->get('cart') : [
            'items' => [],
            'subtotal' => '0.00',
            'igv' => '0.00',
            'total' => '0.00'
        ];
        
        $exists = false;
        $itemKey = null;

        foreach($cart['items'] as $key => $item){
            $is_loaned_req = ($request->is_loaned === 'true' || $request->is_loaned === '1' || $request->is_loaned === true);
            $is_loaned_item = isset($item['is_loaned']) ? $item['is_loaned'] : false;
            
            if($item['id'] == $request->id && $is_loaned_item == $is_loaned_req){
                $exists = true;
                $itemKey = $key;
                break;
            }
        }

        if($exists){
            array_splice($cart['items'], $itemKey, 1);
        }

        session()->put('cart', $cart);
        $this->summary();

        return response()->json(['status' => true]);
    }

    public function split(Request $request) {
        $cart = session()->get('cart') ? session()->get('cart') : [
            'items' => [], 'subtotal' => '0.00', 'igv' => '0.00', 'total' => '0.00'
        ];
        
        $product = Product::find($request->id);
        if(!$product) return response()->json(['status' => false]);
        
        $price_applied = $product->price;
        $is_special = false;
        if($request->client_id){
            $special_price = \App\Models\Price::where('client_id', $request->client_id)
                ->where('product_id', $product->id)
                ->first();
            if($special_price){
                $price_applied = $special_price->price;
                $is_special = true;
            }
        }
        
        $newItems = [];
        foreach($cart['items'] as $item){
            if($item['id'] != $request->id){
                $newItems[] = $item;
            } else {
                if(isset($item['original_price'])) {
                    $price_applied = $item['original_price'];
                }
            }
        }
        $cart['items'] = $newItems;
        
        if ($request->sell_qty > 0) {
            $cart['items'][] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => number_format($price_applied, 2, '.', ''),
                'original_price' => number_format($price_applied, 2, '.', ''),
                'quantity' => intval($request->sell_qty),
                'amount' => number_format($price_applied * $request->sell_qty, 2, '.', ''),
                'special' => $is_special,
                'is_loanable' => $product->is_loanable,
                'is_loaned' => false
            ];
        }
        
        if ($request->loan_qty > 0) {
            $cart['items'][] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => '0.00',
                'original_price' => number_format($price_applied, 2, '.', ''),
                'quantity' => intval($request->loan_qty),
                'amount' => '0.00',
                'special' => $is_special,
                'is_loanable' => $product->is_loanable,
                'is_loaned' => true
            ];
        }
        
        session()->put('cart', $cart);
        $this->summary();
        
        return response()->json(['status' => true]);
    }

    private function addProductToCart(&$cart, $product, $client_id, $quantityToAdd = 1){
        $price_applied = $product->price;
        $is_special = false;
        if($client_id){
            $special_price = \App\Models\Price::where('client_id', $client_id)
                ->where('product_id', $product->id)
                ->first();
            if($special_price){
                $price_applied = $special_price->price;
                $is_special = true;
            }
        }

        $exists = false;
        $itemKey = null;

        foreach($cart['items'] as $key => $item){
            if($item['id'] == $product->id && empty($item['is_loaned'])){
                $exists = true;
                $itemKey = $key;
                break;
            }
        }

        if($exists){
            $cart['items'][$itemKey]['quantity'] += $quantityToAdd;
            $cart['items'][$itemKey]['price'] = number_format($price_applied, 2, '.', ''); // Update price in case it changed
            $cart['items'][$itemKey]['amount'] = number_format($cart['items'][$itemKey]['price'] * $cart['items'][$itemKey]['quantity'], 2, '.', '');
            $cart['items'][$itemKey]['special'] = $is_special;
            $cart['items'][$itemKey]['is_loanable'] = $product->is_loanable;
        }else{
            $cart['items'][] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => number_format($price_applied, 2, '.', ''),
                'original_price' => number_format($price_applied, 2, '.', ''),
                'quantity' => $quantityToAdd,
                'amount' => number_format($price_applied * $quantityToAdd, 2, '.', ''),
                'special' => $is_special,
                'is_loanable' => $product->is_loanable,
                'is_loaned' => false
            ];
        }
    }

    public function summary(){
        $cart = session()->get('cart') ? session()->get('cart') : [
            'items' => [],
            'subtotal' => '0.00',
            'igv' => '0.00',
            'total' => '0.00'
        ];

        $subtotal = 0;
        $igv = 0;
        $total = 0;

        foreach($cart['items'] as $key => $item){
            $total += floatval($item['price']) * intval($item['quantity']);
        }

        $subtotal = $total/1.18;
        $igv = $total - $subtotal;
        
        $cart['total'] = number_format($total, 2);
        $cart['subtotal'] = number_format($subtotal, 2);
        $cart['igv'] = number_format($igv, 2);

        session()->put('cart', $cart);
    }

    public function updatePricesByClient(Request $request){
        $cart = session()->get('cart') ? session()->get('cart') : [
            'items' => [],
            'subtotal' => '0.00',
            'igv' => '0.00',
            'total' => '0.00'
        ];

        foreach($cart['items'] as $key => $item){
            $product = \App\Models\Product::find($item['id']);
            $price_applied = $product->price;
            $is_special = false;

            if($request->client_id){
                $special_price = \App\Models\Price::where('client_id', $request->client_id)
                    ->where('product_id', $item['id'])
                    ->first();
                if($special_price){
                    $price_applied = $special_price->price;
                    $is_special = true;
                }
            }

            $cart['items'][$key]['price'] = number_format($price_applied, 2, '.', '');
            $cart['items'][$key]['original_price'] = number_format($price_applied, 2, '.', '');
            $cart['items'][$key]['amount'] = number_format($price_applied * $item['quantity'], 2, '.', '');
            $cart['items'][$key]['special'] = $is_special;
            
            if (isset($item['is_loaned']) && $item['is_loaned']) {
                $cart['items'][$key]['price'] = '0.00';
                $cart['items'][$key]['amount'] = '0.00';
            }
        }

        session()->put('cart', $cart);
        $this->summary();

        return response()->json(['status' => true]);
    }
}
