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

            $exists = false;
            $itemKey = null;

            foreach($cart['items'] as $key => $item){
                if($item['id'] == $request->id){
                    $exists = true;
                    $itemKey = $key;
                }
            }

            if($exists){
                $cart['items'][$itemKey]['quantity']++;
                $cart['items'][$itemKey]['amount'] = number_format($cart['items'][$itemKey]['price'] * $cart['items'][$itemKey]['quantity'], 2, '.', '');
            }else{
                $cart['items'][] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'quantity' => 1,
                    'amount' => $product->price,
                    'special' => false
                ];
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
            if($item['id'] == $request->id){
                $exists = true;
                $itemKey = $key;
            }
        }

        if($exists){
            $cart['items'][$itemKey]['price'] = number_format($request->price, 2, '.', '');
            $cart['items'][$itemKey]['quantity'] = intval($request->quantity);
            $cart['items'][$itemKey]['amount'] = number_format($request->price * $request->quantity, 2);
            $cart['items'][$itemKey]['special'] = $request->special == 'true' ? true : false;
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
            if($item['id'] == $request->id){
                $exists = true;
                $itemKey = $key;
            }
        }

        if($exists){
            array_splice($cart['items'], $itemKey, 1);
        }

        session()->put('cart', $cart);
        $this->summary();

        return response()->json(['status' => true]);
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
}
