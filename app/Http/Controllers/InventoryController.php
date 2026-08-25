<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supply;
use App\Models\FixedAsset;
use App\Models\Product;
use App\Models\Client;
use App\Models\User;
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
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $isDispatcher = auth()->user()->hasRole('despachador');

        // 0. Consulta de Ventas filtradas por rango de fechas opcional
        $salesQuery = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->where('sales.status', '!=', 'Anulado');

        if ($startDate) {
            $salesQuery->whereDate('sales.date', '>=', $startDate);
        }
        if ($endDate) {
            $salesQuery->whereDate('sales.date', '<=', $endDate);
        }

        $salesTotals = $salesQuery
            ->select('sale_details.product_id', DB::raw('SUM(sale_details.quantity) as total_sold'))
            ->groupBy('sale_details.product_id')
            ->pluck('total_sold', 'product_id');

        // Consumo de Insumos por ventas de productos
        $suppliesSalesTotals = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('product_supplies', 'sale_details.product_id', '=', 'product_supplies.product_id')
            ->where('sales.status', '!=', 'Anulado')
            ->when($startDate, fn($q) => $q->whereDate('sales.date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('sales.date', '<=', $endDate))
            ->select('product_supplies.supply_id', DB::raw('SUM(sale_details.quantity * product_supplies.quantity) as total_consumed'))
            ->groupBy('product_supplies.supply_id')
            ->pluck('total_consumed', 'product_supplies.supply_id');

        // Funciones auxiliares para calcular movimientos según fecha
        $getIncomes = function ($type, $id, $name) use ($startDate, $endDate) {
            $q = InventoryMovement::where('item_type', $type)->whereIn('movement_type', ['income', 'return']);
            if ($id) $q->where('item_id', $id);
            else $q->where('item_name', $name);

            if ($startDate) $q->whereDate('created_at', '>=', $startDate);
            if ($endDate) $q->whereDate('created_at', '<=', $endDate);
            return floatval($q->sum('quantity'));
        };

        $getOutcomesManual = function ($type, $id, $name) use ($startDate, $endDate) {
            $q = InventoryMovement::where('item_type', $type)->where('movement_type', 'outcome');
            if ($id) $q->where('item_id', $id);
            else $q->where('item_name', $name);

            if ($startDate) $q->whereDate('created_at', '>=', $startDate);
            if ($endDate) $q->whereDate('created_at', '<=', $endDate);
            return floatval($q->sum('quantity'));
        };

        $getInitial = function ($type, $id, $name, $currentStock) use ($startDate) {
            $q = InventoryMovement::where('item_type', $type)->where('movement_type', 'initial_balance');
            if ($id) $q->where('item_id', $id);
            else $q->where('item_name', $name);

            $hasInitialRecord = $q->exists();
            $baseInitial = $hasInitialRecord ? floatval($q->sum('quantity')) : floatval($currentStock);

            if (!$startDate) {
                return $baseInitial;
            }

            // Si hay filtro de fecha de inicio, calcular el saldo inicial acumulado previo a esa fecha
            $priorIncomes = floatval(InventoryMovement::where('item_type', $type)
                ->whereIn('movement_type', ['income', 'return'])
                ->when($id, fn($query) => $query->where('item_id', $id), fn($query) => $query->where('item_name', $name))
                ->whereDate('created_at', '<', $startDate)
                ->sum('quantity'));

            $priorOutcomesManual = floatval(InventoryMovement::where('item_type', $type)
                ->where('movement_type', 'outcome')
                ->when($id, fn($query) => $query->where('item_id', $id), fn($query) => $query->where('item_name', $name))
                ->whereDate('created_at', '<', $startDate)
                ->sum('quantity'));

            $priorSales = 0;
            if ($type === 'product' && $id) {
                $priorSales = floatval(DB::table('sale_details')
                    ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                    ->where('sales.status', '!=', 'Anulado')
                    ->where('sale_details.product_id', $id)
                    ->whereDate('sales.date', '<', $startDate)
                    ->sum('sale_details.quantity'));
            } elseif ($type === 'supply' && $id) {
                $priorSales = floatval(DB::table('sale_details')
                    ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                    ->join('product_supplies', 'sale_details.product_id', '=', 'product_supplies.product_id')
                    ->where('sales.status', '!=', 'Anulado')
                    ->where('product_supplies.supply_id', $id)
                    ->whereDate('sales.date', '<', $startDate)
                    ->sum(DB::raw('sale_details.quantity * product_supplies.quantity')));
            }

            return $baseInitial + $priorIncomes - ($priorOutcomesManual + $priorSales);
        };

        // 1. INSUMOS (Bidones, tapas, sellos, etiquetas, bolsas, etc.)
        $suppliesQuery = Supply::query();
        if ($isDispatcher) {
            $suppliesQuery->where('allowed_for_dispatchers', true);
        }
        $supplies = $suppliesQuery->get()->map(function ($supply) use ($suppliesSalesTotals, $getInitial, $getIncomes, $getOutcomesManual) {
            $initial = $getInitial('supply', $supply->id, null, $supply->stock);
            $incomes = $getIncomes('supply', $supply->id, null);

            $salidas_ventas = floatval($suppliesSalesTotals[$supply->id] ?? 0);
            $salidas_manuales = $getOutcomesManual('supply', $supply->id, null);

            $outcomes = $salidas_ventas + $salidas_manuales;
            $saldo_final = $initial + $incomes - $outcomes;

            return (object) [
                'id' => $supply->id,
                'name' => $supply->name,
                'unit' => $supply->unit ?? 'Unidades',
                'allowed_for_dispatchers' => (bool) $supply->allowed_for_dispatchers,
                'saldo_inicial' => $initial,
                'ingresos' => $incomes,
                'salidas' => $outcomes,
                'saldo_final' => $saldo_final,
                'current_stock' => $supply->stock,
            ];
        });

        // 2. ACTIVOS FIJOS (Dispensadores, congeladoras, exhibidores, etc.)
        $assetCategoryObj = ExpenseCategory::where('name', 'like', '%Activo fijo%')->first();
        $subCatObjs = $assetCategoryObj ? $assetCategoryObj->subcategories : collect();
        $subCatNames = $subCatObjs->pluck('name')->toArray();
        $defaultCategories = ['congeladoras', 'exhibidores', 'dispensadores', 'maquina gourmet', 'maquina 1500 kg', 'planta de agua', 'selladora', 'repuestos', 'moto', 'mostradores', 'camion'];
        $dbCategories = FixedAsset::distinct()->pluck('category')->filter()->toArray();
        
        // Unificar categorías preservando nombre formateado
        $allCatNames = array_unique(array_merge($subCatNames, $defaultCategories, $dbCategories));
        $allAssets = FixedAsset::all();

        $assetsData = collect($allCatNames)->map(function ($catName) use ($allAssets, $subCatObjs, $startDate, $endDate, $getInitial, $getIncomes, $getOutcomesManual) {
            $catLower = strtolower(trim($catName));
            $matchingAssets = $allAssets->filter(function ($a) use ($catLower) {
                return strtolower(trim($a->category)) === $catLower;
            });

            $totalCount = $matchingAssets->count();
            $availableCount = $matchingAssets->where('status', 'available')->count();
            $assignedCount = $matchingAssets->where('status', 'assigned')->count();

            $initial = $getInitial('fixed_asset', null, $catName, $totalCount);
            $incomes = $getIncomes('fixed_asset', null, $catName);

            // Asignaciones a clientes en fixed_asset_assignments
            $salidas_asignaciones = floatval(DB::table('fixed_asset_assignments')
                ->join('fixed_assets', 'fixed_asset_assignments.fixed_asset_id', '=', 'fixed_assets.id')
                ->whereRaw('LOWER(TRIM(fixed_assets.category)) = ?', [$catLower])
                ->when($startDate, fn($q) => $q->whereDate('fixed_asset_assignments.assigned_date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('fixed_asset_assignments.assigned_date', '<=', $endDate))
                ->count());

            $salidas_manuales = $getOutcomesManual('fixed_asset', null, $catName);
            $outcomes = $salidas_asignaciones + $salidas_manuales;
            $saldo_final = $initial + $incomes - $outcomes;

            // Verificar si está habilitado para despachador
            $matchingSub = $subCatObjs->first(fn($s) => strtolower(trim($s->name)) === $catLower);
            $isAllowed = $matchingSub ? (bool) $matchingSub->allowed_for_dispatchers : $matchingAssets->contains('allowed_for_dispatchers', true);

            return (object) [
                'category' => ucfirst($catName),
                'total_count' => $totalCount,
                'available_count' => $availableCount,
                'assigned_count' => $assignedCount,
                'allowed_for_dispatchers' => $isAllowed,
                'saldo_inicial' => $initial,
                'ingresos' => $incomes,
                'salidas' => $outcomes,
                'saldo_final' => $saldo_final,
            ];
        });

        if ($isDispatcher) {
            $assetsData = $assetsData->filter(fn($a) => $a->allowed_for_dispatchers)->values();
        }

        // 3. PRODUCTOS TERMINADOS (Bolsas de hielo, agua, etc.)
        $productsQuery = Product::where('is_combo', false);
        if ($isDispatcher) {
            $productsQuery->where('allowed_for_dispatchers', true);
        }
        $products = $productsQuery->get()->map(function ($product) use ($salesTotals, $getInitial, $getIncomes, $getOutcomesManual) {
            $initial = $getInitial('product', $product->id, null, $product->stock ?? 0);
            $incomes = $getIncomes('product', $product->id, null);

            $salidas_ventas = floatval($salesTotals[$product->id] ?? 0);
            $salidas_manuales = $getOutcomesManual('product', $product->id, null);

            $outcomes = $salidas_ventas + $salidas_manuales;
            $saldo_final = $initial + $incomes - $outcomes;

            return (object) [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'allowed_for_dispatchers' => (bool) $product->allowed_for_dispatchers,
                'saldo_inicial' => $initial,
                'ingresos' => $incomes,
                'salidas' => $outcomes,
                'saldo_final' => $saldo_final,
                'current_stock' => $product->stock,
            ];
        });

        $clients = Client::orderBy('name', 'asc')->get();
        $dispatchers = User::where('role', 'despachador')->orderBy('name', 'asc')->get();
        $paymentMethods = PaymentMethod::all();

        return view('inventories.index', compact('supplies', 'assetsData', 'products', 'clients', 'dispatchers', 'paymentMethods', 'isDispatcher'));
    }

    public function toggleDispatcherPermission(Request $request)
    {
        if (auth()->user()->hasRole('despachador')) {
            return response()->json(['status' => false, 'error' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'item_type' => 'required|string|in:supply,product,fixed_asset',
            'item_id' => 'nullable',
            'item_name' => 'nullable|string',
            'allowed' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->errors()->first()]);
        }

        if ($request->item_type === 'supply') {
            $item = Supply::find($request->item_id);
            if (!$item) return response()->json(['status' => false, 'error' => 'Insumo no encontrado']);
            $item->allowed_for_dispatchers = $request->allowed;
            $item->save();
        } elseif ($request->item_type === 'product') {
            $item = Product::find($request->item_id);
            if (!$item) return response()->json(['status' => false, 'error' => 'Producto no encontrado']);
            $item->allowed_for_dispatchers = $request->allowed;
            $item->save();
        } elseif ($request->item_type === 'fixed_asset') {
            $catName = $request->item_name ?: $request->item_id;
            $catLower = strtolower(trim($catName));

            // Actualizar o crear subcategoría
            $assetCategory = ExpenseCategory::firstOrCreate(['name' => 'Activo fijo']);
            $subcat = ExpenseSubcategory::firstOrCreate(
                ['expense_category_id' => $assetCategory->id, 'name' => ucfirst($catName)]
            );
            $subcat->allowed_for_dispatchers = $request->allowed;
            $subcat->save();

            // Actualizar activos fijos con esa categoría
            FixedAsset::whereRaw('LOWER(TRIM(category)) = ?', [$catLower])
                ->update(['allowed_for_dispatchers' => $request->allowed]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Permiso actualizado correctamente.',
            'allowed' => (bool) $request->allowed
        ]);
    }

    public function storeInitialBalance(Request $request)
    {
        if (auth()->user()->hasRole('despachador')) {
            return response()->json(['status' => false, 'error' => 'No autorizado para configurar saldos iniciales.'], 403);
        }

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
                $incomes = InventoryMovement::where('item_type', 'supply')->where('item_id', $supply->id)->whereIn('movement_type', ['income', 'return'])->sum('quantity');
                $outcomes = InventoryMovement::where('item_type', 'supply')->where('item_id', $supply->id)->where('movement_type', 'outcome')->sum('quantity');
                $supply->update(['stock' => $request->quantity + $incomes - $outcomes]);
            }
        }

        // Sync stock if product
        if ($request->item_type === 'product' && $request->item_id) {
            $product = Product::find($request->item_id);
            if ($product) {
                $incomes = InventoryMovement::where('item_type', 'product')->where('item_id', $product->id)->whereIn('movement_type', ['income', 'return'])->sum('quantity');
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
        $isDispatcher = auth()->user()->hasRole('despachador');

        $validator = Validator::make($request->all(), [
            'item_type' => 'required|string|in:supply,fixed_asset,product',
            'item_id' => 'nullable',
            'item_name' => 'nullable|string',
            'client_id' => 'nullable|exists:clients,id',
            'dispatcher_id' => 'nullable|exists:users,id',
            'movement_type' => 'required|string|in:income,outcome,adjustment,return',
            'quantity' => 'required|numeric|gt:0',
            'amount' => 'nullable|numeric|min:0',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->errors()->first()]);
        }

        // Validación de permisos para despachadores
        if ($isDispatcher) {
            if ($request->movement_type !== 'return') {
                return response()->json(['status' => false, 'error' => 'Los despachadores solo pueden registrar devoluciones.'], 403);
            }

            if ($request->item_type === 'supply' && $request->item_id) {
                $supply = Supply::find($request->item_id);
                if (!$supply || !$supply->allowed_for_dispatchers) {
                    return response()->json(['status' => false, 'error' => 'Este insumo no está habilitado para despachadores.'], 403);
                }
            }
            if ($request->item_type === 'product' && $request->item_id) {
                $prod = Product::find($request->item_id);
                if (!$prod || !$prod->allowed_for_dispatchers) {
                    return response()->json(['status' => false, 'error' => 'Este producto no está habilitado para despachadores.'], 403);
                }
            }
        }

        $client = null;
        if ($request->client_id) {
            $client = Client::find($request->client_id);
        }

        // Si es despachador, autoasignar su ID; si es admin/asistente, usar el seleccionado
        $finalDispatcherId = $isDispatcher ? auth()->id() : $request->dispatcher_id;
        $dispatcher = $finalDispatcherId ? User::find($finalDispatcherId) : null;

        $defaultNote = $request->movement_type === 'return' ? 'Devolución de inventario' : 'Movimiento de inventario manual';
        $finalNote = $request->notes ?: $defaultNote;

        InventoryMovement::create([
            'item_type' => $request->item_type,
            'item_id' => $request->item_id,
            'item_name' => $request->item_name,
            'client_id' => $request->client_id,
            'dispatcher_id' => $finalDispatcherId,
            'movement_type' => $request->movement_type,
            'quantity' => $request->quantity,
            'notes' => $finalNote,
            'user_id' => auth()->id(),
        ]);

        $itemName = $request->item_name;

        // Sync stock if supply
        if ($request->item_type === 'supply' && $request->item_id) {
            $supply = Supply::find($request->item_id);
            if ($supply) {
                $itemName = $supply->name;
                $initial = InventoryMovement::where('item_type', 'supply')->where('item_id', $supply->id)->where('movement_type', 'initial_balance')->sum('quantity');
                $incomes = InventoryMovement::where('item_type', 'supply')->where('item_id', $supply->id)->whereIn('movement_type', ['income', 'return'])->sum('quantity');
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
                $incomes = InventoryMovement::where('item_type', 'product')->where('item_id', $product->id)->whereIn('movement_type', ['income', 'return'])->sum('quantity');
                $outcomes = InventoryMovement::where('item_type', 'product')->where('item_id', $product->id)->where('movement_type', 'outcome')->sum('quantity');
                $product->update(['stock' => $initial + $incomes - $outcomes]);
            }
        }

        // Financial & Cashbox Integration if Amount & Payment Method provided
        if ($request->amount > 0 && $request->payment_method_id) {
            $clientNote = $client ? " (Cliente: {$client->name})" : "";
            $dispatcherNote = $dispatcher ? " (Despachador: {$dispatcher->name})" : "";

            if ($request->movement_type === 'income') {
                // Ingreso de inventario comprado = EGRESO / GASTO de dinero
                $category = ExpenseCategory::firstOrCreate(['name' => 'Compra de Inventario']);
                $subcategory = ExpenseSubcategory::firstOrCreate([
                    'expense_category_id' => $category->id,
                    'name' => ucfirst($request->item_type)
                ]);

                Expense::create([
                    'description' => "Compra de inventario: {$itemName} - Cant: {$request->quantity}{$clientNote}{$dispatcherNote}",
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
                        'dispatcher_id' => $finalDispatcherId,
                        'payment_method_id' => $request->payment_method_id,
                        'type' => 'income',
                        'amount' => $request->amount,
                        'note' => "Salida/Venta de inventario: {$itemName} - Cant: {$request->quantity}{$clientNote}{$dispatcherNote}",
                        'date' => now()
                    ]);
                }
            } elseif ($request->movement_type === 'return') {
                // Devolución de inventario (Devolución de cliente con reembolso de dinero) = EGRESO de dinero de caja
                $cashbox = Cashbox::currentOpen();
                if ($cashbox) {
                    CashboxMovement::create([
                        'cashbox_id' => $cashbox->id,
                        'user_id' => auth()->id(),
                        'dispatcher_id' => $finalDispatcherId,
                        'payment_method_id' => $request->payment_method_id,
                        'type' => 'expense',
                        'amount' => $request->amount,
                        'note' => "Devolución de inventario: {$itemName} - Cant: {$request->quantity}{$clientNote}{$dispatcherNote}",
                        'date' => now()
                    ]);
                }
            }
        }

        return response()->json(['status' => true, 'message' => 'Movimiento registrado con éxito.']);
    }

    public function history(Request $request, $itemType, $itemId = null)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $query = InventoryMovement::with(['user', 'client', 'dispatcher'])
            ->where('item_type', $itemType);

        if ($itemId && is_numeric($itemId) && intval($itemId) > 0) {
            $query->where('item_id', $itemId);
        } elseif ($request->item_name) {
            $query->where('item_name', $request->item_name);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $movements = $query->orderBy('created_at', 'desc')->get()->map(function ($m) {
            $typeLabel = 'Movimiento';
            if ($m->movement_type === 'initial_balance') $typeLabel = 'Saldo Inicial';
            if ($m->movement_type === 'income') $typeLabel = 'Ingreso (+)';
            if ($m->movement_type === 'outcome') $typeLabel = 'Salida (-)';
            if ($m->movement_type === 'return') $typeLabel = 'Devolución (+)';
            if ($m->movement_type === 'adjustment') $typeLabel = 'Ajuste';

            $clientName = $m->client ? $m->client->name : null;
            $dispatcherName = $m->dispatcher ? $m->dispatcher->name : null;

            return [
                'id' => $m->id,
                'type_label' => $typeLabel,
                'movement_type' => $m->movement_type,
                'quantity' => floatval($m->quantity),
                'notes' => $m->notes ?: '-',
                'client_name' => $clientName,
                'dispatcher_name' => $dispatcherName,
                'user' => $m->user ? $m->user->name : 'Sistema',
                'date' => $m->created_at ? $m->created_at->format('d/m/Y H:i') : '-',
                'raw_date' => $m->created_at ? $m->created_at->toDateTimeString() : '1970-01-01 00:00:00',
            ];
        });

        // Historial adicional por tipo de ítem
        if ($itemType === 'product' && !empty($itemId) && is_numeric($itemId) && intval($itemId) > 0) {
            $salesQuery = DB::table('sale_details')
                ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                ->leftJoin('clients', 'sales.client_id', '=', 'clients.id')
                ->leftJoin('users as dispatchers', 'sales.dispatcher_id', '=', 'dispatchers.id')
                ->leftJoin('users', 'sales.user_id', '=', 'users.id')
                ->where('sales.status', '!=', 'Anulado')
                ->where('sale_details.product_id', $itemId);

            if ($startDate) {
                $salesQuery->whereDate('sales.date', '>=', $startDate);
            }
            if ($endDate) {
                $salesQuery->whereDate('sales.date', '<=', $endDate);
            }

            $salesMovements = $salesQuery
                ->select(
                    'sales.id as sale_id',
                    'sales.date as date_time',
                    'sale_details.quantity as qty',
                    'sales.order as order',
                    'sales.guide as guide',
                    'clients.name as client_name',
                    'dispatchers.name as dispatcher_name',
                    'users.name as user_name'
                )
                ->get()
                ->map(function ($s) {
                    $clientInfo = $s->client_name ? " (Cliente: {$s->client_name})" : '';
                    $ref = $s->guide ? "Guía: {$s->guide}" : "Pedido: {$s->order}";
                    return [
                        'id' => 'sale_' . $s->sale_id,
                        'type_label' => 'Salida (Venta)',
                        'movement_type' => 'outcome',
                        'quantity' => floatval($s->qty),
                        'notes' => "Venta {$ref}{$clientInfo}",
                        'client_name' => $s->client_name,
                        'dispatcher_name' => $s->dispatcher_name,
                        'user' => $s->user_name ?: 'Sistema',
                        'date' => $s->date_time ? \Carbon\Carbon::parse($s->date_time)->format('d/m/Y H:i') : '-',
                        'raw_date' => $s->date_time ?: '1970-01-01 00:00:00',
                    ];
                });

            $allMovements = $movements->concat($salesMovements)->sortByDesc('raw_date')->values();
            return response()->json($allMovements);
        }

        if ($itemType === 'supply' && !empty($itemId) && is_numeric($itemId) && intval($itemId) > 0) {
            $supplySalesMovements = DB::table('sale_details')
                ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                ->join('product_supplies', 'sale_details.product_id', '=', 'product_supplies.product_id')
                ->join('products', 'sale_details.product_id', '=', 'products.id')
                ->leftJoin('clients', 'sales.client_id', '=', 'clients.id')
                ->leftJoin('users as dispatchers', 'sales.dispatcher_id', '=', 'dispatchers.id')
                ->leftJoin('users', 'sales.user_id', '=', 'users.id')
                ->where('sales.status', '!=', 'Anulado')
                ->where('product_supplies.supply_id', $itemId)
                ->when($startDate, fn($q) => $q->whereDate('sales.date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('sales.date', '<=', $endDate))
                ->select(
                    'sales.id as sale_id',
                    'sales.date as date_time',
                    DB::raw('(sale_details.quantity * product_supplies.quantity) as qty'),
                    'products.name as product_name',
                    'sales.order as order',
                    'sales.guide as guide',
                    'clients.name as client_name',
                    'dispatchers.name as dispatcher_name',
                    'users.name as user_name'
                )
                ->get()
                ->map(function ($s) {
                    $clientInfo = $s->client_name ? " (Cliente: {$s->client_name})" : '';
                    $ref = $s->guide ? "Guía: {$s->guide}" : "Pedido: {$s->order}";
                    return [
                        'id' => 'supply_sale_' . $s->sale_id,
                        'type_label' => 'Salida (Consumo Venta)',
                        'movement_type' => 'outcome',
                        'quantity' => floatval($s->qty),
                        'notes' => "Consumo por venta de {$s->product_name} en {$ref}{$clientInfo}",
                        'client_name' => $s->client_name,
                        'dispatcher_name' => $s->dispatcher_name,
                        'user' => $s->user_name ?: 'Sistema',
                        'date' => $s->date_time ? \Carbon\Carbon::parse($s->date_time)->format('d/m/Y H:i') : '-',
                        'raw_date' => $s->date_time ?: '1970-01-01 00:00:00',
                    ];
                });

            $allMovements = $movements->concat($supplySalesMovements)->sortByDesc('raw_date')->values();
            return response()->json($allMovements);
        }

        if ($itemType === 'fixed_asset' && $request->item_name) {
            $catName = $request->item_name;
            $assignments = DB::table('fixed_asset_assignments')
                ->join('fixed_assets', 'fixed_asset_assignments.fixed_asset_id', '=', 'fixed_assets.id')
                ->leftJoin('clients', 'fixed_asset_assignments.client_id', '=', 'clients.id')
                ->where('fixed_assets.category', 'like', "%{$catName}%")
                ->when($startDate, fn($q) => $q->whereDate('fixed_asset_assignments.assigned_date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('fixed_asset_assignments.assigned_date', '<=', $endDate))
                ->select(
                    'fixed_asset_assignments.id as assignment_id',
                    'fixed_asset_assignments.assigned_date',
                    'fixed_asset_assignments.returned_date',
                    'fixed_assets.name as asset_name',
                    'fixed_assets.internal_code',
                    'clients.name as client_name'
                )
                ->get();

            $assetMovements = collect();
            foreach ($assignments as $a) {
                $clientInfo = $a->client_name ? " a cliente {$a->client_name}" : '';
                $codeInfo = $a->internal_code ? " (Cód: {$a->internal_code})" : '';

                $assetMovements->push([
                    'id' => 'assign_' . $a->assignment_id,
                    'type_label' => 'Salida (Asignación)',
                    'movement_type' => 'outcome',
                    'quantity' => 1.00,
                    'notes' => "Asignación de {$a->asset_name}{$codeInfo}{$clientInfo}",
                    'client_name' => $a->client_name,
                    'dispatcher_name' => null,
                    'user' => 'Sistema',
                    'date' => $a->assigned_date ? \Carbon\Carbon::parse($a->assigned_date)->format('d/m/Y H:i') : '-',
                    'raw_date' => $a->assigned_date ?: '1970-01-01 00:00:00',
                ]);

                if ($a->returned_date) {
                    $assetMovements->push([
                        'id' => 'ret_' . $a->assignment_id,
                        'type_label' => 'Ingreso (Devolución)',
                        'movement_type' => 'income',
                        'quantity' => 1.00,
                        'notes' => "Devolución de {$a->asset_name}{$codeInfo}{$clientInfo}",
                        'client_name' => $a->client_name,
                        'dispatcher_name' => null,
                        'user' => 'Sistema',
                        'date' => \Carbon\Carbon::parse($a->returned_date)->format('d/m/Y H:i'),
                        'raw_date' => $a->returned_date,
                    ]);
                }
            }

            $allMovements = $movements->concat($assetMovements)->sortByDesc('raw_date')->values();
            return response()->json($allMovements);
        }

        return response()->json($movements);
    }

    public function storeSupply(Request $request)
    {
        if (auth()->user()->hasRole('despachador')) {
            return response()->json(['status' => false, 'error' => 'No autorizado para crear insumos.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'stock' => 'nullable|numeric|min:0',
            'allowed_for_dispatchers' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->errors()->first()]);
        }

        $supply = Supply::create([
            'name' => $request->name,
            'unit' => $request->unit ?: 'Unidades',
            'stock' => $request->stock ?: 0,
            'allowed_for_dispatchers' => $request->has('allowed_for_dispatchers') ? (bool) $request->allowed_for_dispatchers : false,
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
