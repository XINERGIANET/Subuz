<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supply;
use App\Models\FixedAsset;
use App\Models\Product;
use App\Models\InventoryMovement;
use App\Models\ExpenseCategory;
use App\Models\ExpenseSubcategory;
use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\Cashbox;
use App\Models\CashboxMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        // 1. INSUMOS (Bidones, tapas, sellos, etiquetas, bolsas 3kg, 5kg, 2kg, etc.)
        $supplies = Supply::all()->map(function ($supply) {
            $hasInitialRecord = InventoryMovement::where('item_type', 'supply')
                ->where('item_id', $supply->id)
                ->where('movement_type', 'initial_balance')
                ->exists();

            $initial = $hasInitialRecord
                ? floatval(InventoryMovement::where('item_type', 'supply')
                    ->where('item_id', $supply->id)
                    ->where('movement_type', 'initial_balance')
                    ->sum('quantity'))
                : floatval($supply->stock);

            $incomes = floatval(InventoryMovement::where('item_type', 'supply')
                ->where('item_id', $supply->id)
                ->where('movement_type', 'income')
                ->sum('quantity'));

            $outcomes = floatval(InventoryMovement::where('item_type', 'supply')
                ->where('item_id', $supply->id)
                ->where('movement_type', 'outcome')
                ->sum('quantity'));

            $saldo_final = $initial + $incomes - $outcomes;

            return (object) [
                'id' => $supply->id,
                'name' => $supply->name,
                'unit' => $supply->unit ?? 'Unidades',
                'saldo_inicial' => $initial,
                'ingresos' => $incomes,
                'salidas' => $outcomes,
                'saldo_final' => $saldo_final,
                'current_stock' => $supply->stock,
            ];
        });

        // 2. ACTIVOS FIJOS (Dispensadores, congeladoras, exhibidores, etc.)
        $defaultCategories = ['Dispensadores', 'Congeladoras', 'Exhibidores'];
        $dbCategories = FixedAsset::distinct()->pluck('category')->filter()->toArray();
        $assetCategories = array_unique(array_merge($defaultCategories, $dbCategories));

        $assetsData = collect($assetCategories)->map(function ($catName) {
            $totalCount = FixedAsset::where('category', 'like', "%{$catName}%")->count();
            $availableCount = FixedAsset::where('category', 'like', "%{$catName}%")->where('status', 'available')->count();
            $assignedCount = FixedAsset::where('category', 'like', "%{$catName}%")->where('status', 'assigned')->count();

            $hasInitialRecord = InventoryMovement::where('item_type', 'fixed_asset')
                ->where('item_name', $catName)
                ->where('movement_type', 'initial_balance')
                ->exists();

            $initial = $hasInitialRecord
                ? floatval(InventoryMovement::where('item_type', 'fixed_asset')
                    ->where('item_name', $catName)
                    ->where('movement_type', 'initial_balance')
                    ->sum('quantity'))
                : floatval($totalCount);

            $incomes = floatval(InventoryMovement::where('item_type', 'fixed_asset')
                ->where('item_name', $catName)
                ->where('movement_type', 'income')
                ->sum('quantity'));

            $outcomes = floatval(InventoryMovement::where('item_type', 'fixed_asset')
                ->where('item_name', $catName)
                ->where('movement_type', 'outcome')
                ->sum('quantity'));

            $saldo_final = $initial + $incomes - $outcomes;

            return (object) [
                'category' => $catName,
                'total_count' => $totalCount,
                'available_count' => $availableCount,
                'assigned_count' => $assignedCount,
                'saldo_inicial' => $initial,
                'ingresos' => $incomes,
                'salidas' => $outcomes,
                'saldo_final' => $saldo_final,
            ];
        });

        // 3. PRODUCTOS TERMINADOS (Bolsas de hielo, agua, etc.)
        $products = Product::where('is_combo', false)->get()->map(function ($product) {
            $hasInitialRecord = InventoryMovement::where('item_type', 'product')
                ->where('item_id', $product->id)
                ->where('movement_type', 'initial_balance')
                ->exists();

            $initial = $hasInitialRecord
                ? floatval(InventoryMovement::where('item_type', 'product')
                    ->where('item_id', $product->id)
                    ->where('movement_type', 'initial_balance')
                    ->sum('quantity'))
                : floatval($product->stock ?? 0);

            $incomes = floatval(InventoryMovement::where('item_type', 'product')
                ->where('item_id', $product->id)
                ->where('movement_type', 'income')
                ->sum('quantity'));

            $outcomes = floatval(InventoryMovement::where('item_type', 'product')
                ->where('item_id', $product->id)
                ->where('movement_type', 'outcome')
                ->sum('quantity'));

            $saldo_final = $initial + $incomes - $outcomes;

            return (object) [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'saldo_inicial' => $initial,
                'ingresos' => $incomes,
                'salidas' => $outcomes,
                'saldo_final' => $saldo_final,
                'current_stock' => $product->stock,
            ];
        });

        $paymentMethods = PaymentMethod::all();

        return view('inventories.index', compact('supplies', 'assetsData', 'products', 'paymentMethods'));
    }

    public function storeInitialBalance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_type' => 'required|string|in:supply,fixed_asset,product',
            'item_id' => 'nullable',
            'item_name' => 'nullable|string',
            'quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->errors()->first()]);
        }

        // Delete existing initial balance movement for this item
        $query = InventoryMovement::where('item_type', $request->item_type)
            ->where('movement_type', 'initial_balance');

        if ($request->item_id) {
            $query->where('item_id', $request->item_id);
        } else {
            $query->where('item_name', $request->item_name);
        }
        $query->delete();

        // Insert new initial balance
        InventoryMovement::create([
            'item_type' => $request->item_type,
            'item_id' => $request->item_id,
            'item_name' => $request->item_name,
            'movement_type' => 'initial_balance',
            'quantity' => $request->quantity,
            'notes' => 'Saldo inicial configurado por usuario',
            'user_id' => auth()->id(),
        ]);

        // Sync stock if supply
        if ($request->item_type === 'supply' && $request->item_id) {
            $supply = Supply::find($request->item_id);
            if ($supply) {
                $incomes = InventoryMovement::where('item_type', 'supply')->where('item_id', $supply->id)->where('movement_type', 'income')->sum('quantity');
                $outcomes = InventoryMovement::where('item_type', 'supply')->where('item_id', $supply->id)->where('movement_type', 'outcome')->sum('quantity');
                $supply->update(['stock' => $request->quantity + $incomes - $outcomes]);
            }
        }

        // Sync stock if product
        if ($request->item_type === 'product' && $request->item_id) {
            $product = Product::find($request->item_id);
            if ($product) {
                $incomes = InventoryMovement::where('item_type', 'product')->where('item_id', $product->id)->where('movement_type', 'income')->sum('quantity');
                $outcomes = InventoryMovement::where('item_type', 'product')->where('item_id', $product->id)->where('movement_type', 'outcome')->sum('quantity');
                $product->update([
                    'initial_stock' => $request->quantity,
                    'stock' => $request->quantity + $incomes - $outcomes
                ]);
            }
        }

        return response()->json(['status' => true, 'message' => 'Saldo inicial guardado con éxito.']);
    }

    public function storeMovement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_type' => 'required|string|in:supply,fixed_asset,product',
            'item_id' => 'nullable',
            'item_name' => 'nullable|string',
            'movement_type' => 'required|string|in:income,outcome,adjustment',
            'quantity' => 'required|numeric|gt:0',
            'amount' => 'nullable|numeric|min:0',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->errors()->first()]);
        }

        InventoryMovement::create([
            'item_type' => $request->item_type,
            'item_id' => $request->item_id,
            'item_name' => $request->item_name,
            'movement_type' => $request->movement_type,
            'quantity' => $request->quantity,
            'notes' => $request->notes ?? 'Movimiento de inventario manual',
            'user_id' => auth()->id(),
        ]);

        $itemName = $request->item_name;

        // Sync stock if supply
        if ($request->item_type === 'supply' && $request->item_id) {
            $supply = Supply::find($request->item_id);
            if ($supply) {
                $itemName = $supply->name;
                $initial = InventoryMovement::where('item_type', 'supply')->where('item_id', $supply->id)->where('movement_type', 'initial_balance')->sum('quantity');
                $incomes = InventoryMovement::where('item_type', 'supply')->where('item_id', $supply->id)->where('movement_type', 'income')->sum('quantity');
                $outcomes = InventoryMovement::where('item_type', 'supply')->where('item_id', $supply->id)->where('movement_type', 'outcome')->sum('quantity');
                $supply->update(['stock' => $initial + $incomes - $outcomes]);
            }
        }

        // Sync stock if product
        if ($request->item_type === 'product' && $request->item_id) {
            $product = Product::find($request->item_id);
            if ($product) {
                $itemName = $product->name;
                $initial = InventoryMovement::where('item_type', 'product')->where('item_id', $product->id)->where('movement_type', 'initial_balance')->sum('quantity');
                $incomes = InventoryMovement::where('item_type', 'product')->where('item_id', $product->id)->where('movement_type', 'income')->sum('quantity');
                $outcomes = InventoryMovement::where('item_type', 'product')->where('item_id', $product->id)->where('movement_type', 'outcome')->sum('quantity');
                $product->update(['stock' => $initial + $incomes - $outcomes]);
            }
        }

        // Financial & Cashbox Integration if Amount & Payment Method provided
        if ($request->amount > 0 && $request->payment_method_id) {
            if ($request->movement_type === 'income') {
                // Ingreso de inventario comprado = EGRESO / GASTO de dinero
                $category = ExpenseCategory::firstOrCreate(['name' => 'Compra de Inventario']);
                $subcategory = ExpenseSubcategory::firstOrCreate([
                    'expense_category_id' => $category->id,
                    'name' => ucfirst($request->item_type)
                ]);

                Expense::create([
                    'description' => "Compra de inventario: {$itemName} - Cant: {$request->quantity}",
                    'amount' => $request->amount,
                    'date' => now()->format('Y-m-d H:i:s'),
                    'real_date' => now()->format('Y-m-d'),
                    'expense_category_id' => $category->id,
                    'expense_subcategory_id' => $subcategory->id,
                    'payment_method_id' => $request->payment_method_id,
                    'user_id' => auth()->id() ?? 1
                ]);
            } elseif ($request->movement_type === 'outcome') {
                // Salida de inventario vendida/cobrada = INGRESO de dinero a caja
                $cashbox = Cashbox::currentOpen();
                if ($cashbox) {
                    CashboxMovement::create([
                        'cashbox_id' => $cashbox->id,
                        'user_id' => auth()->id(),
                        'payment_method_id' => $request->payment_method_id,
                        'type' => 'income',
                        'amount' => $request->amount,
                        'note' => "Salida/Venta de inventario: {$itemName} - Cant: {$request->quantity}",
                        'date' => now()
                    ]);
                }
            }
        }

        return response()->json(['status' => true, 'message' => 'Movimiento registrado con éxito.']);
    }

    public function history(Request $request, $itemType, $itemId = null)
    {
        $query = InventoryMovement::with('user')
            ->where('item_type', $itemType);

        if ($itemId && is_numeric($itemId)) {
            $query->where('item_id', $itemId);
        } elseif ($request->item_name) {
            $query->where('item_name', $request->item_name);
        }

        $movements = $query->orderBy('created_at', 'desc')->get()->map(function ($m) {
            $typeLabel = 'Movimiento';
            if ($m->movement_type === 'initial_balance') $typeLabel = 'Saldo Inicial';
            if ($m->movement_type === 'income') $typeLabel = 'Ingreso (+)';
            if ($m->movement_type === 'outcome') $typeLabel = 'Salida (-)';
            if ($m->movement_type === 'adjustment') $typeLabel = 'Ajuste';

            return [
                'id' => $m->id,
                'type_label' => $typeLabel,
                'movement_type' => $m->movement_type,
                'quantity' => floatval($m->quantity),
                'notes' => $m->notes ?: '-',
                'user' => $m->user ? $m->user->name : 'Sistema',
                'date' => $m->created_at ? $m->created_at->format('d/m/Y H:i') : '-',
            ];
        });

        return response()->json($movements);
    }

    public function storeSupply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'stock' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->errors()->first()]);
        }

        $supply = Supply::create([
            'name' => $request->name,
            'unit' => $request->unit ?: 'Unidades',
            'stock' => $request->stock ?: 0,
        ]);

        if ($request->stock > 0) {
            InventoryMovement::create([
                'item_type' => 'supply',
                'item_id' => $supply->id,
                'movement_type' => 'initial_balance',
                'quantity' => $request->stock,
                'notes' => 'Saldo inicial al crear insumo',
                'user_id' => auth()->id(),
            ]);
        }

        return response()->json(['status' => true, 'message' => 'Insumo registrado exitosamente.']);
    }
}
